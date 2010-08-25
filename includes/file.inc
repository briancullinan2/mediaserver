<?php


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

function handles_count($files)
{
	$modules = array();
	
	foreach(get_handlers() as $handler => $config)
	{
		if(count($files) > 0)
			$modules[$handler . '_count'] = array_sum(array_map('handles', $files, array_fill(0, count($files), $handler)));
		else
			$modules[$handler . '_count'] = 0;
	}
	
	return $modules;
}

function scan_directory($dir, $filter = 'files', $depth = 1)
{
	$files = array();

	// return a flat list of files
	if (is_dir($dir) && $handle = opendir($dir))
	{
		// add slash to end
		if(substr($dir, -strlen(DIRECTORY_SEPARATOR)) != DIRECTORY_SEPARATOR)
			$dir .= DIRECTORY_SEPARATOR;
		
		while (FALSE !== ($file = readdir($handle)))
		{
			// add slash to directories
			if(is_dir($dir . $file))
				$file .= DIRECTORY_SEPARATOR;
			
			// filter files
			if(is_dir($dir . $file) && handles($dir . $file, 'files') && $depth > 1)
			{
				$files = array_merge($files, scan_directory($dir . $file, $filter, $depth - 1));
			}
			elseif(function_exists($filter))
			{
				if(call_user_func_array($filter, array($dir . $file)))
					$files[] = $dir . $file;
			}
			elseif(is_handler($filter))
			{
				if(handles($dir . $file, $filter))
					$files[] = $dir . $file;
			}
			elseif(handles($dir . $file, 'files'))
				$files[] = $dir . $file;
		}
	}
	
	sort($files);
	
	return array_unique($files);
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
	if(isset($GLOBALS['modules'][$handler]) && function_exists('handles_' . $handler))
		return true;
	elseif(isset($GLOBALS['modules'][$handler]['wrapper']))
		return is_handler($GLOBALS['modules'][$handler]['wrapper']);
	else
		return false;
}


function handles_files($file)
{
	return handles($file, 'files');
}
/**
 * Check if a handler handles a certain type of files
 * this is a useful call for templates to use because it provides short syntax
 * @param file The file to test if it is handled
 * @param handler The handler to check if it handles the specified file
 * @return true if the specified handler handles the specified file, false if the handler does not handle that file
 */
function handles($file, $handler = 'files')
{
	// if it isn't a handler at all, return here
	if($handler != 'files' && 
		// check the module is enabled first
		(!is_handler($handler) || dependency($handler) == false)
	)
		return false;
		
	// is an array is passed in it could be the entire $file array
	if(is_array($file) && isset($file['Filepath']))
		$file = $file['Filepath'];

	// if it is not a string there is nothing more we can do
	if(!is_string($file))
		return false;
		
	// check the handles_ function	
	if($handler == 'files')
	{
		// always use forward slash
		$file = str_replace('\\', '/', $file);
		
		// replace the file alias with actual path
		if($handler !== true && setting('admin_alias_enable')) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);

		// check if the given file is a directory or file
		if(
			is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || 
			(
				is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)) && $file[strlen($file)-1] != '/'
			)
		)
		{
			// make sure it isn't a hidden file
			if(strlen(basename($file)) > 0 && substr(basename($file), 0, 1) != '.' || (basename($file) == '' && realpath($file) == realpath('/')))
				return true;
		}
	}
	// wrappers are never handlers because they don't have their own database
	elseif(is_wrapper($handler))
		return false;
		
	// check if there is a handle function
	elseif(function_exists('handles_' . $handler))
		return invoke_module('handles', $handler, array($file));

	// no handler specified, show debug error
	else
		raise_error('Handles called with \'' . $handler . '\' but no \'handles_' . $handler . '()\' function exists!', E_DEBUG);
	
	return false;
}

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
