<?php


function register_template()
{
	return array(
		'name' => 'Templates',
		'description' => 'Display files from the templates directory. Allows for templating CSS and JS files.',
		'privilage' => 0,
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
		'templates/%template/%tfile' => array(
			'callback' => 'output_template_file',
			'no_session' => true,
		),
		'template/%template/%tfile' => array(
			'callback' => 'output_template_file',
			'no_session' => true,
		),
		'template/%tfile' => array(
			'callback' => 'output_template_file',
			'no_session' => true,
		),
		'template/%template/styles' => array(
			'callback' => 'theme',
			'type' => MENU_CALLBACK,
			'template' => 'styles',
		),
		'template/%template/scripts' => array(
			'callback' => 'theme',
			'type' => MENU_CALLBACK,
			'template' => 'scripts',
		),
	);
}


/**
 * Generate a list of templates
 * @ingroup setup
 */
function preload_template()
{
	$GLOBALS['theme_stack'] = array();
	
	// load templating system but only if we are using templates

	// get the list of templates
	$GLOBALS['templates'] = load_modules(setting_local_root() . 'templates');
}

function setup_template()
{
	// bootstrap the modules and setup
	//bootstrap('modules', array_keys($GLOBALS['templates']));
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
	
	$options['setting_local_template'] = array(
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

	return array('template' => array(
		'name' => 'Display Settings',
		'type' => 'fieldset',
		'options' => $options
	));
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
	elseif(isset($_REQUEST['template']))
		return validate($_REQUEST, 'template');
	elseif(($template = session('template')))
		return $template;
	else
		return 'tunegazelle';
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
		elseif(theme_implements($request['tfile'], $request['template']))
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
			return $request['template'];
	}
	elseif(setting('agent_simple') == 'mobile')
		return 'mobile';

	return setting('local_template');
}

function validate_extra($request)
{
	return generic_validate_machine_readable($request, 'extra');
}

/**
 * Implementation of session
 * @ingroup session
 * @return the selected template for reference
 */
function session_template($request)
{
	return validate($request, 'template');
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

function theme_implements($method, $theme = '')
{
	return function_exists('theme_' . $theme . '_' . $method);
}

function implements_theme($method)
{
	if(substr($method, 0, 7) == 'theme__')
		return '';
	elseif(substr($method, 0, 6) == 'theme_')
		return substr($method, 6, strpos($method, '_', 6) - 6);
	else
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
		flush();
		if($request['template'] != '')
			theme($request['template'], array());
		return;
	}
	elseif(is_array($request))
	{
		$request = array_shift($request);
	}
	
	// if the theme function is being called then the output vars better be set
	if(!isset($GLOBALS['output']['html']))
		set_output_vars();
		
	// the request is a string, this is most common
	// check if function exists in current theme
	if(theme_implements($request, setting('local_template')))
		$function = 'theme_' . setting('local_template') . '_' . $request;
	// check if a default function exists for the theme
	elseif(theme_implements($request))
		$function = 'theme__' . $request;
	else
	{
		raise_error('Theme function \'theme_' . validate(array('template' => setting('local_template')), 'template') . '_' . $request . '\' was not found.', E_DEBUG);
		return false;
	}
		
	// get the arguments to pass on to theme_ functions
	$args = func_get_args();
	
	// do not pass original theme call argument
	array_shift($args);
	
	raise_error('Theming \'' . $request . '\' with the function \'' . $function . '\'.', E_VERBOSE);
	
	array_push($GLOBALS['theme_stack'], array('func' => $function, 'args' => $args));
	
	$result = call_user_func_array($function, $args);
	
	array_pop($GLOBALS['theme_stack']);
		
	return $result;
}

function ob_theme($request = '')
{
	$args = func_get_args();
	ob_start();
	$result = call_user_func_array('theme', $args);
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
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
	if(isset($GLOBALS['output'][$name]) && !$append)
	{
		raise_error('Variable "' . $name . '" already set!', E_DEBUG);
	}
	if(!$append)
		$GLOBALS['output'][$name] = $value;
	elseif(!isset($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = $value;
	elseif(is_string($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = array($GLOBALS['output'][$name], $value);
	elseif(is_array($GLOBALS['output'][$name]) && is_array($value))
		$GLOBALS['output'][$name] = array_merge($GLOBALS['output'][$name], $value);
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
		if($name != 'html')
			$GLOBALS['output']['html'][$name] = traverse_array($value);
	}
}

function output_template_file($request)
{
	$request['template'] = validate($request, 'template');
	$request['tfile'] = validate($request, 'tfile');

	$file = setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $request['template'] . DIRECTORY_SEPARATOR . $request['tfile'];
	
	if(file_exists($file))
	{
			
		// set some general headers
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: ' . mime($file));
		
		// close session now so they can keep using the website
		if(isset($_SESSION)) session_write_close();
		
		// set up the output stream
		$of = fopen('php://output', 'wb');
		
		// get the input stream
		$if = fopen($file, 'rb');
		
		// output file with ranges from server
		list($seek_start) = output_ranges($files[0]['Filesize']);
		
		// seek to start of missing part
		if(isset($seek_start))
			fseek($if, $seek_start);
		
		output_stream($if, $of);
	}
	else
	{
		raise_error('File not found!', E_USER);
		theme();
	}
}

function theme__default()
{
	theme('header');

	theme('footer');
}

function theme__footer()
{
	if($GLOBALS['output']['module'] != 'users')
		theme('login_block');
	
	?>
	Modules:
<ul>
<?php
foreach(get_modules() as $i => $module)
{
	if(get_module($module, 'privilage') > $GLOBALS['output']['user']['Privilage'])
		continue;
		
	if(!module_implements('output', $module))
		$link = 'admin/module/' . $module;
	else
		$link = $name;
									
	?><li><a href="<?php print url($link); ?>"><?php echo get_module($module, 'name'); ?></a></li><?php
}
?>
</ul>
</body>
</html>
	<?php
}

function theme__header($title = NULL, $description = NULL)
{
	if(!isset($title))
		$title = htmlspecialchars(get_module($GLOBALS['output']['module'], 'name')) . ' : ' . setting('html_name');
	
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print $title; ?></title>
</head>

<body>
<h1><?php print $title; ?></h1><?php
	
	theme('errors_block');
}

function theme__redirect()
{
	theme('header');
	
	?>You are being redirected...<?php
	
	theme('footer');
}

function theme__redirect_block()
{
	if(isset($GLOBALS['output']['html']['redirect']))
	{
		?><META HTTP-EQUIV="refresh" CONTENT="1;URL=<?php print $GLOBALS['output']['html']['redirect']; ?>"><?php
	}
}

function theme__template()
{
	theme('header');
	
	theme('template_block');

	theme('footer');
}

function theme__template_block()
{
	?>
	<br /><br />Select a Template:<br />
	<?php
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print url('template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><br /><?php
		}
	}
}

function theme__scripts($scripts = array(), $combined = false)
{
	if(!isset($scripts) && isset($GLOBALS['output']['scripts']))
		$scripts = $GLOBALS['output']['scripts'];
	elseif(!isset($scripts))
		return;
		
	if(is_string($scripts)) $scripts = array($scripts);

	if($combined)
	{
		header("Cache-Control: cache");  
		header("Pragma: public");
		header('Content-Type: text/javascript');
		
		foreach($scripts as $i => $script)
		{
			print file_get_contents(setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . setting('local_template') . DIRECTORY_SEPARATOR . validate(array('tfile' => $script), 'tfile'));
		}
	}
	else
	{
		foreach($scripts as $link)
		{
			?>
			<script type="text/javascript" src="<?php print url($link); ?>"></script>
			<?php
		}
	}
}

function theme__styles($styles = array(), $combined = false)
{
	if(!isset($styles) && isset($GLOBALS['output']['styles']))
		$styles = $GLOBALS['output']['styles'];
	elseif(!isset($styles))
		return;
		
	if(is_string($styles)) $styles = array($styles);
	
	if($combined)
	{
		header("Cache-Control: cache");  
		header("Pragma: public");
		header('Content-Type: text/css');
		
		foreach($styles as $i => $style)
		{
			print file_get_contents(setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . setting('local_template') . DIRECTORY_SEPARATOR . validate(array('tfile' => $style), 'tfile'));
		}
	}
	else
	{
		foreach($styles as $link)
		{
			?>
			<link rel="stylesheet" href="<?php print url($link); ?>" type="text/css"/>
			<?php
		}
	}
}


/**
 * Implementation of output
 * @ingroup output
 */
/*function output_template($request)
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
	elseif(theme_implements($request['tfile'], $request['template']) || theme_implements($request['tfile']))
	{
		theme($request['tfile']);
		
		return;
	}
	
	// set some general headers
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: ' . mime($file));
	
	// close session now so they can keep using the website
	if(isset($_SESSION)) session_write_close();
	
	// set up the output stream
	$of = fopen('php://output', 'wb');
	
	// get the input stream
	$if = fopen($file, 'rb');
	
	// output file with ranges from server
	list($seek_start) = output_ranges($files[0]['Filesize']);
	
	// seek to start of missing part
	if(isset($seek_start))
		fseek($if, $seek_start);
	
	output_stream($if, $of);
}
*/