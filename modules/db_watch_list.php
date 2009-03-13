<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_watch.php';

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
		
		$dir = str_replace('\\', '/', $dir);
		
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
		{
			if(!isset($database)) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
		
			// make sure it is in the watch list and not the ignore list
			if(!isset($ignored)) $ignored = db_watch::get($database, array('search_Filepath' => '/^!/'), $count, $error);
			if(!isset($watched)) $watched = db_watch::get($database, array('search_Filepath' => '/^\\^/'), $count, $error);
			
			if(self::is_watched($dir, $watched, $ignored))
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
				
				
				if( !isset($file) || date("Y-m-d h:i:s", filemtime($dir)) != $file['Filedate'] )
				{
					// only change should clean if it was because of time difference
					if(isset($file) && $should_clean === false)
						$should_clean = true;
					return true;
				}
				else
				{
					$db_files = $database->query(array(
							'SELECT' => db_file::DATABASE,
							'COLUMNS' => array('count(*)'),
							'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
						)
					);
					
					// check for file count inconsistency but don't process anything
					$count = 1;
					if ($dh = opendir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
					{
						// count files
						while (($file = readdir($dh)) !== false)
						{
							if(db_file::handles($dir . $file))
							{
								if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir . $file)))
								{
									if(self::is_watched($dir . $file, $watched, $ignored))
										$count++;
								}
								else
								{
									$count++;
								}
							}
							
							// return if count is different from database
							if($count > $db_files[0]['count(*)'])
							{
								log_error('Directory count inconsitency: too few files in database!');
								return true;
							}
						}
						closedir($dh);
					}
					
					// if count if less then number of directories in database
					if($count < $db_files[0]['count(*)'])
					{
						log_error('Directory count inconsitency: too many files in database!');
						return true;
					}
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
		$dir = str_replace('\\', '/', $dir);
		
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
					if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file['Filepath'])))
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
			// yes we are checking it twice but it is better to be safe then sorry
			if(!isset($ignored)) $ignored = db_watch::get($database, array('search_Filepath' => '/^!/'), $count, $error);
			if(!isset($watched)) $watched = db_watch::get($database, array('search_Filepath' => '/^\\^/'), $count, $error);
			
			if(self::is_watched($dir, $watched, $ignored))
			{
				// search recursively
				$status = self::handle_dir($database, $dir);
				
				// remove any occurance of this directory from the database
				//  it only gets here if it isn't handled, so it shouldn't be in the database
				$database->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
				
				return $status;
			}
		}
		
		return true;
	}
	
	static function handle_dir($database, $dir)
	{
		global $tm_start, $secs_total, $state, $dirs;
		
		if(!isset($dirs))
			$dirs = array();
		
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
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
				
				if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file['Filepath'])) && !in_array(realpath($file['Filepath']), $dirs))
				{
					$dirs[] = realpath($file['Filepath']);
					
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
			$request['file'] = str_replace('\\', '/', $request['file']);
			if(USE_ALIAS == true) $request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
			if(self::handles($request['file']))
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
			else
			{
				return array();
			}
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
