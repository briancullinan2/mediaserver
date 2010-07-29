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
 * Helper function
 */
function preload_settings()
{
	// get system settings from a few different locations
	if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.php'))
	{
		if($GLOBALS['settings'] = parse_ini_file(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'settings.php', true))
		{
			// awesome settings are loaded properly
			foreach($GLOBALS['settings'] as $name => $value)
			{
				if(is_array($value))
				{
					foreach($value as $subsetting => $subvalue)
					{
						$GLOBALS['settings'][$name][$subsetting] = urldecode($subvalue);
					}
				}
				else
					$GLOBALS['settings'][$name] = urldecode($value);
			}
		}
		else
		{
			unset($GLOBALS['settings']);
		}
	}
	
	// the include must have failed
	if(!isset($GLOBALS['settings']))
	{
		$GLOBALS['settings'] = array();
		
		// try and forward them to the install page
		if(!isset($_REQUEST['module'])) $_REQUEST['module'] = 'admin_install';
	}
}

/**
 * set up all the default site settings
 * @ingroup setup
 */
function setup_settings()
{
	// load settings from database
	if(setting_installed() && setting('database_enable'))
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'id = -1',
				'LIMIT' => 1
			)
		, false);
			
		// make sure the query succeeded
		if(is_array($return) && count($return) > 0)
		{
			// merge the settings from the database
			$db_settings = unserialize($return[0]['Settings']);
			$GLOBALS['settings'] = array_merge($db_settings, $GLOBALS['settings']);
		}
		else
			raise_error('There was an error getting the administrative settings from the database!', E_DEBUG);
	}

	// loop through the modules and call settings functions on them if they are set to callbacks
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['settings']) && is_string($config['settings']) && 
			$config['settings'] == $module && function_exists('setting_' . $module)
		)
			$GLOBALS['modules'][$module]['settings'] = invoke_module('setting', $module, array($GLOBALS['settings']));
	}
	
	// merge everything with the default settings
	$GLOBALS['settings'] = array_merge(settings_get_defaults($GLOBALS['settings']), $GLOBALS['settings']);
}

/**
 * Implementation of settings
 * DO NOT TOUCH!  This is how the dependency_settings() function determines when this module has been setup in setup_settings()
 * @ingroup settings
 */
function setting_settings()
{
	return array('database_reset', 'all_reset');
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
	if(!file_exists(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'settings.php'))
	{
		if(file_exists(setting_local_root() . 'settings.php'))
			return setting_local_root() . 'settings.php';
	}
	
	// add handling for multiple domains like drupal does
	
	return setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'settings.php';
}

/**
 * Implementation of dependency
 * Get all the dependencies based on what settings must be set up first
 * @ingroup dependency
 */
function dependency_settings($settings)
{
	// this is a hack to return empty array after the setup has been done
	if(isset($GLOBALS['output']['modules']))
		return array();

	$depends = array();
	
	// loop through all the modules and look for config settings set to the module name
	// those modules must be set up before settings tries to load their default values
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['settings']) && is_string($config['settings']) && 
			$config['settings'] == $module && function_exists('setting_' . $module)
		)
			$depends[] = $module;
	}
	
	// always load database first if it is being used
	if(dependency('database') !== false)
		$depends[] = 'database';

	return $depends;
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
	// if by chance settings isn't set up
	if(!isset($GLOBALS['settings']))
		preload_settings();
	
	// use cached setting if past the point of outputting the template
	if(!isset($GLOBALS['templates']['vars']))
	{
		// if the setting is not found, try to get the default
		if(function_exists('setting_' . $name))
			$GLOBALS['settings'][$name] = call_user_func_array('setting_' . $name, array($GLOBALS['settings']));
		elseif(isset($GLOBALS['setting_' . $name]) && is_callable($GLOBALS['setting_' . $name]))
			$GLOBALS['settings'][$name] = call_user_func_array($GLOBALS['setting_' . $name], array($GLOBALS['settings']));
	}
	
	// if the setting is loaded already use that
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting isn't found in the configuration
	raise_error('Setting \'' . $name . '\' not found!', E_VERBOSE);
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
		if(isset($config['settings']) && is_array($config['settings']))
		{
			foreach($config['settings'] as $i => $setting)
			{
				if(function_exists('setting_' . $setting))
					$new_setting = call_user_func_array('setting_' . $setting, array($settings));
				elseif(isset($GLOBALS['setting_' . $setting]) && is_callable($GLOBALS['setting_' . $setting]))
					$new_setting = $GLOBALS['setting_' . $setting]($settings);
				else
					raise_error('Setting \'' . $setting . '\' is specified without a validate function in the ' . $module . ' module.', E_DEBUG);
			
				if(isset($new_setting))
					$default_settings[$setting] = $new_setting;
				else
					unset($default_settings[$setting]);
			}
		}
	}
	
	return $default_settings;
}

/**
 * @}
 */
 
/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_setting_database_reset($request)
{
	if(isset($request['setting_database_reset']))
	{
		$result = _modules_save_db_settings(array());
		
		if($result)
			raise_error('The database settings have been reset!', E_WARN);
		else
			raise_error('There was a problem while saving the settings to the database!', E_USER);
	}
}
 
/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_setting_all_reset($request)
{
	if(isset($request['setting_all_reset']))
	{
		$result = _modules_save_db_settings(array());
		
		if($result)
			raise_error('The database settings have been reset!', E_WARN);
		else
			raise_error('There was a problem while saving the settings to the database!', E_USER);
			
		$result = _modules_save_fs_settings(array());
		
		if($result === -1)
			raise_error('Cannot save settings, the settings file is not writable!', E_USER);
		if($result === true)
			raise_error('The file settings have been reset!', E_WARN);
		else
			raise_error('There was a problem with saving the settings in the settings file.', E_USER);
	}
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_settings($settings)
{
	$options = array();
	
	$options['database_reset'] = array(
		'name' => 'Database Settings',
		'status' => 'warn',
		'description' => array(
			'list' => array(
				'Reset all the settings in the database, this action cannot be undone.',
			),
		),
		'type' => 'submit',
		'value' => 'Reset',
	);
	
	$options['all_reset'] = array(
		'name' => 'All Settings',
		'status' => 'fail',
		'description' => array(
			'list' => array(
				'Reset all the settings including the administrative settings (requires write access).',
			),
		),
		'type' => 'submit',
		'value' => 'Reset',
	);
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['users']['Settings']))
}

