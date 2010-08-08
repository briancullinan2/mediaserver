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
 * @}
 */

/**
 * Gets the columns from the specified handler
 */
function get_columns($handler)
{
	if(isset($GLOBALS['modules'][$handler]['database']))
	{
		return array_keys($GLOBALS['modules'][$handler]['database']);
	}
	elseif(isset($GLOBALS['modules'][$handler]['wrapper']))
	{
		return get_columns($GLOBALS['modules'][$handler]['wrapper']);
	}
	else
	{
		return array_keys($GLOBALS['modules']['files']['database']);
	}
}

/**
 * get all columns from every handlers
 * @return a list of all the columns combined from every handler installed
 */
function get_all_columns()
{
	$columns = array();
	foreach(get_handlers() as $handler => $config)
	{
		$columns = array_merge($columns, array_flip(get_columns($handler)));
	}
	
	$columns = array_keys($columns);

	return $columns;
}

function get_handlers($get_wrappers = true, $get_internals = false, $wrappers_only = false, $internals_only = false)
{
	// get all combos of handlers
	$handlers = array_filter(array_keys($GLOBALS['modules']), 'is_handler');
	$wrappers = array_filter($handlers, 'is_wrapper');
	$internals = array_filter($handlers, 'is_internal');
	
	// remove the handlers set to false
	if(!$get_internals && !$internals_only)
		$handlers = array_diff($handlers, $internals);
	if(!$get_wrappers && !$wrappers_only)
		$handlers = array_diff($handlers, $wrappers);
	
	// if they only want certain types of handlers
	if($wrappers_only && $internals_only)
		$handlers = array_intersect($handlers, array_merge($wrappers, $internals));
	elseif($wrappers_only)
		$handlers = array_intersect($handlers, $wrappers);
	elseif($internals_only)
		$handlers = array_intersect($handlers, $internals);
	
	// flip keys back and remerge module configs
	$handlers = array_flip($handlers);

	return array_intersect_key($GLOBALS['modules'], $handlers);
}

function is_internal($handler)
{
	if(!is_handler($handler))
		return;
		
	if(isset($GLOBALS['modules'][$handler]['internal']))
	{
		return $GLOBALS['modules'][$handler]['internal'];
	}
	elseif(isset($GLOBALS['modules'][$handler]['wrapper']))
	{
		return is_internal($GLOBALS['modules'][$handler]['wrapper']);
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
	if(!is_handler($handler))
		return false;
	
	// fs_ handlers are never wrappers
	if(setting('database_enable') == false)
		return false;
	if($handler == 'files')
		return false;
	return isset($GLOBALS['modules'][$handler]['wrapper']);
}

function is_handler($handler)
{
	if(isset($GLOBALS['modules'][$handler]['database']))
		return true;
	elseif(isset($GLOBALS['modules'][$handler]['wrapper']))
		return is_handler($GLOBALS['modules'][$handler]['wrapper']);
	else
		return false;
}


function handles_count($files)
{
	$modules = array();
	
	foreach(get_handlers() as $handler => $config)
	{
		$modules[$handler . '_count'] = array_sum(array_map('handles', $files, array_fill(0, count($files), $handler)));
	}
	
	return $modules;
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
	// if it isn't a handler at all, return here
	if($handler != 'files' && $handler !== true && !is_handler($handler))
		return false;
	
	// check the module is enabled first
	if($handler != 'files' && dependency($handler) == false)
		return false;
		
	// is an array is passed in it could be the entire $file array
	if(is_array($file) && isset($file['Filepath']))
		$file = $file['Filepath'];

	// if it is not a string there is nothing more we can do
	if(!is_string($file))
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
		return invoke_module('handles', $handler, array($file));
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
	$fileinfo['Filesize'] = file_exists($file)?filesize($file):0;
	$fileinfo['Filemime'] = getMime($file);
	$fileinfo['Filedate'] = file_exists($file)?date("Y-m-d h:i:s", filemtime($file)):date("Y-m-d h:i:s", 0);
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
		invoke_module('add', $handler, array($file, $force));
	
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
		return invoke_module('output', $handler, array($file, $handler));
	elseif(is_wrapper($handler) && function_exists('output_' . $GLOBALS['modules'][$handler]['wrapper']))
		return invoke_module('output', $GLOBALS['modules'][$handler]['wrapper'], array($file, $handler));
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

function merge_files_info($files, $default_cat)
{
	// loop through handlers and get extra file information
	foreach(get_handlers() as $handler => $config)
	{
		// do not get the same file information that is already available, skip default_cat
		if($handler != $default_cat && handles($file['Filepath'], $handler))
		{
		}
	}
}

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
		return invoke_module('get', $handler, $request, $count);
	elseif(is_wrapper($handler))
		return get_files($GLOBALS['modules'][$handler]['wrapper']);
	elseif($handler != 'files')
	{
		raise_error('get_files() called with handler \'' . $handler . '\' but no get_ handler function exists! Defaulting to files', E_DEBUG);
		
		// set the cat in the request to the provided handler
		$request['cat'] = validate(array('cat' => $handler), 'cat');
	}
	
	// if using the database and this isn't an internal request
	if(!$internal && dependency('database'))
	{
		return _get_database_files($request, $count, $handler);
	}
	// if NOT using the database or it IS and internal request
	elseif((!setting('database_enable') && setting_installed()) || $internal == true)
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
	$request['start'] = validate_start($request);
	$request['limit'] = validate_limit($request);
	$request['cat'] = validate_cat($request);

	if(isset($request['selected']) && count($request['selected']) > 0 )
	{
		$request['selected'] = validate($request, 'selected');
		foreach($request['selected'] as $i => $id)
		{
			$file = str_replace('\\', '/', @pack('H*', $id));
			if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
			{
				if(handles($file, $handler))
				{
					$info = call_user_func_array('get_' . $handler . '_info', array($file));
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
		if(!isset($request['dir'])) $request['dir'] = realpath('/');
		$request['dir'] = str_replace('\\', '/', $request['dir']);
			
		// check to make sure is it a valid directory before continuing
		if (is_dir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])) && is_readable(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])))
		{
			// scandir - read in a list of the directory content
			$tmp_files = scandir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir']));
			$count = count($tmp_files);

			// do some filtering of files
			$new_files = array();
			for($j = 0; $j < $count; $j++)
			{
				// parse out all the files that this handler doesn't handle, just like a filter
				//  but only if we are not called by internals
				if(
					!handles($request['dir'] . $tmp_files[$j], true) ||
					(
						// if dirs only, then unset all the files	
						isset($request['dirs_only']) && $request['dirs_only'] == true &&
						!is_dir($request['dir'] . $tmp_files[$j])
					)
				)
				{
					// just continue
					continue;
				}

				// if match is set and depth is not equal to zero then get files recursively
				if(isset($request['match']) && isset($request['depth']) &&
					is_dir($request['dir'] . $tmp_files[$j]) &&
					is_numeric($request['depth']) && $request['depth'] > 0
				)
				{
					$sub_files = _get_local_files(array(
							'dir' => $request['dir'] . $tmp_files[$j] . DIRECTORY_SEPARATOR,
							'depth' => $request['depth'] - 1
						) + $request, $tmp_count, 'files');

					$count = count($tmp_files);
					
					// check for directory on this level
					if(!isset($request['match']) || preg_match($request['match'], $tmp_files[$j]) > 0)
						$new_files[] = $tmp_files[$j];
						
					// add sub files
					$new_files = array_merge($new_files, $sub_files);
				}
				else
				{
					// if match is set, use that to parse files
					if(!isset($request['match']) || preg_match($request['match'], $tmp_files[$j]) > 0)
						$new_files[] = $tmp_files[$j];
				}
			}

			// set the count to the total length of the file list
			$count = count($new_files);
			
			// start the information getting and combining of file info
			for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
			{
				// skip the file if the file information is already loaded
				if(is_array($new_files[$i]) && isset($new_files[$i]['Filepath']))
				{
					$files[] = $new_files[$i];
					continue;
				}
				
				// get the information from the handler for 1 file
				$info = call_user_func_array('get_' . $request['cat'] . '_info', array($request['dir'] . $new_files[$i]));
				$info['id'] = bin2hex($request['dir'] . $new_files[$i]);
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
	foreach(get_modules() as $module => $config)
	{
		if(function_exists('alter_query_' . $module))
		{
			$result = invoke_module('alter_query', $module, array($request, &$props));
			
			if(isset($result) && $result == false)
				return array();
		}
	}
	
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

	// now make some changes:
	
	// do alias replacement on every file path
	if(setting('admin_alias_enable') == true)
	{
		foreach($files as $index => $file)
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
	//  get count if limit is not set, which is should never be because of validate()
	//  get count if it is greater than or equal to the limit, even though it will always be equal to or less then limit
	//  if it is less, only get count if start is set
	if(!isset($request['limit']) || count($files) >= $request['limit'] || 
		(isset($request['start']) && $request['start'] > 0))
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
	
	if($handler != 'files')
		module_invoke('remove', $handler, $file);
	
	$file = str_replace('\\', '/', $file);
	
	// remove files with inside paths like directories
	if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
	else $file_dir = $file;
	
	raise_error('Removing ' . $GLOBALS['modules'][$handler]['name'] . ': ' . $file, E_DEBUG);

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
	module_invoke('cleanup', $handler);

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
			raise_error('Removing Duplicate ' . $GLOBALS['modules'][$handler]['name'] . ': ' . $file['Filepath'], E_DEBUG);
			
			$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => 'id=' . $file['id']), false);
		}
	}
	
	raise_error('Cleanup: for ' . $GLOBALS['modules'][$handler]['name'] . ' complete.', E_DEBUG);
	
}

/**
 * @}
 */
