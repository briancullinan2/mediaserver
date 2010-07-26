<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_convert()
{
	return array(
		'name' => lang('convert title', 'Image Converter'),
		'description' => lang('convert description', 'Convert images to different formats.'),
		'privilage' => 1,
		'path' => __FILE__,
		'template' => false,
		'settings' => array('convert_path', 'convert_args'),
		'depends on' => array('converter'),
	);
}

/**
 * Checks for convert path
 * @ingroup configure
 */
function configure_convert($settings, $request)
{
	$settings['convert_path'] = setting_convert_path($settings);
	$settings['convert_args'] = setting_convert_args($settings);
	
	$options = array();
	
	if(dependency('converter') != false)
	{
		$options['convert_path'] = array(
			'name' => lang('convert path title', 'Convert Path'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('convert path description 1', 'A converter has been set and detected, you may change this path to specify a new converter.'),
					lang('convert path description 2', 'The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.'),
					lang('convert path description 3', 'The converter detected is "' . basename($settings['convert_path']) . '".'),
				),
			),
			'type' => 'text',
			'value' => $settings['convert_path'],
		);
	}
	else
	{
		$options['convert_path'] = array(
			'name' => lang('convert path title', 'Convert Path'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('convert path fail description 1', 'The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.'),
					lang('convert path fail description 2', 'This convert could be ImageMagik.'),
				),
			),
			'type' => 'text',
			'value' => $settings['convert_path'],
		);
	}
	
	$options['convert_args'] = array(
		'name' => lang('convert args title', 'Convert Arguments'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('convert args description 1', 'Specify the string of arguments to pass to the converter.'),
				lang('convert args description 2', 'Certain keys in the argument string will be replaced with dynamic values by the encode plugin:') . '<br />
				%IF - ' . lang('convert args input file', 'Input file, the filename that will be inserted for transcoding') . '<br />
				%FM - ' . lang('convert args format', 'Format to output') . '<br /> 
				%TH - ' . lang('convert args height', 'Thumbnail height') . '<br />
				%TW - ' . lang('convert args width', 'Thumbnail width') . '<br />
				%OF - ' . lang('convert args output file', 'Output file if necissary'),
			),
		),
		'type' => 'text',
		'value' => $settings['convert_args'],
	);

	return $options;
}

/**
 * Implementation of status
 * @ingroup status
 */
function dependency_converter($settings)
{
	$settings['convert_path'] = setting_convert_path($settings);
	return file_exists($settings['convert_path']);
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The default install path for VLC on windows or linux based on validate_SYSTEM_TYPE
 */
function setting_convert_path($settings)
{
	if(isset($settings['convert_path']) && is_file($settings['convert_path']))
		return $settings['convert_path'];
	else
	{
		if(setting_system_type($settings) == 'win')
			return 'C:\Program Files\ImageMagick-6.4.9-Q16\convert.exe';
		else
			return '/usr/bin/convert';
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The entire arg string for further validation by the configure() function
 */
function setting_convert_args($settings)
{
	if(isset($settings['convert_args']) && is_file($settings['convert_args']))
		return $settings['convert_args'];
	else
	{
		if(setting_system_type($settings) == 'win')
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
		$request['convert'] = validate($request, 'convert');
		switch($request['convert'])
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
	return generic_validate_numeric_default($request, 'cheight', 512);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 512 by default, any number greater than zero is valid
 */
function validate_cwidth($request)
{
	return generic_validate_numeric_default($request, 'cwidth', 512);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_convert($request)
{
	set_time_limit(0);
	ignore_user_abort(1);

	if(!isset($request['convert']))
	{
		theme('default');
		
		return;
	}
	
	// validate all the variables used
	$request['convert'] = validate($request, 'convert');
	$request['cheight'] = validate($request, 'cheight');
	$request['cwidth'] = validate($request, 'cwidth');
	$request['cformat'] = validate($request, 'cformat');
	$request['cat'] = validate($request, 'cat');

	// get the file path from the database
	$files = get_files($request, $count, $request['cat']);
	
	if(count($files) > 0)
	{
		// the ids handler will do the replacement of the ids
		$files = get_ids(array('cat' => $request['cat']), $tmp_count, $files);
		
		$tmp_request = array();
		$tmp_request['file'] = $files[0]['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);
		
		// get all the information incase we need to use it
		foreach($GLOBALS['modules'] as $handler => $config)
		{
			if($handler != $request['cat'] && !is_internal($handler) && handles($files[0]['Filepath'], $handler))
			{
				$return = get_files($tmp_request, $tmp_count, $handler);
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
			
		if(!isset($request['cheight']) && isset($files[0]['Height']))
			$request['cheight'] = $files[0]['Height'];
		
		if(!isset($request['cwidth']) && isset($files[0]['Width']))
			$request['cwidth'] = $files[0]['Width'];
		
		$request['cfile'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $files[0]['Filepath']);
	}

	// set the headers
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
