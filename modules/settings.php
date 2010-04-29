<?php

/**
 * allow templates to save settings
 * get all template settings from database
 * parse out selected template setting
 * return settings in manner that the template understands
 * templates can submit settings using the config options for the template
 *   define the setting name and type in a key array
 *   types will be specified in the documentation (aligns with form types), text, int, radio, checkbox)
 */
 
/**
 * Implementation of register
 * @ingroup register
 */
function register_settings()
{
	return array(
		'name' => 'Settings',
		'description' => 'This allows users to save theme settings.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

/**
 * set up all the default site settings
 * @ingroup setup
 */
function setup_settings()
{
	// if there isn't by chance already a setting global set it up here
	if(!isset($GLOBALS['settings']))
		$GLOBALS['settings'] = array();
	
	// merge everything with the default settings
	$GLOBALS['settings'] = array_merge(settings_get_defaults($GLOBALS['settings']), $GLOBALS['settings']);
}

/**
 * Get a setting
 * @param name The setting name to get
 */
function setting($name)
{
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting isn't found in the configuration
	PEAR::raiseError('Setting \'' . $name . '\' not found!', E_DEBUG);
}

/**
 * Get all the default settings
 */
function settings_get_defaults($settings)
{
	// existing settings are passed in to this function incase a default depends on something already set up
	
	// new settings to return
	$default_settings = array();
	
	// load core settings first
	foreach($GLOBALS['modules']['core']['settings'] as $i => $setting)
	{
		$default_settings[$setting] = call_user_func_array('validate_' . $setting, array($settings));
	}
	
	// loop through each module and get the default settings
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['settings']))
		{
			foreach($config['settings'] as $i => $setting)
			{
				if(function_exists('validate_' . $setting))
				{
					$default_settings[$setting] = call_user_func_array('validate_' . $setting, array($settings));
				}
				else
					PEAR::raiseError('Setting \'' . $setting . '\' is specified without a validate function in the ' . $module . ' module.');
			}
		}
	}

	// database
	$default_settings['use_database'] = false;
	$default_settings['db_type'] = 'mysql';
	$default_settings['db_server'] = 'localhost';
	$default_settings['db_user'] = '';
	$default_settings['db_pass'] = '';
	$default_settings['db_name'] = '';
		
	// html name
	$default_settings['html_name'] = 'Brian\'s Media Website';
	
	// other
	$default_settings['debug_mode'] = false;
	$default_settings['recursive_get'] = false;
	$default_settings['no_bots'] = true;
	
	// tmp dir
	$tmpfile = tempnam("dummy","");
	unlink($tmpfile);
	$default_settings['tmp_dir'] = dirname($tmpfile) . DIRECTORY_SEPARATOR;

	// users
	$default_settings['local_users'] = $settings['local_root'] . 'users' . DIRECTORY_SEPARATOR;
	
	// buffer size
	$default_settings['buffer_size'] = 2*1024*8;
	
	// use alias
	$default_settings['use_alias'] = true;
	
	return $default_settings;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['user']['Settings']))
}

