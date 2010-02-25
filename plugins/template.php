<?php

// control outputting of template files
//  validate template variable

function register_template()
{
	return array(
		'name' => 'template',
		'description' => 'Display files from the templates directory. Allows for templating CSS and JS files.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('template'),
		'notemplate' => true
	);
}

function validate_tfile($request)
{
	if(isset($request['tfile']))
	{
		$request['template'] = validate_template($request);
		if(is_file(LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile']))
			return $request['tfile'];
	}
}


function validate_template($request, $session = '')
{
	if(!isset($request['template']) && $session != '')
		$request['template'] = $session;
		
	// check if it is a valid template specified
	if(isset($request['template']))
	{
		if(substr($request['template'], 0, 10) == 'templates/')
			$request['template'] = substr($request['template'], 10);
		if($request['template'][strlen($request['template'])-1] == '/')
			$request['template'] = substr($request['template'], 0, -1);
		if(in_array($request['template'], $GLOBALS['templates']))
		{
			return $request['template'];
		}
	}
	elseif(preg_match('/.*mobile.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		return 'mobile';
	}
	return basename(LOCAL_DEFAULT);
}

function session_template($request)
{
	return $request['template'];
}

function output_template($request)
{
	$request['template'] = validate_template($request);
	$request['tfile'] = validate_tfile($request);

	$file = LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile'];
	print_r(getMime($file));

	// set some general headers
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: ' . getMime($file));
	
	// set up the output stream
	$op = fopen('php://output', 'wb');
	
	// get the input stream
	$fp = fopen($file, 'rb');
	
	//-------------------- THIS IS ALL RANAGES STUFF --------------------
	
	// range can only be used when the filesize is known
	
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
	$seek_end = (empty($seek_end)) ? (filesize($file) - 1) : min(abs(intval($seek_end)),(filesize($file) - 1));
	//$seek_end = $file['Filesize'] - 1;
	$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
	
	// Only send partial content header if downloading a piece of the file (IE workaround)
	if ($seek_start > 0 || $seek_end < (filesize($file) - 1))
	{
		header('HTTP/1.1 206 Partial Content');
	}

	header('Accept-Ranges: bytes');
	header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . filesize($file));

	//headers for IE Bugs (is this necessary?)
	//header("Cache-Control: cache, must-revalidate");  
	//header("Pragma: public");

	header('Content-Length: ' . ($seek_end - $seek_start + 1));
	
	//-------------------- END RANAGES STUFF --------------------
	
	// close session now so they can keep using the website
	if(isset($_SESSION)) session_write_close();
	
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
}