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


function get_info_files($file)
{
	$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
	
	$fileinfo = array();
	$fileinfo['Filepath'] = str_replace('\\', '/', $file);
	$fileinfo['Filename'] = basename($file);
	$fileinfo['Filesize'] = file_exists($file)?filesize($file):0;
	$fileinfo['Filemime'] = getMime($file);
	$fileinfo['Filedate'] = file_exists($file)?date("Y-m-d h:i:s", filemtime($file)):date("Y-m-d h:i:s", 0);
	$fileinfo['Filetype'] = getFileType($file);
	
	return $fileinfo;
}


function get_files($request, &$count)
{
	// retrieve files from database
}

function get_filesystem($request, &$count)
{
	$files = array();

	// do validation! for the fields we use
	$request['start'] = validate($request, 'start');
	$request['limit'] = validate($request, 'limit');
	$request['cat'] = validate($request, 'cat');

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

