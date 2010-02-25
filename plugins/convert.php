<?php

function register_convert()
{
	return array(
		'name' => 'convert',
		'description' => 'Convert images to different formats.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true
	);
}

function validate_convert($request)
{
	// set the header first thing so browser doesn't stall or get tired of waiting for the process to start
	if(isset($request['convert']))
	{
		$request['convert'] = strtolower($request['convert']);
		if($request['convert'] == 'jpg' || $request['convert'] == 'gif' || $request['convert'] == 'png')
		{
			return $request['convert'];
		}
	}
	return 'jpg';
}

function validate_cformat($request)
{
	if(!isset($request['cformat']))
	{
		$request['convert'] = validate_convert($request);
		switch(strtoupper($request['convert']))
		{
			case 'jpg':
				$request['cformat'] = 'jpeg';
				break;
			case 'gif':
				$request['cformat'] = 'gif';
				break;
			case 'png':
				$request['cformat'] = 'png';
				break;
		}
	}
	
	if(!in_array($request['cformat'], array('jpeg', 'gif', 'png')))
		return 'jpeg';
	else
		return $request['cformat'];
}

function validate_cheight($request)
{
	if(!isset($request['cheight']) || !is_numeric($request['cheight']))
		return 512;
	else
		return $request['cheight'];
}

function validate_cwidth($request)
{
	if(!isset($request['cwidth']) || !is_numeric($request['cwidth']))
		return 512;
	else
		return $request['cwidth'];
}

function output_convert($request)
{

	set_time_limit(0);
	ignore_user_abort(1);

	$request['convert'] = validate_convert($request);
	$request['cheight'] = validate_cheight($request);
	$request['cwidth'] = validate_cwidth($request);
	$request['cformat'] = validate_cwidth($request);
	$request['cat'] = validate_cat($request);

	switch($request['convert'])
	{
		case 'jpg':
			header('Content-Type: image/jpg');
			break;
		case 'gif':
			header('Content-Type: image/gif');
			break;
		case 'png':
			header('Content-Type: image/png');
			break;
	}

	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($request, &$count));
	
	if(count($files) > 0)
	{
		// the ids module will do the replacement of the ids
		$files = db_ids::get(array('cat' => $request['cat']), $tmp_count, $files);
		
		$tmp_request = array();
		$tmp_request['file'] = $files[0]['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);
		
		// get all the information incase we need to use it
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if($module != $request['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count));
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
			
		if(!isset($request['cheight']) && isset($files[0]['Height']))
			$request['cheight'] = $files[0]['Height'];
		
		if(!isset($request['cwidth']) && isset($files[0]['Width']))
			$request['cwidth'] = $files[0]['Width'];
		
		$request['cfile'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $files[0]['Filepath']);
	}

	// close session so the client can continue browsing the site
	if(isset($_SESSION)) session_write_close();

	// replace the argument string with the contents of $_REQUEST
	//  without validation this is VERY DANGEROUS!
	$cmd = basename(CONVERT) . ' ' . str_replace(array('%TH', '%TW', '%IF', '%FM'), array($request['cheight'], $request['cwidth'], $request['cfile'], $request['cformat']), CONVERT_ARGS);

	// run process and output binary from pipe
	$descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	);

	$process = proc_open($cmd, $descriptorspec, $pipes, dirname(CONVERT), NULL, array('binary_pipes' => true));

	// output file
	if(is_resource($process))
	{
		while(!feof($pipes[1]))
		{
			if(connection_status()!=0)
			{
				proc_terminate($process);
				break;
			}
			print fread($pipes[1], BUFFER_SIZE);
			flush();
		}
		fclose($pipes[1]);
	
		$status = proc_get_status($process);
		kill9('convert', $status['pid']);
	
		$return_value = proc_close($process);
	}
}


?>
