<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_archive()
{
	return array(
		'name' => 'Archive Generator',
		'description' => 'Convert sets of files to an archive using a command line program.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

/**
 * Implementation of validate
 * Does nothing yet
 * @ingroup validate
 */
function validate_archive($request)
{
	
}

/**
 * Implementation of output
 * Outputs an archive
 * @ingroup output
 */
function output_archive($request)
{
		
	// set the header first thing so browser doesn't stall or get tired of waiting for the process to start
	switch($request['archive'])
	{
		case 'ZIPIN':
			header('Content-Type: application/zip');
			break;
		case 'RARIN':
			header('Content-Type: application/rar');
			break;
	}
	
	// set up required variables
	$request['cat'] = validate_cat($request);
	
	if(!isset($request['%IF']) && isset($request['id']))
		$request['%IF'] = $request['id'];
	
	// check the id and stuff
	if(isset($request['%IF']))
	{
		// get the file path from the database
		$files = call_user_func_array($request['cat'] . '::get', array(array('id' => $request['id']), &$count));
		
		if(count($files) > 0)
		{
			$request['%IF'] = $files[0]['Filepath'];
		}
	}
	else
	{
		exit;
	}
	
	set_time_limit(0);
	ignore_user_abort(1);
	
	// do the validation for all the input options!
	// first filter out all the unwanted request vars
	foreach($_REQUEST as $key => $value)
	{
		if(!in_array($key, array('convert', '%IF', '%FM')))
			unset($_REQUEST[$key]);
	}
	
	// here is some presets:
	switch($_REQUEST['convert'])
	{
		case 'JPG':
			$_REQUEST['%FM'] = 'jpeg';
			break;
		case 'GIF':
			$_REQUEST['%FM'] = 'gif';
			break;
		case 'PNG':
			$_REQUEST['%FM'] = 'png';
			break;
	}
	
	// validate each individually, these also go to default if there is invalid input! (in this case it uses jpeg settings)
	if(!in_array($_REQUEST['%FM'], array('jpeg', 'gif', 'png')))
		$_REQUEST['%FM'] = 'jpeg';
	
	// replace the argument string with the contents of $_REQUEST
	//  without validation this is VERY DANGEROUS!
	$cmd = basename(setting('archive_path')) . ' ' . str_replace(array_keys($_REQUEST), array_values($_REQUEST), setting('archive_args'));
	
	// run process and output binary from pipe
	$descriptorspec = array(
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	);
	
	$process = proc_open($cmd, $descriptorspec, $pipes, dirname(setting('archive_path')), NULL, array('binary_pipes' => true));
	
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
			print fread($pipes[1], setting('buffer_size'));
			flush();
		}
		fclose($pipes[1]);
		
		$status = proc_get_status($process);
		kill9('convert', $status['pid']);
		
		$return_value = proc_close($process);
	}
}

