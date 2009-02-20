<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_watch_list extends db_watch
{
	const DATABASE = 'watch_list';
	
	const NAME = 'Changed Directories from Database';

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	static function handles($dir)
	{
		global $mysql;
		
		if(is_dir($dir))
		{
			// changed directories or directories that don't exist in the database
			$db_files = $mysql->query(array(
					'SELECT' => 'files',
					'COLUMNS' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
				)
			);
			
			if( (count($db_files) == 0 || date("Y-m-d h:i:s", filemtime($dir)) != $db_files[0]['Filedate']) )
			{
				// make sure it is in the watch list and not the ignore list
				$watched = db_watch::get($mysql, array(), $count, $error);
				
				$is_ignored = false;
				$is_watched = false;
				foreach($watched as $i => $watch)
				{
					if(substr($dir, 0, strlen($watch['Filepath'])-1) == substr($watch['Filepath'], 1))
					{
						if($watch['Filepath'][0] == '^')
							$is_watched = true;
						elseif($watch['Filepath'][0] == '!')
							$is_ignored = true;
					}
				}
				
				// if the path is watched and ignored that means there is an ignore directory inside the watch directory
				//   this is what we want, so always return false in this case
				if($is_ignored) return false;
				
				// even if it isn't ignored we still have to check if it is even watched
				if($is_watched) return true;
			}
		}
		
		return false;
	}

	static function handle($mysql, $dir)
	{		
		if(db_watch_list::handles($dir))
		{
			$db_watch_list = $mysql->query(array(
					'SELECT' => db_watch_list::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
				)
			);
			
			if( count($db_watch_list) == 0 )
			{
				$id = db_watch_list::add($mysql, $dir);
				
				return db_watch_list::handle_dir($mysql, $dir);
			}
			else
			{
				print 'Searching directory: ' . $dir . "\n";
				flush();
				
				// search all the files in the directory
				$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
				
				foreach($files as $i => $file)
				{
					if(is_file($file['Filepath']))
					{
						db_watch_list::handle_file($mysql, $file['Filepath']);
					}
				}
				
				db_watch_list::handle_file($mysql, $dir);
				
				// delete the selected folder from the database
				$mysql->query(array('DELETE' => 'watch_list', 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
			}
		}
		// check directories recursively
		else
		{
			return db_watch_list::handle_dir($mysql, $dir);
		}
		
		return true;
	}
	
	static function handle_dir($mysql, $dir)
	{
		global $tm_start, $secs_total, $state;
		
		if(is_dir($dir))
		{
			print 'Looking for changes in: ' . $dir . "\n";
			flush();
		
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
					$status = db_watch_list::handle($mysql, $file['Filepath']);
					
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
	
	static function handle_file($mysql, $file)
	{
		foreach($GLOBALS['modules'] as $i => $module)
		{
			// never pass is to fs_file, it is only used to internals in this case
			if($module != 'fs_file' && $module != 'db_watch' && $module != 'db_watch_list')
				call_user_func_array($module . '::handle', array($mysql, $file));
		}
	}
	
	static function add($mysql, $file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		print 'Queueing directory: ' . $file . "\n";
		flush();
		
		// add to database
		$id = $mysql->query(array('INSERT' => 'watch_list', 'VALUES' => $fileinfo));
		
		return $id;
		
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		return db_file::get($mysql, $request, $count, $error, get_class());
	}
	
	static function cleanup($mysql, $watched, $ignored)
	{
		// call default cleanup function
		db_file::cleanup($mysql, $watched, $ignored, get_class());
	}
}

?>