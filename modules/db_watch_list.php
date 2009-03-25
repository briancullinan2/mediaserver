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
		$dir = str_replace('\\', '/', $dir);
		
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
		{
			if(self::is_watched($dir))
			{
				if($file == NULL)
				{
					// changed directories or directories that don't exist in the database
					$db_files = $GLOBALS['database']->query(array(
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
					return true;
				}
				else
				{
					$db_files = $GLOBALS['database']->query(array(
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
								if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir . $file . '/')))
								{
									if(self::is_watched($dir . $file . '/'))
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

	static function is_watched($dir)
	{
		$is_ignored = false;
		$is_watched = false;
		foreach($GLOBALS['watched'] as $i => $watch)
		{
			if(substr($dir, 0, strlen($watch['Filepath'])) == $watch['Filepath'])
			{
				$is_watched = true;
			}
		}
		foreach($GLOBALS['ignored'] as $i => $ignore)
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

	static function handle($dir)
	{
		$dir = str_replace('\\', '/', $dir);
		
		if(self::handles($dir))
		{
			$db_watch_list = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
				)
			);
			
			if( count($db_watch_list) == 0 )
			{
				$id = self::add($dir);
				
				return self::handle_dir($dir);
			}
			else
			{
				log_error('Scanning directory: ' . $dir);
				
				// search all the files in the directory
				$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
				
				// send new/changed files to other modules
				$paths = array();
				$paths[] = $dir;
				foreach($files as $i => $file)
				{
					self::handle_file($file['Filepath']);
						
					$paths[] = $file['Filepath'];
				}
				
				// search for removed files
				$db_files = $GLOBALS['database']->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => array('Filepath'),
						'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
					)
				);
				
				$db_paths = array();
				foreach($db_files as $j => $file)
				{
					if(!in_array($file['Filepath'], $paths))
					{
						log_error('Removing: ' . $file['Filepath']);
						
						// remove file from each module
						foreach($GLOBALS['modules'] as $i => $module)
						{
							if($module != 'fs_file' && $module != 'db_watch' && $module != 'db_watch_list')
								call_user_func_array($module . '::remove', array($file['Filepath'], $module));
						}
					}
					$db_paths[] = $file['Filepath'];
				}
				
				// add current directory to database
				self::handle_file($dir);
				
				// check for new files
				$paths = array_diff($paths, $db_paths);
				foreach($paths as $i => $path)
				{
					if(is_dir($path))
					{
						self::add($path);
					}
				}
				
				// delete the selected folder from the database
				$GLOBALS['database']->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
			}
		}
		// check directories recursively
		else
		{
			// yes we are checking it twice but it is better to be safe then sorry
			if(self::is_watched($dir))
			{
				// search recursively
				$status = self::handle_dir($dir);
				
				// remove any occurance of this directory from the database
				//  it only gets here if it isn't handled, so it shouldn't be in the database
				$GLOBALS['database']->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
				
				return $status;
			}
		}
		
		return true;
	}
	
	static function handle_dir($dir)
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
					$status = self::handle($file['Filepath']);
					
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
	
	static function handle_file($file)
	{
		foreach($GLOBALS['modules'] as $i => $module)
		{
			// never pass is to fs_file, it is only used to internals in this case
			if($module != 'fs_file' && $module != 'db_watch' && $module != 'db_watch_list')
				call_user_func_array($module . '::handle', array($file));
		}
	}
	
	static function add($file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		log_error('Queueing directory: ' . $file);
		
		// add to database
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
		return $id;
	}
	
	static function get($request, &$count, &$error)
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
					self::handle_file($file['Filepath']);
				}
				
				self::handle_file($request['file']);
				
				// delete the selected folder from the database
				$GLOBALS['database']->query(array('DELETE' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['file']) . '"'));
			}
			else
			{
				return array();
			}
		}
		
		return db_file::get($request, $count, $error, get_class());
	}
	
	static function cleanup()
	{
		// call default cleanup function
		db_file::cleanup(get_class());
	}
}

?>
