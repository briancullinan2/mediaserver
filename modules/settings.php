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
		'description' => 'This allows users to save theme and site settings.',
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
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 * @return possible settings file paths in order of preference to the system, false if file doesn't exist anywhere
 */
function setting_settings_file()
{
	// return file from where it is supposed to be
	// check other directories if it doesn't exist there
	if(!file_exists(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'settings.ini'))
	{
		if(file_exists(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'settings.ini'))
			return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'settings.ini';
	}
	
	// add handling for multiple domains like drupal does
	
	return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'settings.ini';
}

/**
 * @defgroup setting Settings Functions
 * All functions that handling site settings, basically designed exactly the same way as validate()
 * @param settings the current list of settings for settings to depend on each other
 * @return default values of settings or a validated setting input
 * @{
 */

/**
 * Get a setting
 * @ingroup setting
 * @param name The setting name to get
 */
function setting($name)
{
	// if the setting is loaded already use that
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting is not found, try to get the default
	if(function_exists('setting_' . $name))
		return call_user_func_array('setting_' . $name, array($GLOBALS['settings']));
	elseif(isset($GLOBALS['setting_' . $name]) && is_callable($GLOBALS['setting_' . $name]))
		return $GLOBALS['setting_' . $name]($GLOBALS['settings']);
		
	// if the setting isn't found in the configuration
	PEAR::raiseError('Setting \'' . $name . '\' not found!', E_DEBUG);
}

/**
 * Get all the default settings
 * @ingroup setting
 */
function settings_get_defaults($settings)
{
	// existing settings are passed in to this function incase a default depends on something already set up
	
	// new settings to return
	$default_settings = array();
	
	// load core settings first
	foreach($GLOBALS['modules']['core']['settings'] as $i => $setting)
	{
		$default_settings[$setting] = call_user_func_array('setting_' . $setting, array($settings));
	}
	
	// loop through each module and get the default settings
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['settings']))
		{
			foreach($config['settings'] as $i => $setting)
			{
				if(function_exists('setting_' . $setting))
					$default_settings[$setting] = call_user_func_array('setting_' . $setting, array($settings));
				elseif(isset($GLOBALS['setting_' . $setting]) && is_callable($GLOBALS['setting_' . $setting]))
					$default_settings[$setting] = $GLOBALS['setting_' . $setting]($settings);
				else
					PEAR::raiseError('Setting \'' . $setting . '\' is specified without a validate function in the ' . $module . ' module.', E_DEBUG);
			}
		}
	}
	
	return $default_settings;
}

/**
 * @}
 */

/**
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['user']['Settings']))
}

