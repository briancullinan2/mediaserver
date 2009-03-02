<?php

// thing to consider:
// recognize category because that will determine what the id is refering to
// if the type can be handled by a browser then output it, otherwise disposition it

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
if(USE_DATABASE) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $database = NULL;

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

if(isset($_REQUEST['id']))
{
	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($database, array('id' => $_REQUEST['id']), &$count, &$error));

	if($error == '')
	{
		if(count($files) > 0)
		{
			// set up the output stream
			$op = fopen('php://output', 'wb');
			
			// get the input stream
			$fp = call_user_func_array($_REQUEST['cat'] . '::out', array($database, $files[0]['Filepath']));

			//-------------------- THIS IS ALL RANAGES STUFF --------------------
			
			// maybe they already defined the size in the header using the module, so check it
			if(!isset($files[0]['Filesize']))
			{
				$headers = headers_list();
				foreach($headers as $i => $header)
				{
					if(substr($header, 0, 15) == 'Content-Length:')
					{
						$files[0]['Filesize'] = substr($header, 16);
						if(!is_numeric($files[0]['Filesize']) || $files[0]['Filesize'] == 0) // problem getting the value?
							unset($files[0]['Filesize']);
						break;
					}
				}
			}
			
			// range can only be used when the filesize is known
			if(!isset($files[0]['Filesize']))
			{
				// try and get filesize from other handlers
				foreach($GLOBALS['modules'] as $i => $module)
				{
					if($module != $_REQUEST['cat'] && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
					{
						$return = call_user_func_array($module . '::get', array($database, array('file' => $files[0]['Filepath']), &$tmp_count, &$tmp_error));
						if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
					}
				}
			}
			
			// if the filesize is still not known, just output the stream without any fancy stuff
			if(isset($files[0]['Filesize']))
			{
				
				// close session now so they can keep using the website
				if(isset($_SESSION)) session_write_close();
				
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
			
			if(is_resource($fp) && is_resource($op))
			{
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
			else
			{ print 'Cannot open file!'; }
		}
		else
		{ print 'File not found!'; }
	}
	else
	{
		print $error;
	}
}
else
{
	print 'No Id';
}

?>