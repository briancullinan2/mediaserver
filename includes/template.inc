<?php


function register_template()
{
	return array(
		'name' => 'Template Output',
		'description' => 'Display files from the templates directory. Allows for templating CSS and JS files.',
		'privilage' => 1,
		'session' => 'template',
		'template' => false,
		'settings' => 'template',
		'output' => 'template_variables',
		'package' => 'core',
	);
}


/**
 * control outputting of template files
 *  validate template variable
 */

function menu_template()
{
	return array(
		'template/%template/%tfile' => array(
			'callback' => 'output_template',
		)
	);
}


/**
 * Generate a list of templates
 * @ingroup setup
 */
function setup_template()
{
	// load templating system but only if we are using templates

	// get the list of templates
	$GLOBALS['templates'] = load_modules(setting_local_root() . 'templates');
	
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_template()
{
	return array('local_template');
}

/**
 * Configure all template options
 * @ingroup configure
 */
function configure_template($settings, $request)
{
	$settings['local_root'] = setting('local_root');
	$settings['local_template'] = setting('local_template');
	
	$options = array();
	
	$templates = array();
	foreach($GLOBALS['templates'] as $template => $config)
	{
		$templates[$template] = $config['name'];
	}
	
	$options['local_template'] = array(
		'name' => 'Local Template',
		'status' => '',
		'description' => array(
			'list' => array(
				'If this is set, this template will always be displayed to the users.  They will not be given the option to select their own template.',
			),
		),
		'type' => 'select',
		'value' => $settings['local_template'],
		'options' => array('' => 'Not Set') + $templates,
	);

	return $options;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_local_template($settings)
{
	if(isset($settings['local_template']) && in_array($settings['local_template'], array_keys($GLOBALS['templates'])))
		return $settings['local_template'];
	else
		return 'live';
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts any valid existing file from the scope of the template directory, throws an error if the file is invalid
 */
function validate_tfile($request)
{
	if(isset($request['tfile']))
	{
		$request['template'] = validate($request, 'template');
		$file = setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile'];
		// get real path and make sure it begins with the template directory
		if(is_file($file))
		{
			if(substr(realpath($file), 0, strlen(realpath(setting('local_root') . 'templates'))) == realpath(setting('local_root') . 'templates'))
			{
				return $request['tfile'];
			}
		}
		elseif(function_exists('theme_' . $request['template'] . '_' . $request['tfile']))
			return $request['tfile'];

		raise_error('Template file requested but could not be found!', E_DEBUG|E_WARN);
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return setting('local_template') by default, accepts any valid template, attempts to determine the best template based on the HTTP_USER_AGENT
 */
function validate_template($request, $session = '')
{
	if(!isset($request['template']) && $session != '')
		$request['template'] = $session;

	// check if it is a valid template specified
	if(isset($request['template']) && $request['template'] != '')
	{
		// remove template directory from beginning of input
		if(substr($request['template'], 0, 10) == 'templates/' || substr($request['template'], 0, 10) == 'templates\\')
			$request['template'] = substr($request['template'], 10);
			
		// remove leading slash if there is one
		if($request['template'][strlen($request['template'])-1] == '/' || $request['template'][strlen($request['template'])-1] == '\\')
			$request['template'] = substr($request['template'], 0, -1);
			
		// check to make sure template is valid
		if(in_array($request['template'], array_keys($GLOBALS['templates'])))
		{
			return $request['template'];
		}
	}
	elseif(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/.*mobile.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		return 'mobile';
	}
	return setting('local_template');
}

/**
 * Implementation of session
 * @ingroup session
 * @return the selected template for reference
 */
function session_template($request)
{
	return $request['template'];
}

/**
 * Register a stylesheet for use with a particular template
 * @param request the url to the stylesheet that validate with validate_template and validate_tfile
 * @return true on success, false on failure and throws an error
 */
function register_style($request)
{
	// convert the request string to an array
	if(!is_array($request))
		$request = url($request, true, false, true);
		
	// validate the 2 inputs needed
	$request['template'] = validate($request, 'template');
	$request['tfile'] = validate($request, 'tfile');

	// only continue if bath properties are set
	if(isset($request['template']) && isset($request['tfile']))
	{
		register_output_vars('styles', 'template/' . $request['template'] . '/' . $request['tfile'], true);
		return true;
	}
	else
		raise_error('Style could not be set because of missing arguments.', E_DEBUG|E_WARN);
	return false;
}

/**
 * Register a javascript for use with a particular template
 * @param request the url to the javascript that validate with validate_template and validate_tfile
 * @return true on success, false on failure and throws an error
 */
function register_script($request)
{
	// convert the request string to an array
	if(!is_array($request))
		$request = url($request, true, false, true);
		
	// validate the 2 inputs needed
	$request['template'] = validate($request, 'template');
	$request['tfile'] = validate($request, 'tfile');
	
	// only continue if bath properties are set
	if(isset($request['template']) && isset($request['tfile']))
	{
		register_output_vars('scripts', 'template/' . $request['template'] . '/' . $request['tfile'], true);
		return true;
	}
	else
		raise_error('Script could not be set because of missing arguments.', E_DEBUG|E_WARN);
		
	return false;
}

/**
 * Calls theming functions
 * @param request the name of the theme function to call
 */
function theme($request = '')
{
	// if the theme function is just being called without any input
	//   then call the default theme function
	if($request == '')
	{
		$request['template'] = validate(array(), 'template');
		set_output_vars();
		call_user_func_array('output_' . $request['template'], array());
		return;
	}
	
	// if the theme function is being called then the output vars better be set
	if(!isset($GLOBALS['output']['html']))
		set_output_vars();
	
	// get the arguments to pass on to theme_ functions
	$args = func_get_args();
	
	// do not pass original theme call argument
	array_shift($args);
	
	// if the request is an array, assume they are setting the template and theme call
	if(is_array($request))
	{
		// the tfile parameter can be used to call the theme_ function
		$request['template'] = validate($request, 'template');
		$request['tfile'] = validate($request, 'tfile');
		
		// if the function exists call the theme_ implementation
		if(function_exists('theme_' . $request['template'] . '_' . $request['tfile']))
		{
			// call the function and be done with it
			call_user_func_array('theme_' . $request['template'] . '_' . $request['tfile'], $args);
			return true;
		}
		// check if a default function exists for the theme
		elseif(function_exists('theme__' . $request['tfile']))
		{
			// call the function and be done with it
			call_user_func_array('theme__' . $request['tfile'], $args);
			return true;
		}
		else
			raise_error('Theme function \'theme_' . $request['template'] . '_' . $request['tfile'] . '\' was not found.', E_DEBUG|E_WARN);
	}
	// the request is a string, this is most common
	elseif(is_string($request))
	{
		// check if function exists in current theme
		if(function_exists('theme_' . setting('local_template') . '_' . $request))
		{
			call_user_func_array('theme_' . setting('local_template') . '_' . $request, $args);
			return true;
		}
		// check if a default function exists for the theme
		elseif(function_exists('theme__' . $request))
		{
			call_user_func_array('theme__' . $request, $args);
			return true;
		}
		// it is possible the whole request
		else
			raise_error('Theme function \'theme_' . validate(array('template' => setting('local_template')), 'template') . '_' . $request . '\' was not found.', E_DEBUG|E_WARN, 'template');
	}
	else
		raise_error('Theme function could not be handled because of an unrecognized argument.', E_DEBUG|E_WARN);
	return false;
}

/**
 * Implementation of always_output
 * @ingroup always_output
 */
function template_variables($request)
{
	$request['template'] = validate($request, 'template');
	
	// assign some shared variables
	register_output_vars('templates', $GLOBALS['templates']);
	
	// this is just a helper variable for templates to use that only need to save 1 setting
	if(isset($request['extra'])) register_output_vars('extra', $request['extra']);
	
	// register user settings for this template
	$user = session('users');
	if(isset($user['settings']['templates'][$request['template']]))
		register_output_vars('settings', $user['settings']['templates'][$request['template']]);
		
	// go through and set the defaults
	elseif(isset($GLOBALS['templates'][$request['template']]['settings']))
	{
		$settings = array();
		foreach($GLOBALS['templates'][$request['template']]['settings'] as $key => $setting)
		{
			if(isset($setting['default']))
				$settings[$key] = $setting['default'];
		}
		register_output_vars('settings', $settings);
	}
}

/**
 * Make variables available for output in the templates,
 * convert variables to HTML compatible for security
 * @param name name of the variable the template can use to refer to
 * @param value value for the variable, converted to HTML
 * @param append (Optional) append the input value to a pre-existing set of data
 */
function register_output_vars($name, $value, $append = false)
{
	if(isset($GLOBALS['output'][$name]) && $append == false)
	{
		raise_error('Variable "' . $name . '" already set!', E_DEBUG);
	}
	if($append == false)
		$GLOBALS['output'][$name] = $value;
	elseif(!isset($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = $value;
	elseif(is_string($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = array($GLOBALS['output'][$name], $value);
	elseif(is_array($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name][] = $value;
}

/**
 * Function to call before the template is called, this can also be called from the first time #theme() is called
 * This sets all the register variables as HTML or original content, it also removes all unnecissary variables that might be used to penetrate the site
 */
function set_output_vars()
{
	// triggers for output can also be set
	trigger('output', NULL, $_REQUEST);
	
	$GLOBALS['output']['html'] = array();

	foreach($GLOBALS['output'] as $name => $value)
	{
		$GLOBALS['output']['html'][$name] = traverse_array($value);
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_template($request)
{
	$request['template'] = validate($request, 'template');
	$request['tfile'] = validate($request, 'tfile');

	$file = setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile'];

	// if the file is not set, it cannot continue
	if(!isset($request['tfile']))
	{
		// if the tfile isn't specified, display the template template
		theme('template');
		
		return;
	}
	// check if they are calling a theme function
	elseif(function_exists('theme_' . $request['template'] . '_' . $request['tfile']) || 
		function_exists('theme__' . $request['tfile']))
	{
		theme($request);
		
		return;
	}
	
	// set some general headers
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: ' . mime($file));
	
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
			fwrite($op, fread($fp, setting('buffer_size', 2*1024*8)));
		}
		
		// close file handles and return succeeded
		fclose($fp);
	}
}
