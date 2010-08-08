<?php

/** things to consider:
 * recognize category because that will determine what the id is refering to
 * if the type can be handled by a browser then output it, otherwise disposition it
 */

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts any file name
 */
function validate_filename($request)
{
	// just return the same, this is only used for pretty dirs and compatibility
	if(isset($request['filename']))
	{
		// remove any slashes that still exist in the file name
		if(strpos($request['filename'], '/') !== false)
			$request['filename'] = substr($request['filename'], -strrpos($request['filename'], '/') + 1);
		if(strpos($request['filename'], '\\') !== false)
			$request['filename'] = substr($request['filename'], -strrpos($request['filename'], '\\') + 1);
		
		// return the filename
		return generic_validate_filename($request, 'filename');
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, validate input is a directory inside an archive or disk image, a directory directly on the filesystem, or a directory handled by the selected wrapper handler
 */
function validate_dir($request)
{
	// if this is not validated completely it is OK because it will be fixed in the db_file handler when it is looked up
	//   this shouldn't cause any security risks
	if(isset($request['dir']))
	{
		// this is needed to make sure the directory is handled by something
		$request['cat'] = validate($request, 'cat');
		
		// replace directory with actual path
		if(setting('admin_alias_enable') == true && setting('database_enable') && setting_installed())
			$tmp = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
		else
			$tmp = $request['dir'];
			
		// add leading slash
		if(substr($request['dir'], -1) != '/' && substr($request['dir'], -1) != '\\')
			$request['dir'] .= '/';
			
		// this checks the input 'dir' is on the actual file system
		if(is_dir(realpath($tmp)) || 
			// this check the 'dir' for directories inside archives and disk images
			handles($request['dir'], $request['cat']) == true ||
			// this check the dir for wrappers, wrappers can handle their own dir
			is_wrapper($request['cat'])
		)
			return $request['dir'];
		else
			raise_error('Directory does not exist!', E_USER);
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts files on the actual file system, or files handled by a handler other than db_file/fs_file
 */
function validate_file($request)
{
	// if this is not validated completely it is OK because it will be fixed in the db_file handler when it is looked up
	//   this shouldn't cause any security risks
	if(isset($request['file']))
	{
		$request['cat'] = validate($request, 'cat');
		if(setting('admin_alias_enable') == true)
			$tmp = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
		if(is_file(realpath($tmp)) || handles($request['file'], $request['cat']) == true)
			return $request['file'];
		else
			raise_error('File does not exist!', E_USER);
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return true by default
 */
function validate_dirs_only($request)
{
	return generic_validate_boolean_true($request, 'dirs_only');
}

/**
 * Implementation of alter_query
 * Alters the query based on file and dir input variables
 * @ingroup alter_query
 */
function alter_query_file($request, &$props)
{
	// do not alter the query if selected is set
	$request['selected'] = validate($request, 'selected');
	if(isset($request['selected']) && count($request['selected']) > 0 ) return $props;
	
//---------------------------------------- Directory ----------------------------------------\\
	// add dir filter to where
	if(isset($request['dir']))
	{
		$columns = get_columns($request['cat']);
				
		if($request['dir'] == '') $request['dir'] = '/';
		
		// this is necissary for dealing with windows and cross platform queries coming from templates
		//  yes: the template should probably handle this by itself, but this is convenient and easy
		//   it is purely for making all the paths look prettier
		if($request['dir'][0] == '/') $request['dir'] = realpath('/') . substr($request['dir'], 1);

		// replace separator
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		
		// replace aliased path with actual path
		if(setting('admin_alias_enable') == true)
			$request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
			
		// maybe the dir is not loaded yet, this part is costly but it is a good way to do it
		if(setting('recursive_get') && handles($request['dir'], 'updates'))
		{
			$GLOBALS['tm_start'] = array_sum(explode(' ', microtime()));
			scan_dir($request['dir']);
		}
		
		// make sure file exists if we are using the file handler
		if($request['cat'] != 'files' || is_dir(realpath($request['dir'])) !== false)
		{
		
			// require_permit below so the user can't see files that don't belong to them
			
			// make sure directory is in the database
			$dirs = $GLOBALS['database']->query(array('SELECT' => $request['cat'], 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"', 'LIMIT' => 1), true);
			
			// check the file database, some handlers use their own database to store special paths,
			//  while other handlers only store files and no directories, but these should still be searchable paths
			//  in which case the handler is responsible for validation of it's own paths
			if(count($dirs) == 0)
				$dirs = $GLOBALS['database']->query(array('SELECT' => 'files', 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"', 'LIMIT' => 1), true);
			
			// top level directory / should always exist
			if($request['dir'] == realpath('/') || count($dirs) > 0)
			{
				if(!isset($props['WHERE'])) $props['WHERE'] = array();
			
				// if the includes is blank then only show files from current directory
				if(!isset($request['search']))
				{
					if(isset($request['dirs_only']))
						$props['WHERE'][] = 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND LOCATE("/", Filepath, ' . (strlen($request['dir'])+1) . ') = LENGTH(Filepath)';
					else
						$props['WHERE'][] = 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND (LOCATE("/", Filepath, ' . (strlen($request['dir'])+1) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($request['dir'])+1) . ') = LENGTH(Filepath)) AND Filepath != "' . addslashes($request['dir']) . '"';
					
					// put folders at top if the handler supports a filetype
					if(in_array('Filetype', $columns))
					{
						$props['ORDER'] = '(Filetype = "FOLDER") DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
					}
				}
				// show all results underneath directory
				else
				{
					if(isset($request['dirs_only']))
						$props['WHERE'][] = 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND RIGHT(Filepath, 1) = "/" AND Filepath != "' . addslashes($request['dir']) . '"';
					else
						$props['WHERE'][] = 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND Filepath != "' . addslashes($request['dir']) . '"';
				}
			}
			else
			{
				raise_error('Directory does not exist!', E_USER);
				// don't ever continue after this error
				return false;
			}
		}
	}
	
//---------------------------------------- File ----------------------------------------\\
	// add file filter to where - this is mostly for internal use
	if(isset($request['file']))
	{
		// replace separator
		$request['file'] = str_replace('\\', '/', $request['file']);
		
		// this is necissary for dealing with windows and cross platform queries coming from templates
		if($request['file'][0] == DIRECTORY_SEPARATOR) $request['file'] = realpath('/') . substr($request['file'], 1);
		
		// replace aliased path with actual path
		if(setting('admin_alias_enable') == true)
			$request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
			
		// if the id is available then use that instead
		if(isset($request[$request['cat'] . '_id']) && $request[$request['cat'] . '_id'] != 0)
		{
			if(!isset($props['WHERE'])) $props['WHERE'] = array();
			
			// add single id to where
			$props['WHERE'][] = 'id = ' . $request[$request['cat'] . '_id'] . ' OR Filepath = "' . addslashes($request['file']) . '"';					
		}
		else
		{
			// make sure file exists if we are using the file handler
			if($request['cat'] != 'files' || file_exists(realpath($request['file'])) !== false)
			{					
				if(!isset($props['WHERE'])) $props['WHERE'] = array();
				
				// add file to where
				$props['WHERE'][] = 'Filepath = "' . addslashes($request['file']) . '"';
			}
			else
			{
				raise_error('File does not exist!', E_USER);
				// don't ever continue after this error
				return false;
			}
		}
		
		// these variables are no longer nessesary
		$props['LIMIT'] = 1;
		unset($props['ORDER']);
		unset($props['GROUP']);
	}
}

/**
 * Implementation of always output
 * @ingroup always_output
 */
function file_variables($request)
{
	// some templates refer to the dir to determine their own location
	if(isset($request['dir'])) register_output_vars('dir', $request['dir']);
	
	// set filename, just a helper for templates to set content disposition
	if(isset($request['filename'])) register_output_vars('filename', $request['filename']);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_file($request)
{
	set_time_limit(0);

	// set up request variables
	$request['cat'] = validate($request, 'cat');
	$request['id'] = validate($request, 'id');
	
	if(!isset($request['id']))
	{
		raise_error('You must select a file for download!', E_USER);
		theme();
		return;
	}

	// get the file path from the database
	$files = get_files($request, $count, $request['cat']);
	
	if(count($files) == 0)
	{
		raise_error('File not found!', E_USER);
		theme();
		return;
	}

	$tmp_request = array();
	$tmp_request['file'] = $files[0]['Filepath'];

	// merge with tmp_request to look up more information
	$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);

	// get info from other handlers
	foreach(get_handlers() as $handler => $config)
	{
		if($handler != $request['cat'] && handles($files[0]['Filepath'], $handler))
		{
			$return = get_files($tmp_request, &$tmp_count, $handler);
			if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
		}
	}
	
	// set some general headers
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: ' . $files[0]['Filemime']);
	header('Content-Disposition: attachment; filename="' . $files[0]['Filename'] . '"');
	
	// set up the output stream
	$op = fopen('php://output', 'wb');
	
	// get the input stream
	$fp = output_handler($files[0]['Filepath'], $request['cat']);
	
	//-------------------- THIS IS ALL RANAGES STUFF --------------------
	
	// range can only be used when the filesize is known
	
	// if the filesize is still not known, just output the stream without any fancy stuff
	if(isset($files[0]['Filesize']))
	{				
		// check for range request
		if(isset($_SERVER['HTTP_RANGE']))
		{
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
	
			if ($size_unit == 'bytes')
			{
				// multiple ranges could be specified at the same time, but for simplicity only serve the first range
				// http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				if(strpos($range_orig, ',') !== false)
					list($range, $extra_ranges) = explode(',', $range_orig, 2);
				else
					$range = $range_orig;
			}
			else
			{
				$range = '-';
			}
		}
		else
		{
			$range = '-';
		}
		
		// figure out download piece from range (if set)
		list($seek_start, $seek_end) = explode('-', $range, 2);
	
		// set start and end based on range (if set), else set defaults
		// also check for invalid ranges.
		$seek_end = (empty($seek_end)) ? ($files[0]['Filesize'] - 1) : min(abs(intval($seek_end)),($files[0]['Filesize'] - 1));
		//$seek_end = $file['Filesize'] - 1;
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
		
		// Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($files[0]['Filesize'] - 1))
		{
			header('HTTP/1.1 206 Partial Content');
		}

		header('Accept-Ranges: bytes');
		header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $files[0]['Filesize']);
	
		//headers for IE Bugs (is this necessary?)
		//header("Cache-Control: cache, must-revalidate");  
		//header("Pragma: public");
	
		header('Content-Length: ' . ($seek_end - $seek_start + 1));
	}
	
	//-------------------- END RANAGES STUFF --------------------
	
	// close session now so they can keep using the website
	if(isset($_SESSION)) session_write_close();
	
	if(!is_resource($fp) || !is_resource($op))
	{
		raise_error('Cannot open file!', E_USER);
		theme();
		return;
	}

	// seek to start of missing part
	if(isset($seek_start))
		fseek($fp, $seek_start);
	
	// output file
	while (!feof($fp)) {
		fwrite($op, fread($fp, setting('buffer_size')));
	}
	
	// close file handles and return succeeded
	fclose($fp);
}

