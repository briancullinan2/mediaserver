<?php

// control outputting of template files
//  validate template variable

function register_template()
{
	return array(
		'name' => 'Template Output',
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
		else
			PEAR::raiseError('Template file requested but could not be found!', E_WARN);
	}
}


function validate_template($request, $session = '')
{
	if(!isset($request['template']) && $session != '')
		$request['template'] = $session;
		
	// check if it is a valid template specified
	if(isset($request['template']))
	{
		// remove template directory from beginning of input
		if(substr($request['template'], 0, 10) == 'templates/' || substr($request['template'], 0, 10) == 'templates\\')
			$request['template'] = substr($request['template'], 10);
			
		// remove leading slash if there is one
		if($request['template'][strlen($request['template'])-1] == '/' || $request['template'][strlen($request['template'])-1] == '\\')
			$request['template'] = substr($request['template'], 0, -1);
		
		// check to make sure template is valid
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

function register_style($request)
{
	// convert the request string to an array
	if(!is_array($request))
		$request = href($request, true, false, true);
		
	// validate the 2 inputs needed
	$request['template'] = validate_template($request);
	$request['tfile'] = validate_tfile($request);

	// only continue if bath properties are set
	if(isset($request['template']) && isset($request['tfile']))
	{
		register_output_vars('styles', 'plugin=template&template=' . $request['template'] . '&tfile=' . $request['tfile'], true);
		return true;
	}
	else
		PEAR::raiseError('Style could not be set because of missing arguments.', E_WARN);
	return false;
}

function register_script($request)
{
	// convert the request string to an array
	if(!is_array($request))
		$request = href($request, true, false, true);
		
	// validate the 2 inputs needed
	$request['template'] = validate_template($request);
	$request['tfile'] = validate_template($request);
	
	// only continue if bath properties are set
	if(isset($request['template']) && isset($request['tfile']))
	{
		register_output_vars('scripts', 'plugin=template&template=' . $request['template'] . '&tfile=' . $request['tfile'], true);
		return true;
	}
	else
		PEAR::raiseError('Script could not be set because of missing arguments.', E_WARN);
		
	return false;
}

function theme($request)
{
	$args = func_get_args();
	unset($args[0]);
	$args = array_values($args);
	if(is_array($piece))
	{
		$request['template'] = validate_template($request);
		$request['tfile'] = validate_template($request);
		if(!function_exists('theme_' . $request['template'] . '_' . $request['tfile']))
			$request['template'] = validate_template(array('template' => LOCAL_BASE));
		if(function_exists('theme_' . $request['template'] . '_' . $request['tfile']))
		{
			// call the function and be done with it
			call_user_func_array('theme_' . $request['template'] . '_' . $request['tfile'], $args);
			return true;
		}
		else
			PEAR::raiseError('Theme function \'theme_' . $request['template'] . '_' . $request['tfile'] . '\' was not found.', E_WARN);
	}
	elseif(is_string($request))
	{
		if(function_exists('theme_' . validate_template(array('template' => HTML_TEMPLATE)) . '_' . $request))
		{
			call_user_func_array('theme_' . validate_template(array('template' => HTML_TEMPLATE)) . '_' . $request, $args);
			return true;
		}
		elseif(function_exists('theme_' . validate_template(array('template' => LOCAL_BASE)) . '_' . $request))
		{
			call_user_func_array('theme_' . validate_template(array('template' => LOCAL_BASE)) . '_' . $request, $args);
			return true;
		}
		// it is possible the whole request
		else
		{
			$request = href($request, true, false, true);
			$result = theme((array)$request);
			if($result == false)
				PEAR::raiseError('Theme function could not be handled because of an unrecognized argument.', E_WARN);
			return $result;
		}
	}
	else
		PEAR::raiseError('Theme function could not be handled because of an unrecognized argument.', E_WARN);
	return false;
}

function output_template($request)
{
	$request['template'] = validate_template($request);
	$request['tfile'] = validate_tfile($request);

	$file = LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile'];

	// if it is a CSS file, redirect so it can use relative paths for images
	//   does this cause any security flaws?
	if(getMime($file) == 'text/css')
	{
		header('Location: ' . HTML_ROOT . 'templates/' . $request['template'] . '/' . $request['tfile']);
		exit;
	}
	
	if(!isset($request['tfile']))
	{
		// if the tfile isn't specified, display the template template
		if(isset($GLOBALS['templates']['TEMPLATE_TEMPLATE']))
		{
			// select template for the current plugin
			if(getExt($GLOBALS['templates']['TEMPLATE_TEMPLATE']) == 'php')
				@include $GLOBALS['templates']['TEMPLATE_TEMPLATE'];
			else
			{
				set_output_vars();
				$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_TEMPLATE']);
			}
		}
	}
	

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
