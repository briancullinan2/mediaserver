<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_convert()
{
	return array(
		'name' => 'Image Converter',
		'description' => 'Convert images to different formats.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true,
		'configurable' => array('convert_path', 'convert_args'),
	);
}

function configure_convert($request)
{
	$request['convert_path'] = validate_convert_path($request);
	$request['convert_args'] = validate_convert_args($request);
	
	$options = array();
	
	if(file_exists($request['convert_path']))
	{
		$options['convert_path'] = array(
			'name' => 'Convert Path',
			'status' => '',
			'description' => '<li>A converter has been set and detected, you may change this path to specify a new converter.</li>
			<li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
			<li>The converter detected is "' . basename($request['convert_path']) . '".</li>',
			'input' => '<input type="text" name="convert_path" value="' . htmlspecialchars($request['convert_path']) . '" />'
		);
	}
	else
	{
		$options['convert_path'] = array(
			'name' => 'Convert Path',
			'status' => 'fail',
			'description' => '<li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
			<li>This convert could be ImageMagik.</li>',
			'input' => '<input type="text" name="convert_path" value="' . htmlspecialchars($request['convert_path']) . '" />'
		);
	}
	
	$options['convert_args'] = array(
		'name' => 'Convert Arguments',
		'status' => '',
		'description' => '<li>Specify the string of arguments to pass to the converter.</li>
		<li>Certain keys in the argument string will be replaced with dynamic values by the encode plugin:
		%IF - Input file, the filename that will be inserted for transcoding<br />
		%FM - Format to output<br />
		%TH - Thumbnail height<br />
		%TW - Thumbnail width<br />
		%OF - Output file if necissary<br />
		</li>',
		'input' => '<input type="text" name="convert_args" value="' . htmlspecialchars($request['convert_args']) . '" />'
	);

	return $options;
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return The default install path for VLC on windows or linux based on validate_SYSTEM_TYPE
 */
function validate_convert_path($request)
{
	if(isset($request['convert_path']) && is_file($request['convert_path']))
		return $request['convert_path'];
	else
	{
		if(setting('system_type') == 'win')
			return 'C:\Program Files\ImageMagick-6.4.9-Q16\convert.exe';
		else
			return '/usr/bin/convert';
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return The entire arg string for further validation by the configure() function
 */
function validate_convert_args($request)
{
	if(isset($request['convert_args']) && is_file($request['convert_args']))
		return $request['convert_args'];
	else
	{
		if(setting('system_type') == 'win')
			return '"%IF" %FM:-';
		else
			return '"%IF" -resize "%TWx%TH" %FM:-';
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return jpg by default, jpg, gif, and png are valid
 */
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return jpeg by default, jpeg, gif, and png are valid
 */
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return 512 by default, any number greater than zero is valid
 */
function validate_cheight($request)
{
	if(!isset($request['cheight']) || !is_numeric($request['cheight']) || $request['cheight'] <= 0)
		return 512;
	else
		return $request['cheight'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 512 by default, any number greater than zero is valid
 */
function validate_cwidth($request)
{
	if(!isset($request['cwidth']) || !is_numeric($request['cwidth']) || $request['cwidth'] <= 0)
		return 512;
	else
		return $request['cwidth'];
}

/**
 * Implementation of output
 * @ingroup output
 */
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
		// the ids handler will do the replacement of the ids
		$files = db_ids::get(array('cat' => $request['cat']), $tmp_count, $files);
		
		$tmp_request = array();
		$tmp_request['file'] = $files[0]['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);
		
		// get all the information incase we need to use it
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			if($handler != $request['cat'] && constant($handler . '::INTERNAL') == false && call_user_func_array($handler . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($handler . '::get', array($tmp_request, &$tmp_count));
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
	$cmd = basename(setting('convert_path')) . ' ' . str_replace(array(
		'%TH', 
		'%TW', 
		'%IF', 
		'%FM'
	), array(
		$request['cheight'], 
		$request['cwidth'], 
		$request['cfile'], 
		$request['cformat']
	), setting('convert_args'));

	// run process and output binary from pipe
	$descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	);

	$process = proc_open($cmd, $descriptorspec, $pipes, dirname(setting('convert_path')), NULL, array('binary_pipes' => true));

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
