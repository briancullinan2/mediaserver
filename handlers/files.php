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
	if(setting_use_database() == false)
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
function handles_files($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	if((is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || (is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)) && $file[strlen($file)-1] != '/')) && !in_array($file, $GLOBALS['ignored']))
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

/**
 * @}
 */

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
function add_files($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	// files always qualify, we are going to log every single one!
	if(handles($file, 'db_file'))
	{
		
		// check if it is in the database
		$db_file = $GLOBALS['database']->query(array(
				'SELECT' => 'files',
				'COLUMNS' => array('id', 'Filedate'),
				'WHERE' => 'Filepath = "' . addslashes($file) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_file) == 0 )
		{
			// get file information
			$fileinfo = get_file_info($file);
			
			// always add to file database
			PEAR::raiseError('Adding file: ' . $file, E_DEBUG);
			
			// add to database
			return $GLOBALS['database']->query(array('INSERT' => 'files', 'VALUES' => $fileinfo), false);
		}
		// not dependent on force because it checks for modification
		else
		{
			// get file information
			$fileinfo = get_file_info($file);
			
			// update file if modified date has changed
			if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
			{
				PEAR::raiseError('Modifying file: ' . $file, E_DEBUG);
				
				// update database
				return $GLOBALS['database']->query(array('UPDATE' => 'files', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_file[0]['id']), false);
			}
			else
			{
				PEAR::raiseError('Skipping file: ' . $file, E_DEBUG);
			}
			
		}
		
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
		return call_user_func_array('output_' . $handler, array($file));
	elseif(is_wrapper($handler) && function_exists('output_' . $GLOBALS['handlers'][$handler]['wrapper']))
		return call_user_func_array('output_' . $GLOBALS['handlers'][$handler]['wrapper'], array($file));
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
	
	if(setting('use_alias') == true)
		$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	// check to make sure file is valid
	if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
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
function get_files($request, &$count, $handler)
{
	// if the handler is set, call that instead
	if($handler != 'files' && function_exists('get_' . $handler))
		return call_user_func_array('get_' . $handler, array($request, $count));
	elseif(is_wrapper($handler))
		return get_files($GLOBALS['handlers'][$handler]['wrapper']);
	elseif($handler != 'files')
	{
		PEAR::raiseError('get_files() called with handler \'' . $handler . '\' but no get_ handler function exists! Defaulting to db_file', E_DEBUG);
		
		// set the cat in the request to the provided handler
		$request['cat'] = validate_cat(array('cat' => $handler));
	}
		
	// handle the request using default functionality
	$files = array();

	// set up initial props
	$props = array();
	$request['cat'] = validate_cat($request);

//---------------------------------------- Selection ----------------------------------------\\
	
	$props = alter_query_core($request, $props);

	$props = alter_query_select($request, $props);
	if($props == false)
		return array();

	$props = alter_query_file($request, $props);
	if($props == false)
		return array();
	
	$props = alter_query_search($request, $props);
	
//---------------------------------------- Query ----------------------------------------\\
	// finally start processing query
	$props['SELECT'] = $request['cat'];
	if(isset($props['GROUP'])) $props['COLUMNS'] = ',count(*)' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
	$props['COLUMNS'] = '*' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
	// get directory from database
	$files = $GLOBALS['database']->query($props, true);
	
	if($files !== false)
	{
		// now make some changes
		foreach($files as $index => $file)
		{
			// do alias replacement on every file path
			if(setting('use_alias') == true)
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
	{
		$handler = 'db_file';
	}
	
	$file = str_replace('\\', '/', $file);
	
	// remove files with inside paths like directories
	if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
	else $file_dir = $file;
	
	PEAR::raiseError('Removing ' . $GLOBALS['handlers'][$handler]['name'] . ': ' . $file, E_DEBUG);

	// remove file(s) from database
	$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'), false);	

	// delete ids
	remove_db_ids($file, $handler);
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
function cleanup($handler = NULL)
{
	if( $handler == NULL )
	{
		$handler = 'db_file';
	}

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
				if((!setting('use_alias') || in_array($curr_dir, $GLOBALS['paths']) !== false))
				{
					// this allows for us to make sure that at least the beginning 
					//   of the path is an aliased path
					$between = true;
					
					if(!in_array($curr_dir, $directories))
					{
						$directories[] = $curr_dir;
						// if the setting('use_alias') is true this will only add the folder
						//    if it is in the list of aliases
						$watched_to_where .= ' Filepath != "' . addslashes($curr_dir) . '" AND';
					}
				}
				// but make an exception for folders between an alias and the watch path
				elseif(setting('use_alias') && $between && !in_array($curr_dir, $directories))
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
	$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => $where_str), false);
	
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
		$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => $ignored_where), false);
	}
	
	// remove any duplicates
	$files = $GLOBALS['database']->query(array(
			'SELECT' => $handler,
			'COLUMNS' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
			'GROUP' => 'Filepath',
			'HAVING' => 'num > 1'
		)
	, false);

	// remove first item from all duplicates
	foreach($files as $i => $file)
	{
		PEAR::raiseError('Removing Duplicate ' . $GLOBALS['handlers'][$handler]['name'] . ': ' . $file['Filepath'], E_DEBUG);
		
		$GLOBALS['database']->query(array('DELETE' => $handler, 'WHERE' => 'id=' . $file['id']), false);
	}
	
	PEAR::raiseError('Cleanup: for ' . $GLOBALS['handlers'][$handler]['name'] . ' complete.', E_DEBUG);
	
}

/**
 * @}
 */
