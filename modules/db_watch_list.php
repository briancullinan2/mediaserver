<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_audio.php';

// watch list handler
class db_watch_list extends db_watch
{
	const DATABASE = 'watch_list';
	
	const NAME = 'New and Changed Directories from Database';
	
	const INTERNAL = true;

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'Filepath' => 'TEXT'
		);
	}
	
	static function handles($dir, $file = NULL)
	{
		$dir = str_replace('\\', '/', $dir);
		if(USE_ALIAS == true) $dir = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $dir);

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
							'WHERE' => 'Filepath = "' . addslashes($dir) . '"',
							'LIMIT' => 1
						)
					, false);
					if(count($db_files) > 0)
					{
						$file = $db_files[0];
					}
				}
				
				// doesn't exist in files database, but is watched should definitely be scanned
				//   definitely scan it if the directory change time is different from the database
				if( !isset($file) || date("Y-m-d h:i:s", filemtime($dir)) != $file['Filedate'] )
				{
					return true;
				}
				else
				{
					// compare the count of files in the database to the file system
					$db_files = $GLOBALS['database']->query(array(
							'SELECT' => db_file::DATABASE,
							'COLUMNS' => array('count(*)'),
							'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
						)
					, false);
					
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
								PEAR::raiseError('Directory count inconsitency: too few files in database!', E_DEBUG);
								return true;
							}
						}
						closedir($dh);
					}

					// if count if less then number of directories in database
					if($count < $db_files[0]['count(*)'])
					{
						PEAR::raiseError('Directory count inconsitency: too many files in database!', E_DEBUG);
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
					'WHERE' => 'Filepath = "' . addslashes($dir) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($db_watch_list) == 0 )
			{
				// add directory to scan queue
				$id = self::add($dir);
				
			}
		}
		
		// check directories recursively
		if(self::is_watched($dir))
		{
			// search recursively
			return self::handle_dir($dir);
		}
		
		return true;
	}
	
	// scan for changed files
	static function scan_dir($dir)
	{
		PEAR::raiseError('Scanning directory: ' . $dir, E_DEBUG);
		
		// search all the files in the directory
		$files = fs_file::get(array('dir' => $dir, 'limit' => 32000), $count, true);
		
		// send new/changed files to other modules
		$paths = array();
		$paths[] = $dir;
		foreach($files as $i => $file)
		{
			self::handle_file($file['Filepath']);
				
			$paths[] = $file['Filepath'];
				
			// don't put too much load of the system
			usleep(1);
		}
		
		// search for files removed from filesystem
		$db_files = $GLOBALS['database']->query(array(
				'SELECT' => db_file::DATABASE,
				'COLUMNS' => array('Filepath'),
				'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
			)
		, false);
		
		$db_paths = array();
		foreach($db_files as $j => $file)
		{
			if(!in_array($file['Filepath'], $paths))
			{
				PEAR::raiseError('Removing: ' . $file['Filepath'], E_DEBUG);
				
				// remove file from each module
				foreach($GLOBALS['modules'] as $i => $module)
				{
					// do not remove ids because other modules may still use the id
					//  allow other modules to handle removing of ids
					if($module != 'db_ids')
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
			if(is_dir($path) && self::is_watched($dir))
			{
				self::add($path);
			}
		}
		
		return false;
	}
	
	// look for changed directories
	//   different from scan dir, which looks for changed and new files
	static function handle_dir($dir, $current = '')
	{
		// prevent recursion from symbolic links and add the resolved path to this list
		if(!isset($GLOBALS['scan_dirs']))
			$GLOBALS['scan_dirs'] = array();
			
		// get current if it is not already set
		if($current == '')
		{
			foreach($GLOBALS['watched'] as $i => $watch)
			{
				if(substr($dir, 0, strlen($watch['Filepath'])) == $watch['Filepath'])
				{
					$current = $watch['Filepath'];
					break;
				}
			}
		}
		
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $current)))
		{
			PEAR::raiseError('Looking for changes in: ' . $current, E_DEBUG);
		
			$files = fs_file::get(array('dir' => $current, 'limit' => 32000), $count, true);
			$has_resumed = false;
			// keep going until all files in directory have been read
			foreach($files as $i => $file)
			{
				if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file['Filepath'])) && !in_array(realpath($file['Filepath']), $GLOBALS['scan_dirs']))
				{
					$GLOBALS['scan_dirs'][] = realpath($file['Filepath']);
					
					// check to see if $dir is above the current directory
					if(substr($current, 0, strlen($dir)) != $dir && $has_resumed == false)
					{
						if(substr($dir, 0, strlen($file['Filepath'])) != $file['Filepath'])
							continue;
						PEAR::raiseError('Resuming looking for changes in: ' . $file['Filepath'], E_DEBUG);
						$has_resumed = true;
					}
					
					// check if execution time is too long
					$secs_total = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];
					
					if( $secs_total > DIRECTORY_SEEK_TIME )
					{
						// return the path to be saved in the state
						return $file['Filepath'];
					}
				
					// keep processing files
					$file['Filepath'] = str_replace('\\', '/', $file['Filepath']);
					
					$current_dir = true;
					if(self::handles($file['Filepath']))
					{
						$db_watch_list = $GLOBALS['database']->query(array(
								'SELECT' => self::DATABASE,
								'COLUMNS' => array('id'),
								'WHERE' => 'Filepath = "' . addslashes($file['Filepath']) . '"',
								'LIMIT' => 1
							)
						, false);
						
						$current_dir = self::handle_dir($dir, $file['Filepath']);
						
						if( count($db_watch_list) == 0 )
						{
							$id = self::add($file['Filepath']);
						}
					}
					
					if( $current_dir !== true || connection_status() != 0)
					{
						return $current_dir;
					}
					
					if($has_resumed == true)
						$dir = dirname($file['Filepath']) . '/';
				}
				
				// don't put too much load on the system
				usleep(1);
			}
		}
		
		// directory as been completed
		return true;
	}
	
	static function handle_file($file)
	{
		$ids = array();
		
		// since we are only dealing with files that actually exist
		$skipped = db_file::handle($file);
		
		//   modify ids if something was added
		$added = false;
		if($skipped !== false)
		{
			$added = true;
			$ids[db_file::DATABASE . '_id'] = $skipped;
		}
		
		// if the file is skipped the only pass it to other handlers for adding, not modifing
		//   if the file was modified or added the information could have changed, so the modules must modify it, if it is already added
		foreach($GLOBALS['modules'] as $i => $module)
		{
			// never pass it to fs_file, it is only used to internals in this case
			// db_file and db_ids are handled independently
			// skip db_watch and db_watch_list to prevent recursion
			if(constant($module . '::INTERNAL') == false && $module != 'db_file')
			{
				$result = call_user_func_array($module . '::handle', array($file, ($skipped !== false)));
				if($result !== false)
				{
					$added = true;
					$ids[constant($module . '::DATABASE') . '_id'] = $result;
				}
				elseif(!isset($ids[constant($module . '::DATABASE') . '_id']))
				{
					$ids[constant($module . '::DATABASE') . '_id'] = false;
				}
			}
		}

		// insert all the ids, force modifying only if something was added
		db_ids::handle($file, ($added == true), $ids);
	}
	
	static function add($file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		PEAR::raiseError('Queueing directory: ' . $file, E_DEBUG);
		
		// add to database
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
		
		return $id;
	}
	
	static function get($request, &$count)
	{
		if(isset($request['file']))
			return array();
		
		return db_file::get($request, $count, get_class());
	}
		
	static function remove($file)
	{
		parent::remove($file, get_class());
	}

	static function cleanup()
	{
		// call default cleanup function
		db_file::cleanup(get_class());
	}
}

?>
