<?php


/**
 * @defgroup register_handler Register Handler Functions
 * Functions that register handlers, basically the same as module registration functions.
 * Supports some extra fields, such as struct for describing the structure of the database, 
 * columns for a list of columns for templates to use, 'wrapper' if it is just a wrapper handler, 
 * 'streamer' if it includes a streamer class that handles file streams
 * @return A configuration array for the handler
 * @{
 */

/** 
 * Implementation of register_handler
 */
function register_files()
{
	return array(
		'name' => 'Files',
		'description' => 'Load information about existing files.',
		'database' => array(
			'id' => 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id)',
			'Filename' => 'TEXT',
			'Filemime' => 'TEXT',
			'Filesize' => 'BIGINT',
			'Filedate' => 'DATETIME',
			'Filetype' => 'TEXT',
			'Filepath' => 'TEXT'
		),
	);
}

/**
 * @}
 */

/**
 * Gets the columns from the specified handler
 */
function columns($handler)
{
	if(isset($GLOBALS['handlers'][$handler]['columns']))
	{
		return $GLOBALS['handlers'][$handler]['columns'];
	}
	elseif(isset($GLOBALS['handlers'][$handler]['database']))
	{
		return array_keys($GLOBALS['handlers'][$handler]['database']);
	}
	elseif(isset($GLOBALS['handlers'][$handler]['wrapper']))
	{
		return columns($GLOBALS['handlers'][$handler]['wrapper']);
	}
	else
	{
		return columns('files');
	}
}

function is_internal($handler)
{
	if(isset($GLOBALS['handlers'][$handler]['internal']))
	{
		return $GLOBALS['handlers'][$handler]['internal'];
	}
	elseif(isset($GLOBALS['handlers'][$handler]['wrapper']))
	{
		return is_internal($GLOBALS['handlers'][$handler]['wrapper']);
	}
	else
	{
		return false;
	}
}

/**
 * Check if the specified class is just a wrapper for some parent database
 * @param handler is the catagory or handler to check
 * @return true or false if the class is a wrapper
 */
function is_wrapper($handler)
{
	// fs_ handlers are never wrappers
	if(setting('database_enable') == false)
		return false;
	if($handler == 'files')
		return false;
	return isset($GLOBALS['handlers'][$handler]['wrapper']);
}

/**
 * @defgroup handles 'Is A Handler For' Functions
 * Functions that specify if a file handler handles a particular file.
 * @param file The file to test for handling
 * @return true if this handler is capable of handling the specified file, false if this handler does not handle the file
 * @{
 */


/**
 * Check if a handler handles a certain type of files
 * this is a useful call for templates to use because it provides short syntax
 * @param file The file to test if it is handled
 * @param handler The handler to check if it handles the specified file
 * @return true if the specified handler handles the specified file, false if the handler does not handle that file
 */
function handles($file, $handler)
{
	// check the module is enabled first
	if($handler != 'files' && dependency($handler) == false)
		return false;
	
	// check the handles_ function	
	if($handler == 'files' || $handler === true)
	{
		$file = str_replace('\\', '/', $file);
		if($handler !== true && setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
		if(
			is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || 
			(
				is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)) && $file[strlen($file)-1] != '/'
			)
		)
		{
			$filename = basename($file);
	
			// make sure it isn't a hidden file
			if(strlen($filename) > 0 && $filename[0] != '.')
				return true;
			else
				return false;
		} else {
			return false;
		}
	}
	// wrappers are never handlers because they don't have their own database
	elseif(is_wrapper($handler))
	{
		return false;
	}
	// check if there is a handle function
	elseif(function_exists('handles_' . $handler))
	{
		return call_user_func_array('handles_' . $handler, array($file));
	}
	// no handler specified, show debug error
	else
	{
		raise_error('Handles called with \'' . $handler . '\' but no \'handles_\' function exists!', E_DEBUG);
		
		return false;
	}
}

/**
 * @}
 */

/**
 * this is a common function for handlers, just abstracting the information about a file
 * @param file The file to get the info for
 * @return an associative array of information that can be inserted directly in to the database
 */
function get_files_info($file)
{
	$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
	
	$fileinfo = array();
	$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $file));
	$fileinfo['Filename'] = basename($file);
	$fileinfo['Filesize'] = filesize($file);
	$fileinfo['Filemime'] = getMime($file);
	$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
	$fileinfo['Filetype'] = getFileType($file);
	
	return $fileinfo;
}

/**
 * @defgroup handle Handle File Functions
 * Functions that read file information and store in the database
 * @param file the file to add to the database
 * @param force force checking if the file is in the database and add it
 * @return The ID for the file added to the database, or false if nothing was done or it isn't handled
 * @{
 */
 
/**
 * Implementation of handle
 */
function add($file, $force = false, $handler = 'files')
{
	$file = str_replace('\\', '/', $file);
	
	// if there is an add_handler function specified use that instead
	if(function_exists('add_' . $handler))
		call_user_func_array('add_' . $handler, array($file, $force));
	
	// return false if there is no info function
	if(!function_exists('get_' . $handler . '_info'))
	{
		raise_error('No get info function or add function exists for ' . $handler . '!', E_DEBUG);
		
		return false;
	}
	
	// files always qualify, we are going to log every single one!
	if(!handles($file, $handler))
		return false;
	
	// check if it is in the database
	$db_file = $GLOBALS['database']->query(array(
			'SELECT' => $handler,
			'COLUMNS' => ($handler == 'files')?array('id', 'Filedate'):'id',
			'WHERE' => 'Filepath = "' . addslashes($file) . '"',
			'LIMIT' => 1
		)
	, false);
	
	if( count($db_file) == 0 )
	{
		// get file information
		$fileinfo = call_user_func_array('get_' . $handler . '_info', array($file));
		
		// always add to file database
		raise_error('Adding ' . $handler . ': ' . $file, E_DEBUG);
		
		// add to database
		return $GLOBALS['database']->query(array('INSERT' => $handler, 'VALUES' => $fileinfo), false);
	}
	// not dependent on force because it checks for modification
	elseif($handler == 'files')
	{
		// get file information
		$fileinfo = get_files_info($file);
		
		// update file if modified date has changed
		if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
		{
			raise_error('Modifying file: ' . $file, E_DEBUG);
			
			// update database
			return $GLOBALS['database']->query(array('UPDATE' => 'files', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_file[0]['id']), false);
		}
		else
		{
			raise_error('Skipping file: ' . $file, E_DEBUG);
		}
		
	}
	elseif($force == true)
	{
		// get file information
		$fileinfo = call_user_func_array('get_' . $handler . '_info', array($file));
		
		raise_error('Modifying ' . $handler . ': ' . $file, E_DEBUG);
		
		// update database
		return $GLOBALS['database']->query(array('UPDATE' => $handler, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_file[0]['id']), false);
	}
	else
	{
		raise_error('Skipping ' . $handler . ': ' . $file, E_DEBUG);
	}
		
	return false;
}

/**
 * @}
 */

/**
 * @defgroup output_handler Output Handler Functions
 * Functions that output the provided file
 * @param file the file to output
 * @return a stream handle for use with fread, or false if there was an error
 * @{
 */
 
/**
 * Output a handler file stream
 */
function output_handler($file, $handler)
{
	if(function_exists('output_' . $handler))
		return call_user_func_array('output_' . $handler, array($file, $handler));
	elseif(is_wrapper($handler) && function_exists('output_' . $GLOBALS['handlers'][$handler]['wrapper']))
		return call_user_func_array('output_' . $GLOBALS['handlers'][$handler]['wrapper'], array($file, $handler));
	else
		return output_files($file, $handler);
}
 
/**
 * Implementation of output_handler
 * no headers is used to prevent changing the headers, if it is called by a module it may just need the stream and no header changes
 */
function output_files($file, $handler)
{
	$file = str_replace('\\', '/', $file);
	
	if(setting('admin_alias_enable') == true)
		$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	// load file from file system
	if(!setting('database_enable') && setting_installed())
	{
		// replace path
		$file = str_replace('\\', '/', $file);
		
		// check to make sure file is valid
		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
		{
			if($fp = @fopen($file, 'rb'))
			{
				return $fp;
			}
		}
	}
	// check to make sure file is valid
	elseif(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
	{
		$files = $GLOBALS['database']->query(array(
			'SELECT' => $handler,
			'WHERE' => 'Filepath = "' . addslashes($file) . '"',
			'LIMIT' => 1
		), true);
		if(count($files) > 0)
		{				
			if($fp = @fopen($file, 'rb'))
			{
				return $fp;
			}
		}
	}
	return false;
}

/**
 * @}
 */

/**
 * @defgroup get_handler Handler 'Get' Functions
 * Functions that return a list of files for the specific handler
 * @param request the request information for the list of files to get from the file database
 * @param count inserts the total number of files that meet the requested criteria in the database
 * @param handler a utility parameter for other handlers to use the default file get() functionality but acting on their own columns() and DATABASE
 * @return an indexed array of files from the database
 * @{
 */

/**----------------------- Magic, do not touch -----------------------
 * - the mysql can be left null to get the files from a directory, in which case a directory must be specified
 * - if the mysql is provided, then the file listings will be loaded from the database
 * - this is a very generalized handler to provide a template for overriding, or for other handlers to modify the $request and pass to this one
 *   - other handlers are responsible for any validation of input that is not listed here, like making sure files exist on the filesystem
 */
function get_files($request, &$count, $handler_or_internal)
{
	// for security, check the handler imediately
	if(is_bool($handler_or_internal))
	{
		// set the internal handler
		$internal = $handler_or_internal;
		$handler = 'files';
	}
	else
	{
		// internal use defaults to false, set handler to input variable
		$internal = false;
		$handler = $handler_or_internal;
	}
	
	// if the handler is set, call that instead
	if($handler != 'files' && function_exists('get_' . $handler))
		return call_user_func_array('get_' . $handler, array($request, $count));
	elseif(is_wrapper($handler))
		return get_files($GLOBALS['handlers'][$handler]['wrapper']);
	elseif($handler != 'files')
	{
		raise_error('get_files() called with handler \'' . $handler . '\' but no get_ handler function exists! Defaulting to files', E_DEBUG);
		
		// set the cat in the request to the provided handler
		$request['cat'] = validate(array('cat' => $handler), 'cat');
	}
	
	// if using the database and this isn't an internal request
	if(dependency('database') && !$internal)
	{
		return _get_database_files($request, $count, $handler);
	}
	// if NOT using the database or it IS and internal request
	elseif(!setting('database_enable') || $internal == true)
	{
		return _get_local_files($request, $count, $handler);
	}
	// if USING the database and NOT and internal request
	else
	{
		// there is probably just something wrong with some code, report it and exit
		raise_error('get_files() called without any acceptable intention!', E_DEBUG);
		
		return array();
	}
}

/** 
 * Implementation of get_handler
 * the mysql can be left null to get the files from a directory, in which case a directory must be specified
 * if the mysql is provided, then the file listings will be loaded from the database
 * don't use $internals = true
 * @ingroup get_handler
 */
function _get_local_files($request, &$count, $handler)
{
	$files = array();

	// do validation! for the fields we use
	$request['start'] = validate($request, 'start');
	$request['limit'] = validate($request, 'limit');

	if(isset($request['selected']) && count($request['selected']) > 0 )
	{
		$request['selected'] = validate($request, 'selected');
		foreach($request['selected'] as $i => $id)
		{
			$file = str_replace('\\', '/', @pack('H*', $id));
			if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
			{
				if(call_user_func($handler . '::handles', $file))
				{
					$info = call_user_func($handler . '::getInfo', $file);
					$info['id'] = bin2hex($request['file']);
					$info['Filepath'] = stripslashes($info['Filepath']);
					
					// make some modifications
					if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= '/';
					$files[] = $info;
				}
			}
		}
	}
	
	if(isset($request['file']))
	{
		$request['cat'] = validate($request, 'cat');
		$request['file'] = str_replace('\\', '/', $request['file']);
		if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $request['file'])))
		{
			if(handles($request['file'], $request['cat']))
			{
				if(function_exists('get_' . $request['cat'] . '_info'))
					$info = call_user_func('get_' . $request['cat'] . '_info', $request['file']);
				
				$info['id'] = bin2hex($request['file']);
				$info['Filepath'] = stripslashes($info['Filepath']);
			}
			else{ raise_error('Invalid file!', E_USER); }
		}
		else{ raise_error('File does not exist!', E_USER); }
	}
	else
	{

		// set a directory if one isn't set already
		if(!isset($request['dir']))
			$request['dir'] = realpath('/');
		$request['dir'] = str_replace('\\', '/', $request['dir']);
			
		// check to make sure is it a valid directory before continuing
		if (is_dir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])) && is_readable(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])))
		{
			// scandir - read in a list of the directory content
			$tmp_files = scandir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir']));
			$count = count($tmp_files);
			
			// do some filtering of files
			for($j = 0; $j < $count; $j++)
			{
				// parse out all the files that this handler doesn't handle, just like a filter
				//  but only if we are not called by internals
				if(!handles($request['dir'] . $tmp_files[$j], true))
					unset($tmp_files[$j]);
				// if dirs only, then unset all the files	
				elseif(
					isset($request['dirs_only']) && $request['dirs_only'] == true &&
					!is_dir($request['dir'] . $tmp_files[$j])
				)
					unset($tmp_files[$j]);
				// if match is set, use that to parse files
				elseif(isset($request['match']) && preg_match($request['match'], $tmp_files[$j]) == 0)
					unset($tmp_files[$j]);
				// if it is not unset
				else
				{
					// if match is set and depth is not equal to zero then get files recursively
					if(isset($request['match']) && is_dir($request['dir'] . $tmp_files[$j]) &&
						isset($request['depth']) && is_numeric($request['depth']) && $request['depth'] > 0)
						$tmp_files = array_merge($tmp_files, _get_local_files(array('dir' => $request['dir'] . $tmp_files[$j], 'depth' => $request['depth'] - 1) + $request, $tmp_count, 'files'));
				}
			}

			// get the values again, this will reset all the indices
			$tmp_files = array_values($tmp_files);
			
			// set the count to the total length of the file list
			$count = count($tmp_files);
			
			// start the information getting and combining of file info
			for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
			{
				// get the information from the handler for 1 file
				$info = call_user_func('get_files_info', $request['dir'] . $tmp_files[$i]);
				$info['id'] = bin2hex($request['dir'] . $tmp_files[$i]);
				$info['Filepath'] = stripslashes($info['Filepath']);
				
				// make some modifications
				if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= '/';
				
				// set the informations in the total list of files
				$files[] = $info;
			}
		}
		else{ raise_error('Directory does not exist!', E_USER); }
	}
		
	return $files;
}

function _get_database_files($request, &$count, $handler)
{
	// handle the request using default functionality
	$files = array();

	// set up initial props
	$props = array();
	$request['cat'] = validate($request, 'cat');

//---------------------------------------- Selection ----------------------------------------\\
	
	// loop through each module and call the method
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(function_exists('alter_query_' . $module))
		{
			$result = call_user_func_array('alter_query_' . $module, array($request, &$props));

			if(isset($result) && $result == false)
				return array();
		}
	}
		
	/*
	$props = alter_query_core($request, $props);

	$props = alter_query_select($request, $props);
	if($props == false)
		return array();

	$props = alter_query_file($request, $props);
	if($props == false)
		return array();
	
	$props = alter_query_search($request, $props);
	*/
	
//---------------------------------------- Query ----------------------------------------\\
	// finally start processing query
	$props['SELECT'] = $request['cat'];
	if(isset($props['GROUP'])) $props['COLUMNS'] = ',count(*)' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
	$props['COLUMNS'] = '*' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
	// get directory from database
	$files = $GLOBALS['database']->query($props, true);
	
	// return here if the query did not work
	if($files === false)
		return array();

	// now make some changes
	foreach($files as $index => $file)
	{
		// do alias replacement on every file path
		if(setting('admin_alias_enable') == true)
		{
			if(isset($file['Filepath']))
				$files[$index]['Filepath'] = preg_replace($GLOBALS['paths_regexp'], $GLOBALS['alias'], $file['Filepath']);
			$alias_flipped = array_flip($GLOBALS['alias']);
			// check if the replaced path was the entire alias path
			// in this case we want to replace the filename with the alias name
			if(isset($file['Filepath']) && isset($alias_flipped[$file['Filepath']]))
			{
				$index = $alias_flipped[$file['Filepath']];
				$files[$index]['Filename'] = substr($GLOBALS['alias'][$index], 1, strlen($GLOBALS['alias'][$index]) - 2);
			}
		}
	}
	
//---------------------------------------- Get Count ----------------------------------------\\
	// only get count if the query is not limited by the limit field
	//  get count if limit is not set, which is should always be because of validate()
	//  get count if it is greater than or equal to the limit, even though it will always be equal to or less then limit
	//  if it is less, only get count if start is set
	if(!isset($request['limit']) || count($files) >= $request['limit'] || (isset($request['start']) && $request['start'] > 0))
	{
		// this is how we get the count of all the items
		//  unset the limit to count it
		unset($props['LIMIT']);
		unset($props['ORDER']);
		$props['COLUMNS'] = '*';
		
		// if where is not set then there is no reason to count the entire database
		if(!isset($props['WHERE']) && !isset($props['GROUP']))
		{
			$props = array('SELECT' => $request['cat']);
		}
		else
		{
			// count the last query
			$props = array('SELECT' => '(' . database::statement_builder($props, true) . ') AS db_to_count');
		}
		$props['COLUMNS'] = 'count(*)';
		
		$result = $GLOBALS['database']->query($props, false);
		
		$count = intval($result[0]['count(*)']);
	}
	// set the count to whatever the number of files is
	else
	{
		$count = count($files);
	}
		
	return $files;
}

/**
 * @}
 */

/**
 * @defgroup remove_handler Handler 'Remove' Functions
 * Functions that remove files from the database for a specific handler
 * @param file the file or folder to remove from the database
 * @param handler a utility parameter to allow handlers to use the default functionality
 * @{
 */
	
/**
 * Implementation of remove_handler
 */
function remove($file, $handler = NULL)
{
	if( $handler == NULL )
		$handler = 'files';
	
	if($handler != 'files' && function_exists('remove_' . $handler))
		return call_user_func_array('remove_' . $handler, array($file));
	
	$file = str_replace('\\', '/', $file);
	
	// remove files with inside paths like directories
	if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
	else $file_dir = $file;
	
	raise_error('Removing ' . $GLOBALS['handlers'][$handler]['name'] . ': ' . $file, E_DEBUG);

	// remove file(s) from database
	$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'), false);	

	// delete ids
	remove_ids($file, $handler);
}

/**
 * @}
 */


/**
 * @defgroup cleanup_handler Handler 'Cleanup' Functions
 * Functions that cleanup files in the database
 * @param handler the utility parameter to allow handler to use the default file functionality but acting on their own DATABASE
 * @{
 */
	
/**
 * Implementation of cleanup_handler
 */
function cleanup($handler = 'files')
{
	// if there is a cleanup_handler function use that instead
	if(function_exists('cleanup_' . $handler))
		call_user_func_array('cleanup_' . $handler, array());

	/*
	// first clear all the items that are no longer in the watch list
	// since the watch is resolved all the items in watch have to start with the watched path
	$watched_where = '';
	foreach($GLOBALS['watched'] as $i => $watch)
	{
		$tmp_watch = str_replace('\\', '/', $watch['Filepath']);
		// add the files that begin with a path from a watch directory
		$watched_where .= ' LEFT(Filepath, ' . strlen($tmp_watch) . ') != "' .  addslashes($tmp_watch) . '" AND';
	}
	// remove last AND
	$watched_where = substr($watched_where, 0, strlen($watched_where)-3);
	
	// but keep the ones leading up to watched directories
	// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
	$watched_to_where = '';
	$directories = array();
	for($i = 0; $i < count($GLOBALS['watched']); $i++)
	{
		$folders = split('/', $GLOBALS['watched'][$i]['Filepath']);
		$curr_dir = (realpath('/') == '/')?'/':'';
		// don't add the watch directory here because it is already added by the previous loop!
		$length = count($folders);
		unset($folders[$length-1]); // remove the blank at the end
		unset($folders[$length-2]); // remove the last folder which is the watch
		$between = false; // directory must be between an aliased path and a watched path
		// add the directories leading up to the watch
		for($j = 0; $j < count($folders); $j++)
		{
			if($folders[$j] != '')
			{
				$curr_dir .= $folders[$j] . '/';
				// if using aliases then only add the revert from the watch directory to the alias
				// ex. Watch = /home/share/Pictures/, Alias = /home/share/ => /Shared/
				//     only /home/share/ is added here
				if((!setting('admin_alias_enable') || in_array($curr_dir, $GLOBALS['paths']) !== false))
				{
					// this allows for us to make sure that at least the beginning 
					//   of the path is an aliased path
					$between = true;
					
					if(!in_array($curr_dir, $directories))
					{
						$directories[] = $curr_dir;
						// if the setting('admin_alias_enable') is true this will only add the folder
						//    if it is in the list of aliases
						$watched_to_where .= ' Filepath != "' . addslashes($curr_dir) . '" AND';
					}
				}
				// but make an exception for folders between an alias and the watch path
				elseif(setting('admin_alias_enable') && $between && !in_array($curr_dir, $directories))
				{
					$directories[] = $curr_dir;
					
					$watched_to_where .= ' Filepath != "' . addslashes($curr_dir) . '" AND';
				}
			}
		}
	}
	// remove last AND
	$watched_to_where = substr($watched_to_where, 0, strlen($watched_to_where)-3);
	
	$where_str = $watched_to_where . ' AND ' . $watched_where;
	
	// remove items that aren't in where directories
	$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => array($where_str)), true);
	
	if(count($GLOBALS['ignored']) > 0)
	{
		
		$ignored_where = '';
		// clean up items that are in the ignore list
		foreach($GLOBALS['ignored'] as $i => $ignore)
		{
			$tmp_ignore = str_replace('\\', '/', $ignore['Filepath']);
			$ignored_where .= ' LEFT(Filepath, ' . strlen($tmp_ignore) . ') = "' . addslashes($tmp_ignore) . '" OR';
		}
		// remove last OR
		$ignored_where = substr($ignored_where, 0, strlen($ignored_where)-2);
		
		// remove items that are ignored
		$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => array($ignored_where)), true);
	}
	*/
	
	// remove any duplicates
	$files = $GLOBALS['database']->query(array(
			'SELECT' => $handler,
			'COLUMNS' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
			'GROUP' => 'Filepath',
			'HAVING' => 'num > 1'
		)
	, true);

	// if files is not an array something must have gone wrong
	if(!is_array($files))
	{
		raise_error('There was a problem when trying to remove duplicate files.', E_DEBUG);
	}
	else
	{
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			raise_error('Removing Duplicate ' . $GLOBALS['handlers'][$handler]['name'] . ': ' . $file['Filepath'], E_DEBUG);
			
			$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => 'id=' . $file['id']), false);
		}
	}
	
	raise_error('Cleanup: for ' . $GLOBALS['handlers'][$handler]['name'] . ' complete.', E_DEBUG);
	
}

/**
 * @}
 */
