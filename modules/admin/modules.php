<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_modules()
{
	$module_func = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$module_func[] = $module . '_enable';
		$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return setting_module_enable($request, \'' . $module . '\');');
	}
	
	return array(
		'name' => lang('modules title', 'Configure Modules'),
		'description' => lang('modules description', 'Display a list of modules and allow for enabling and disabling.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => $module_func,
	);
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
	$required = array('core', 'index', 'login');
	
	$options = array();
	
	$required = lang('modules enable option 1', 'Enabled (Required)');
	$recommend = lang('modules enable option 2', 'Enabled (Recommended)');
	$optional = lang('modules enable option 3', 'Enabled (Optional)');
	$disabled = lang('modules enable option 4', 'Disabled');
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$settings[$module . '_enable'] = setting_module_enable($settings, $module);
		$options[$module . '_enable'] = array(
			'name' => $GLOBALS['modules'][$module]['name'],
			'status' => '',
			'description' => array(
				'list' => array(
					$GLOBALS['modules'][$module]['description'],
					lang('modules enable description 1', 'Choose whether or not to enable the ' . $GLOBALS['modules'][$module]['name'] . ' module.'),
					lang('modules enable description 2', 'Click configure to configure additional options for a specific module.'),
				),
			),
			'type' => 'boolean',
			'value' => in_array($module, $required)?true:$settings[$module . '_enable'],
		);
		
		if(in_array($module, $required))
		{
			$options[$module . '_enable']['options'] = array(
				$required,
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
		return $request['configure_module'];
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
		
		// make sure the new setting is different from the current setting
		if($new_setting != $GLOBALS['settings'][$setting])
		{
			$GLOBALS['settings'][$setting] = $new_setting;
			$settings_changed = true;
		}
	}

	if($settings_changed == true)
	{
		// if we are using a database store the settings in the administrators profile
		if(setting('use_database'))
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
					if($GLOBALS['settings'][$setting] != $defaults['settings'])
					{
						fwrite($fh, $setting . ' = ' . $GLOBALS['settings'][$setting] . "\n");
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
	
	// get which module to ouput the configuration for
	$request['configure_module'] = validate_configure_module($request);
	
	if(function_exists('configure_' . $request['configure_module']))
	{
		// output configuration page
		$options = call_user_func_array('configure_' . $request['configure_module'], array($GLOBALS['settings']));

		$diff = array_diff($GLOBALS['modules'][$request['configure_module']]['settings'], array_keys($options));
		foreach($diff as $i => $key)
		{
			PEAR::raiseError('Option \'' . $key . '\' listed in configure_' . $request['configure_module'] . ' but not listed in the configuration for the module!', E_DEBUG);
		}
		
		register_output_vars('options', $options);
		register_output_vars('configure_module', $request['configure_module']);
	}
}
