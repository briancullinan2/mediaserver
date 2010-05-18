<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_filesystem()
{
	return array(
		'name' => 'Files',
		'description' => 'Read files from the filesystem',
		'columns' => array('id', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath'),
	);
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles($file, $handler)
{
	if($handler == 'filesystem')
	{
		$file = str_replace('\\', '/', $file);
	
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
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
		PEAR::raiseError('Handles called with \'' . $handler . '\' but no \'handles_\' function exists!', E_DEBUG);
		
		return false;
	}
}

/**
 * this is a common function for handlers, just abstracting the information about a file
 * @param file The file to get the info for
 * @return an associative array of information that can be inserted directly in to the database
 */
function get_filesystem_info($file)
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
 * Implementation of output_handler
 * @ingroup output_handler
 */
function output_filesystem($file)
{
	// double check to make sure the database is not in use
	// don't ever output files if the system is not installed
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
	
	return false;
}


/** 
 * Implementation of get_handler
 * the mysql can be left null to get the files from a directory, in which case a directory must be specified
 * if the mysql is provided, then the file listings will be loaded from the database
 * don't use $internals = true
 * @ingroup get_handler
 */
function get_filesystem($request, &$count)
{
	$files = array();

	// do validation! for the fields we use
	$request['start'] = validate_start($request);
	$request['limit'] = validate_limit($request);

	if(isset($request['selected']) && count($request['selected']) > 0 )
	{
		$request['selected'] = validate_selected($request);
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
		$request['cat'] = validate_cat($request);
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
			else{ PEAR::raiseError('Invalid file!', E_USER); }
		}
		else{ PEAR::raiseError('File does not exist!', E_USER); }
	}
	else
	{

		// set a directory if one isn't set already
		if(!isset($request['dir']))
			$request['dir'] = realpath('/');
		$request['dir'] = str_replace('\\', '/', $request['dir']);
			
		// check to make sure is it a valid directory before continuing
		if (is_dir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])))
		{
			// scandir - read in a list of the directory content
			$tmp_files = scandir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir']));
			$count = count($tmp_files);
			
			// parse out all the files that this handler doesn't handle, just like a filter
			//  but only if we are not called by internals
			for($j = 0; $j < $count; $j++)
				if(!handles($request['dir'] . $tmp_files[$j], 'filesystem') || (isset($request['dirs_only']) && $request['dirs_only'] == true && !is_dir($request['dir'] . $tmp_files[$j]))) unset($tmp_files[$j]);

			// get the values again, this will reset all the indices
			$tmp_files = array_values($tmp_files);
			
			// set the count to the total length of the file list
			$count = count($tmp_files);
			
			// start the information getting and combining of file info
			for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
			{
				// get the information from the handler for 1 file
				$info = call_user_func('get_filesystem_info', $request['dir'] . $tmp_files[$i]);
				$info['id'] = bin2hex($request['dir'] . $tmp_files[$i]);
				$info['Filepath'] = stripslashes($info['Filepath']);
				
				// make some modifications
				if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= '/';
				
				// set the informations in the total list of files
				$files[] = $info;
			}
			return $files;
		}
		else{ PEAR::raiseError('Directory does not exist!', E_USER); return false; }
	}
		
	return $files;
}


