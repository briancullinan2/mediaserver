<?php

function menu_updates()
{
	return array(
		'admin/updates' => array(
			'callback' => 'configure',
		)
	);
}

/**
 * Implementation of setup
 */
function setting_updates()
{
	$settings = array();
	
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 128; $i++)
	{
		$GLOBALS['setting_watched_' . $i] = create_function('$settings', 'return setting_watched($settings, \'' . $i . '\');');
		$settings[] = 'watched_' . $i;
	}
	
	return $settings;
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_updates($dir, $file = NULL)
{
	$dir = str_replace('\\', '/', $dir);
	if(setting('admin_alias_enable') == true) $dir = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $dir);

	if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
	{
		if(is_watched($dir))
		{
			if($file == NULL)
			{
				// changed directories or directories that don't exist in the database
				$db_files = $GLOBALS['database']->query(array(
						'SELECT' => 'files',
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
						'SELECT' => 'files',
						'COLUMNS' => array('count(*)'),
						'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
					)
				, false);
				
				// check for file count inconsistency but don't process anything
				$count = 1;
				if (is_readable(str_replace('/', DIRECTORY_SEPARATOR, $dir)) && $dh = opendir(str_replace('/', DIRECTORY_SEPARATOR, $dir)))
				{
					// count files
					while (($file = readdir($dh)) !== false)
					{
						if(handles($dir . $file, 'files'))
						{
							if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $dir . $file . '/')))
							{
								if(is_watched($dir . $file . '/'))
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
							raise_error('Directory count inconsitency: too few files in database!', E_DEBUG);
							return true;
						}
					}
					closedir($dh);
				}

				// if count if less then number of directories in database
				if($count < $db_files[0]['count(*)'])
				{
					raise_error('Directory count inconsitency: too many files in database!', E_DEBUG);
					return true;
				}
			}
		}
	}
	
	return false;
}

/**
 * Helper function
 */
function setting_watched($settings, $index)
{
	if(isset($settings['watched_' . $index]))
	{
		// add prefix
		if($settings['watched_' . $index][0] == '!')
			$prefix = '!';
		else
			$prefix = '^';
		
		// remove leading symbol
		if($settings['watched_' . $index][0] == '!' || $settings['watched_' . $index][0] == '^')
			$settings['watched_' . $index] = substr($settings['watched_' . $index], 1);
		
		// add trailing slash
		if(substr($settings['watched_' . $index], -1) != '/' && substr($settings['watched_' . $index], -1) != '\\')
			$settings['watched_' . $index] .= DIRECTORY_SEPARATOR;
		
		// confirm that it is a directory
		if(is_dir($settings['watched_' . $index]))
			return $prefix . $settings['watched_' . $index];
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_watches($settings)
{
	$watches = array();
	
	for($i = 0; $i < 128; $i++)
	{
		$watch = setting_watched($settings, $i);
		if(isset($watch) && substr($watch, 0, 1) == '^')
			$watches[] = get_files_info(substr($watch, 1));
	}
	$watches[] = get_files_info(setting('local_users'));
	
	return $watches;
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_ignores($settings)
{
	$ignores = array();
	
	for($i = 0; $i < 128; $i++)
	{
		$watch = setting_watched($settings, $i);
		if(isset($watch) && substr($watch, 0, 1) == '!')
			$ignores[] = get_files_info(substr($watch, 1));
	}
	
	return $ignores;
}

/** 
 * Checks if a directory has changed and should be added to the watch list
 */
function is_watched($dir)
{
	$is_ignored = false;
	$is_watched = false;
	foreach(setting('watches') as $i => $watch)
	{
		if(substr($dir, 0, strlen($watch['Filepath'])) == $watch['Filepath'])
		{
			$is_watched = true;
		}
	}
	foreach(setting('ignores') as $i => $ignore)
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

/** 
 * Implementation of handle
 * @ingroup handle
 */
function add_updates($dir)
{
	$dir = str_replace('\\', '/', $dir);
	
	if(handles($dir, 'updates'))
	{
		$update = $GLOBALS['database']->query(array(
				'SELECT' => 'admin_watch',
				'COLUMNS' => array('id'),
				'WHERE' => 'Filepath = "' . addslashes($dir) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($update) == 0 )
		{
			// add directory to scan queue
			$fileinfo = array();
			$fileinfo['Filepath'] = addslashes($dir);
		
			raise_error('Queueing directory: ' . $dir, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => 'updates', 'VALUES' => $fileinfo), false);
			
			return $id;
		}
	}
	
	// check directories recursively
	if(is_watched($dir))
	{
		// search recursively
		return handle_dir($dir);
	}
	
	return true;
}

/** 
 * Scan a directory for changes
 */
function scan_dir($dir)
{
	raise_error('Scanning directory: ' . $dir, E_DEBUG);
	
	// search all the files in the directory
	$files = get_files(array('dir' => $dir, 'limit' => 32000), $count, true);
	
	// send new/changed files to other handlers
	$paths = array();
	$paths[] = $dir;
	foreach($files as $i => $file)
	{
		handle_file($file['Filepath']);
			
		$paths[] = $file['Filepath'];
			
		// don't put too much load of the system
		usleep(1);
	}
	
	// search for files removed from filesystem
	$db_files = $GLOBALS['database']->query(array(
			'SELECT' => 'files',
			'COLUMNS' => array('Filepath'),
			'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'
		)
	, false);
	
	$db_paths = array();
	foreach($db_files as $j => $file)
	{
		if(!in_array($file['Filepath'], $paths) || is_watched($file['Filepath']) == false)
		{
			raise_error('Removing: ' . $file['Filepath'], E_DEBUG);
			
			// remove file from each handler
			foreach($GLOBALS['modules'] as $handler => $config)
			{
				// do not remove ids because other handlers may still use the id
				//  allow other handlers to handle removing of ids
				if($handler != 'ids' && is_handler($handler) && !is_internal($handler) && !is_wrapper($handler))
					remove($file['Filepath'], $handler);
			}
		}
		$db_paths[] = $file['Filepath'];
	}
	
	// add current directory to database
	handle_file($dir);
	
	// check for new files
	$paths = array_diff($paths, $db_paths);
	foreach($paths as $i => $path)
	{
		if(is_dir($path) && is_watched($dir))
		{
			add_updates($path);
		}
	}
	
	return false;
}

/** 
 * look for changed directories
 *   different from scan dir, which looks for changed and new files
 */
function handle_dir($dir, $current = '')
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
		raise_error('Looking for changes in: ' . $current, E_DEBUG);
	
		$files = get_files(array('dir' => $current, 'limit' => 32000), $count, true);
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
					raise_error('Resuming looking for changes in: ' . $file['Filepath'], E_DEBUG);
					$has_resumed = true;
				}
				
				// check if execution time is too long
				$secs_total = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];
				
				if( $secs_total > setting('dir_seek_time') )
				{
					// return the path to be saved in the state
					return $file['Filepath'];
				}
			
				// keep processing files
				$file['Filepath'] = str_replace('\\', '/', $file['Filepath']);
				
				$current_dir = true;
				if(handles($file['Filepath'], 'updates'))

				{
					$db_watch_list = $GLOBALS['database']->query(array(
							'SELECT' => 'admin_watch',
							'COLUMNS' => array('id'),
							'WHERE' => 'Filepath = "' . addslashes($file['Filepath']) . '"',
							'LIMIT' => 1
						)
					, false);
					
					$current_dir = handle_dir($dir, $file['Filepath']);
					
					if( count($db_watch_list) == 0 )
					{
						$id = add_updates($file['Filepath']);
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

/** 
 * Handle individual directories changed files
 */
function handle_file($file)
{
	$ids = array();

	// since we are only dealing with files that actually exist
	$skipped = add($file);
	
	//   modify ids if something was added
	$added = false;
	if($skipped !== false)
	{
		$added = true;
		$ids['files_id'] = $skipped;
	}
	
	// if the file is skipped the only pass it to other handlers for adding, not modifing
	//   if the file was modified or added the information could have changed, so the handlers must modify it, if it is already added
	foreach($GLOBALS['modules'] as $handler => $config)
	{
		// get the file information and add it to the database
		if($handler != 'files' && $handler != 'ids' && handles($file, $handler))
		{
			// call function to add to the database
			$result = add($file, ($skipped !== false), $handler);
			if($result !== false)
			{
				$added = true;
				$ids[$handler . '_id'] = $result;
			}
			elseif(!isset($ids[$handler . '_id']))
			{
				$ids[$handler . '_id'] = false;
			}
		}
	}

	// insert all the ids, force modifying only if something was added
	add_ids($file, ($added == true), $ids);
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_updates($request, &$count)
{
	// change the cat to the table we want to use
	$request['cat'] = validate(array('cat' => 'updates'), 'cat');
	
	if(isset($request['file']))
		return array();
	
	return get_files($request, $count, 'files');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return accepts any positive numeric index to remove
 */
function validate_wremove($request)
{
	return generic_validate_numeric_zero($request, 'wremove');
}

/**
 * Implementation of configure
 */
function configure_updates($settings, $request)
{
	$settings['watches'] = setting('watches');
	$settings['ignores'] = setting('ignores');
	
	$options = array();
	
	$watched_options = array();
	foreach($settings['watches'] as $i => $watch)
	{
		$watched_options[] = 'Watched: ' . $watch['Filepath'];
	}
	foreach($settings['ignores'] as $i => $watch)
	{
		$watched_options[] = 'Ignored: ' . $watch['Filepath'];
	}
	
	$options['manage'] = array(
		'name' => 'Manage Watches',
		'type' => 'fieldset',
		'options' => array(
			'wremove' => array(
				'name' => 'Remove Watch',
				'status' => '',
				'type' => 'set',
				'options' => array(
					'wremove[folder]' => array(
						'name' => 'Current Watched Directories',
						'type' => 'multiselect',
						'options' => $watched_options,
						'value' => '',
					),
					array(
						'value' => '<br />',
					),
					'wremove[remove]' => array(
						'type' => 'submit',
						'value' => 'Remove'
					),
				),
				'description' => array(
					'list' => array(
						'Watched directories are used to updating the database and determining what directories users have access too.',
						'Use this form to manage the watched directories.'
					)
				)
			),
			'waddpath' => array(
				'name' => 'Add Watched Directory',
				'status' => '',
				'type' => 'set',
				'options' => array(
					array(
						'name' => 'Select a Directory',
						'type' => 'theme',
						'value' => 'select_block',
					),
					array(
						'value' => '<br />',
					),
					'waddpath[folder]' => array(
						'type' => 'text',
						'value' => $request['dir'],
					),
					array(
						'value' => '<br />',
					),
					'waddpath[add]' => array(
						'type' => 'submit',
						'value' => 'Add',
					),
				),
				'description' => array(
					'list' => array(
						'Add a directory to the watch list.',
						'Using a ! infront of the directory will ignore the directory.'
					),
				),
			),
		),
	);
	
	// make select call for the file browser
	$files = get_files(array(
		'dir' => validate($request, 'dir'),
		'start' => validate($request, 'start'),
		'limit' => 32000,
		'dirs_only' => true,
	), &$total_count, true);
	
	register_output_vars('files', $files);
	
	return $options;
}

