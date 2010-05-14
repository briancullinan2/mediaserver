<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_modules()
{
	return array(
		'name' => lang('modules title', 'Configure Modules'),
		'description' => lang('modules description', 'Display a list of modules and allow for enabling and disabling.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array(),
		'depends on' => array('admin', 'admin_tools', 'settings')
	);
}

/**
 * Implementation of setup
 * @ingroup setup
 */
function setup_admin_modules()
{
	$module_func = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$module_func[] = $module . '_enable';
		$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return setting_module_enable($request, \'' . $module . '\');');
	}
	
	$GLOBALS['modules']['admin_modules']['settings'] = $module_func;
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @true or false if the settings file is writeable, so that this module can write to it
 */
function dependency_writable_settings_file($settings)
{
	if(setting_installed() == false)
		return false;
	return is_writable(setting_settings_file($settings));
}

/**
 * Implementation of dependencies
 */
function status_admin_modules($settings)
{
	$options = array();
	
	// settings permission
	if(dependency('writable_settings_file'))
	{
		$options['writable_settings_file'] = array(
			'name' => lang('settings access title', 'Access to Settings'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('settings access description', 'The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.'),
				),
			),
			'type' => 'text',
			'disabled' => true,
			'value' => setting_settings_file($settings),
		);
	}
	else
	{
		$options['writable_settings_file'] = array(
			'name' => lang('settings access title', 'Access to Settings'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('settings access fail description 1', 'The system would like access to the following file.  This is so it can write all the settings when we are done with the install.'),
					lang('settings access fail description 2', 'Please create this file, and grant it Read/Write permissions.'),
				),
			),
			'type' => 'text',
			'disabled' => true,
			'value' => setting_settings_file($settings),
		);
	}
	
	return $options;
}

/**
 * Implementation of setting, validate all module_enable settings
 * @ingroup setting
 */
function setting_module_enable($settings, $module)
{
	if(isset($settings[$module . '_enable']))
	{
		if($settings[$module . '_enable'] === false || $settings[$module . '_enable'] === 'false')
			return false;
		elseif($settings[$module . '_enable'] === true || $settings[$module . '_enable'] === 'true')
			return true;
	}
	return true;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_modules($settings)
{
	$recommended = array('select', 'list', 'search');
	$required = array('core', 'index', 'users', 'select');
	
	$options = array();
	
	$required_str = lang('modules enable option 1', 'Enabled (Required)');
	$recommend = lang('modules enable option 2', 'Enabled (Recommended)');
	$optional = lang('modules enable option 3', 'Enabled (Optional)');
	$disabled = lang('modules enable option 4', 'Disabled');
	$description_1 = lang('modules enable description 1', 'Choose whether or not to enable the module.');
	$description_2 = lang('modules enable description 2', 'Click configure to configure additional options for a specific module.');
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$settings[$module . '_enable'] = setting_module_enable($settings, $module);
		$options[$module . '_enable'] = array(
			'name' => $GLOBALS['modules'][$module]['name'],
			'status' => '',
			'description' => array(
				'list' => array(
					$GLOBALS['modules'][$module]['description'],
					$description_1,
					$description_2,
				),
			),
			'type' => 'boolean',
			'value' => in_array($module, $required)?true:$settings[$module . '_enable'],
		);
		
		if(in_array($module, $required))
		{
			$options[$module . '_enable']['options'] = array(
				$required_str,
			);
			$options[$module . '_enable']['disabled'] = true;
		}
		else
		{
			$options[$module . '_enable']['options'] = array(
				(in_array($module, $recommended)?$recommend:$optional),
				$disabled,
			);
		}
	}
	
	return $options;
}

/**
 * Used to configure plugins
 * @ingroup validate
 * @return admin_modules by default, accepts any module name that is configurable
 */
function validate_configure_module($request)
{
	if(isset($request['configure_module']) && isset($GLOBALS['modules'][$request['configure_module']]) &&
		isset($GLOBALS['modules'][$request['configure_module']]['settings']) && count($GLOBALS['modules'][$request['configure_module']]['settings']) > 0
	)
	{
		if(!function_exists('configure_' . $request['configure_module']))
		{
			PEAR::raiseError('Configuration function \'' . $request['configure_module'] . '\' does not exist!', E_DEBUG);
			return 'admin_modules';
		}
		return $request['configure_module'];
	}
	elseif(isset($request['configure_module']) && isset($GLOBALS['handlers'][$request['configure_module']]) &&
		isset($GLOBALS['handlers'][$request['configure_module']]['settings']) && count($GLOBALS['handlers'][$request['configure_module']]['settings']) > 0
	)
	{
		if(!function_exists('configure_' . $request['configure_module']))
		{
			PEAR::raiseError('Configuration handler function \'' . $request['configure_module'] . '\' does not exist!', E_DEBUG);
			return 'admin_modules';
		}
		return $request['configure_module'];
	}
	else
		return 'admin_modules';
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_modules($request)
{
	// check for new settings by looping through the request 
	//  and if there is a variable that has a setting function validate and save it
	$settings_changed = false;
	$new_settings = array();
	foreach($request as $setting => $value)
	{
		// check that the input is in fact and attempted setting
		if(substr($setting, 0, 8) == 'setting_')
		{
			$new_settings[substr($setting, 8)] = $value;
		}
	}

	foreach($new_settings as $setting => $value)
	{
		// validate the attempted setting
		if(function_exists('setting_' . $setting))
			$new_setting = call_user_func_array('setting_' . $setting, array(array_merge($GLOBALS['settings'], $new_settings)));
		elseif(isset($GLOBALS['setting_' . $setting]) && is_callable($GLOBALS['setting_' . $setting]))
			$new_setting = $GLOBALS['setting_' . $setting](array_merge($GLOBALS['settings'], $new_settings));
		else
			PEAR::raiseError('Setting_ function for \'' . $setting . '\' does not exist!', E_DEBUG);
			
		// make sure the new setting is different from the current setting
		if(isset($new_setting) && $new_setting != setting($setting))
		{
			$GLOBALS['settings'][$setting] = $new_setting;
			$settings_changed = true;
		}
	}

	if($settings_changed == true)
	{
		// if we are using a database store the settings in the administrators profile
		if(setting_use_database())
		{
		}
		
		// if the settings file is writable, put the new setting in it
		if(setting('writable_settings_file'))
		{
			PEAR::raiseError('The settings file is writeable!', E_DEBUG|E_WARN);
			
			$defaults = settings_get_defaults(array());
			
			$fh = fopen(setting('settings_file'), 'w');
			
			if($fh !== false)
			{
				
				// only write the settings that are not the default
				foreach($GLOBALS['settings'] as $setting => $value)
				{
					if(setting($setting) != $defaults['settings'])
					{
						fwrite($fh, $setting . ' = ' . setting($setting) . "\n");
					}
				}
				
				PEAR::raiseError('The settings have been saved', E_NOTE);
				
				fclose($fh);
			}
			else
			{
				PEAR::raiseError('There was a problem with saving the settings in the settings file.', E_USER);
			}
		}
		else
		{
			PEAR::raiseError('Cannot save settings, the settings file is not writable!', E_USER);
		}
	}
	
	// output error if can't write to settings
	if(dependency('writable_settings_file') == false)
		PEAR::raiseError('Cannot save changes made on this page, the settings file is not writable!', E_USER);
	
	// get which module to ouput the configuration for
	$request['configure_module'] = validate_configure_module($request);
	
	if(function_exists('configure_' . $request['configure_module']))
	{
		// output configuration page
		$options = call_user_func_array('configure_' . $request['configure_module'], array($GLOBALS['settings']));
		
		// find invalid parameters
		if(isset($GLOBALS['modules'][$request['configure_module']]))
		{
			$missing_settings = array_diff($GLOBALS['modules'][$request['configure_module']]['settings'], array_keys($options));
			$in_settings_not_in_config = array_intersect($missing_settings, $GLOBALS['modules'][$request['configure_module']]['settings']);
		}
		else
		{
			$missing_settings = array_diff($GLOBALS['handlers'][$request['configure_module']]['settings'], array_keys($options));
			$in_settings_not_in_config = array_intersect($missing_settings, $GLOBALS['handlers'][$request['configure_module']]['settings']);
		}
		
		// print out errors for incorrect configuration
		$in_config_not_in_settings = array_intersect($missing_settings, array_keys($options));
		foreach($in_settings_not_in_config as $i => $key)
		{
			PEAR::raiseError('Option \'' . $key . '\' listed in settings for ' . $request['configure_module'] . ' but not listed in the output options configuration!', E_DEBUG);
		}
		foreach($in_config_not_in_settings as $i => $key)
		{
			PEAR::raiseError('Option \'' . $key . '\' listed in the output options for ' . $request['configure_module'] . ' but not listed in the module config!', E_DEBUG);
		}
		
		register_output_vars('options', $options);
		register_output_vars('configure_module', $request['configure_module']);
	}
}
