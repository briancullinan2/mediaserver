<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_watch_list extends db_watch
{
	const DATABASE = 'watch_list';
	
	const NAME = 'New and Changed Directories from Database';

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	static function handles($dir, $file = NULL)
	{
		global $database, $watched, $ignored, $should_clean;
		
		if(!isset($database)) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
		
		if(is_dir($dir))
		{
			if($file == NULL)
			{
				// changed directories or directories that don't exist in the database
				$db_files = $database->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => array('id', 'Filedate'),
						'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
					)
				);
				if(count($db_files) > 0)
				{
					$file = $db_files[0];
				}
			}
			
			// make sure it is in the watch list and not the ignore list
			if(!isset($ignored)) $ignored = db_watch::get($database, array('search_Filepath' => '^!'), $count, $error);
			if(!isset($watched)) $watched = db_watch::get($database, array('search_Filepath' => '^\^'), $count, $error);
			
			
			if( !isset($file) || date("Y-m-d h:i:s", filemtime($dir)) != $file['Filedate'] )
			{
				// only change should clean if it was because of time difference
				if(isset($file))
					$should_clean = true;
				return self::is_watched($dir, $watched, $ignored);
			}
			else
			{
				$db_files = $database->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => array('count(*)'),
						'WHERE' => 'Filepath REGEXP "^' . addslashes(preg_quote($dir)) . '[^' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . ']+' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . '?$"'
					)
				);
				
				// check for file count inconsistency but don't process anything
				$count = 0;
				if ($dh = opendir($dir))
				{
					// count files
					while (($file = readdir($dh)) !== false)
					{
						if(fs_file::handles($dir . $file, true))
						{
							$count++;
						}
						
						// return if count is different from database
						if($count > $db_files[0]['count(*)'])
						{
							$result = self::is_watched($dir, $watched, $ignored);
							if($result == true)
								log_error('Directory count inconsitency: too few files in database!');
							return $result;
						}
					}
				}
				
				// if count if less then number of directories in database
				if($count < $db_files[0]['count(*)'])
				{
					$result = self::is_watched($dir, $watched, $ignored);
					if($result == true)
						log_error('Directory count inconsitency: too many files in database!');
					return $result;
				}
			}
		}
		
		return false;
	}

	static function is_watched($dir, $watched, $ignored)
	{
		$is_ignored = false;
		$is_watched = false;
		foreach($watched as $i => $watch)
		{
			if(substr($dir, 0, strlen($watch['Filepath'])) == $watch['Filepath'])
			{
				$is_watched = true;
			}
		}
		foreach($ignored as $i => $ignore)
		{
			if(substr($dir, 0, strlen($ignore['Filepath'])) == $ignore['Filepath'])
			{
				$is_ignored = true;
			}
		}
		
		// if the path is watched and ignored that means there is an ignore directory inside the watch directory
		//   this is what we want, so always return false in this case
		if($is_ignored) return false;
		
		// even if it isn't ignored we still have to check if it is even watched
		if($is_watched) return true;
		
		return false;
	}

	static function handle($database, $dir)
	{
		if(self::handles($dir))
		{
			$db_watch_list = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
				)
			);
			
			if( count($db_watch_list) == 0 )
			{
				$id = self::add($database, $dir);
				
				return self::handle_dir($database, $dir);
			}
			else
			{
				log_error('Searching directory: ' . $dir);
				
				// search all the files in the directory
				$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
				
				foreach($files as $i => $file)
				{
					if(is_file($file['Filepath']))
					{
						self::handle_file($database, $file['Filepath']);
					}
				}
				
				self::handle_file($database, $dir);
				
				// delete the selected folder from the database
				$database->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
			}
		}
		// check directories recursively
		else
		{
			return self::handle_dir($database, $dir);
		}
		
		return true;
	}
	
	static function handle_dir($database, $dir)
	{
		global $tm_start, $secs_total, $state;
		
		if(is_dir($dir))
		{
			log_error('Looking for changes in: ' . $dir);
		
			$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
					
			if( isset($state) && is_array($state) ) $state_current = array_pop($state);
			
			$i = 0;
			// check state for starting index
			if( isset($state_current) && isset($files[$state_current['index']]) && $files[$state_current['index']]['Filepath'] == $state_current['file'] )
			{
				$i = $state_current['index'];
			}
			elseif(isset($state_current))
			{
				// put it back on because it doesn't match
				array_push($state, $state_current);
			}
			
			$max = count($files);
			// keep going until all files in directory have been read
			for($i; $i < $max; $i++)
			{				
				$file = $files[$i];
				
				if(is_dir($file['Filepath']))
				{
					
					// check if execution time is too long
					$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
					
					if( $secs_total > DIRECTORY_SEEK_TIME )
					{
						// reset previous state when time runs out
						$state = array();
					
						// save some state information
						array_push($state, array('index' => $i, 'file' => $file['Filepath']));
						
						return false;
					}
				
					// keep processing files
					$status = self::handle($database, $file['Filepath']);
					
					if( $status === false )
					{
						array_push($state, array('index' => $i, 'file' => $file['Filepath']));
						
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	static function handle_file($database, $file)
	{
		foreach($GLOBALS['modules'] as $i => $module)
		{
			// never pass is to fs_file, it is only used to internals in this case
			if($module != 'fs_file' && $module != 'db_watch' && $module != 'db_watch_list')
				call_user_func_array($module . '::handle', array($database, $file));
		}
	}
	
	static function add($database, $file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		log_error('Queueing directory: ' . $file);
		
		// add to database
		$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
		return $id;
	}
	
	static function get($database, $request, &$count, &$error)
	{
		// if this module handles this type of file and the get is being called
		//   then we must add new files to database! so handle() it
		if(isset($request['file']))
		{
			// search all the files in the directory
			$files = fs_file::get(NULL, array('dir' => $request['file'], 'limit' => 32000), $count, $error, true);
			
			foreach($files as $i => $file)
			{
				self::handle_file($database, $file['Filepath']);
			}
			
			self::handle_file($database, $request['file']);
			
			// delete the selected folder from the database
			$database->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['file']) . '"'));
		}
		
		return db_file::get($database, $request, $count, $error, get_class());
	}
	
	static function cleanup($database, $watched, $ignored)
	{
		// call default cleanup function
		db_file::cleanup($database, $watched, $ignored, get_class());
	}
}

?>