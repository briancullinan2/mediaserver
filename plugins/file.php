<?php

// things to consider:
// recognize category because that will determine what the id is refering to
// if the type can be handled by a browser then output it, otherwise disposition it

function register_file()
{
	return array(
		'name' => 'File Output',
		'description' => 'Allow users to download files from the database, this module supports HTTP-Ranges.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true,
		'alter query' => array('dir', 'file')
	);
}

function validate_filename($request)
{
	// just return the same, this is only used for pretty dirs and compatibility
	if(isset($request['filename']))
		return $request['filename'];
}

function validate_dir($request)
{
	// if this is not validated completely it is OK because it will be fixed in the db_file module when it is looked up
	//   this shouldn't cause any security risks
	if(isset($request['dir']))
	{
		$request['cat'] = validate_cat($request);
		if(USE_ALIAS == true)
			$tmp = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
			// this check the input 'dir' for actual files
		if(is_dir(realpath($tmp)) || 
			// this check the 'dir' for directories inside archives and disk images
			call_user_func_array($request['cat'] . '::handles', array($request['dir'])) == true ||
			// this check the dir for wrappers, wrappers can handle their own dir
			is_wrapper($request['cat']))
			return $request['dir'];
		else
			PEAR::raiseError('Directory does not exist!', E_USER);
	}
}

function validate_file($request)
{
	// if this is not validated completely it is OK because it will be fixed in the db_file module when it is looked up
	//   this shouldn't cause any security risks
	if(isset($request['file']))
	{
		$request['cat'] = validate_cat($request);
		if(USE_ALIAS == true)
			$tmp = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
		if(is_file(realpath($tmp)) || call_user_func_array($request['cat'] . '::handles', array($request['file'])) == true)
			return $request['file'];
		else
			PEAR::raiseError('File does not exist!', E_USER);
	}
}

function alter_query_file($request, $props)
{
//---------------------------------------- Directory ----------------------------------------\\
	// add dir filter to where
	if(isset($request['dir']))
	{
		$columns = call_user_func($request['cat'] . '::columns');
				
		if($request['dir'] == '') $request['dir'] = '/';
		
		// this is necissary for dealing with windows and cross platform queries coming from templates
		//  yes: the template should probably handle this by itself, but this is convenient and easy
		//   it is purely for making all the paths look prettier
		if($request['dir'][0] == '/') $request['dir'] = realpath('/') . substr($request['dir'], 1);
	
		// replace separator
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		
		// replace aliased path with actual path
		if(USE_ALIAS == true)
			$request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
			
		// maybe the dir is not loaded yet, this part is costly but it is a good way to do it
		if(RECURSIVE_GET && db_watch_list::handles($request['dir']))
		{
			db_watch_list::scan_dir($request['dir']);
		}
		
		// make sure file exists if we are using the file module
		if($request['cat'] != 'db_file' || is_dir(realpath($request['dir'])) !== false)
		{
		
			// make sure directory is in the database
			$dirs = $GLOBALS['database']->query(array('SELECT' => constant($request['cat'] . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"', 'LIMIT' => 1), true);
			
			// check the file database, some modules use their own database to store special paths,
			//  while other modules only store files and no directories, but these should still be searchable paths
			//  in which case the module is responsible for validation of it's own paths
			if(count($dirs) == 0)
				$dirs = $GLOBALS['database']->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"', 'LIMIT' => 1), true);
				
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
					
					// put folders at top if the module supports a filetype
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
				PEAR::raiseError('Directory does not exist!', E_USER);
				// don't ever continue after this error
				return array();
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
		if(USE_ALIAS == true)
			$request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
		
		// if the id is available then use that instead
		if(isset($request[constant($request['cat'] . '::DATABASE') . '_id']) && $request[constant($request['cat'] . '::DATABASE') . '_id'] != 0)
		{
			if(!isset($props['WHERE'])) $props['WHERE'] = '';
			elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
			
			// add single id to where
			$props['WHERE'] .= ' id = ' . $request[constant($request['cat'] . '::DATABASE') . '_id'];					
		}
		else
		{
			// make sure file exists if we are using the file module
			if($request['cat'] != 'db_file' || file_exists(realpath($request['file'])) !== false)
			{					
				if(!isset($props['WHERE'])) $props['WHERE'] = '';
				elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
				
				// add file to where
				$props['WHERE'] .= ' Filepath = "' . addslashes($request['file']) . '"';
			}
			else
			{
				PEAR::raiseError('File does not exist!', E_USER);
				return array();
			}
		}
		
		// these variables are no longer nessesary
		$props['LIMIT'] = 1;
		unset($props['ORDER']);
		unset($props['GROUP']);
	}
	
	return $props;
}


function output_file($request)
{
	set_time_limit(0);

	// set up request variables
	$request['cat'] = validate_cat($request);
	$request['id'] = validate_id($request);
	
	if(!isset($request['id']))
	{
		PEAR::raiseError('You must select a file for download!', E_USER);
		theme();
		return;
	}

	// get the file path from the database
	$files = call_user_func_array($request['cat'] . '::get', array($request, &$count));
	
	if(count($files == 0))
	{
		PEAR::raiseError('File not found!', E_USER);
	}

	// the ids module will do the replacement of the ids
	$files = db_ids::get(array('cat' => $request['cat']), $tmp_count, $files);

	$tmp_request = array();
	$tmp_request['file'] = $files[0]['Filepath'];

	// merge with tmp_request to look up more information
	$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);

	// get info from other handlers
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $request['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
		{
			$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count));
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
	$fp = call_user_func_array($request['cat'] . '::out', array($files[0]['Filepath']));
	
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
		PEAR::raiseError('Cannot open file!', E_USER);
		theme();
		return;
	}

	// seek to start of missing part
	if(isset($seek_start))
		fseek($fp, $seek_start);
	
	// output file
	while (!feof($fp)) {
		fwrite($op, fread($fp, BUFFER_SIZE));
	}
	
	// close file handles and return succeeded
	fclose($fp);
}

