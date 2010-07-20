<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_modules()
{
	return array(
		'name' => lang('modules title', 'Modules'),
		'description' => lang('modules description', 'Display a list of modules and allow for enabling and disabling.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => 'admin_modules',
		'depends on' => array('admin', 'admin_tools'),
		'template' => true,
	);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_admin_modules()
{
	$settings = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$settings[] = $module . '_enable';
		if(!function_exists('setting_' . $module . '_enable'))
		{
			if(!in_array($module, get_required_modules()))
				$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return setting_module_enable($request, \'' . $module . '\');');
			else
				$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return true;');
		}
	}
	
	return $settings;
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
	return is_writable(settings_file($settings));
}

/**
 * Implementation of dependencies
 */
function status_admin_modules($settings)
{
	$status = array();
	
	// settings permission
	if(dependency('writable_settings_file'))
	{
		$status['writable_settings_file'] = array(
			'name' => lang('settings access title', 'Access to Settings'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('settings access description', 'The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.'),
				),
			),
			'type' => 'text',
			'disabled' => true,
			'value' => settings_file(),
		);
	}
	else
	{
		$status['writable_settings_file'] = array(
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
			'value' => settings_file(),
		);
	}
	
	return $status;
}

/**
 * Implementation of setting, validate all module_enable settings
 * @ingroup setting
 */
function setting_module_enable($settings, $module)
{
	// always return true if module is required
	if(in_array($module, get_required_modules()))
		return true;
		
	// check boolean value
	return generic_validate_boolean_true($settings, $module . '_enable');
}

/**
 * Helper function
 */
function get_required_modules()
{
	return array('core', 'index', 'users', 'select', 'settings', 'file');
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_modules($settings, $request)
{
	$recommended = array('select', 'list', 'search');
	
	$options = array();
	
	$required_str = lang('modules enable option 1', 'Enabled (Required)');
	$recommend = lang('modules enable option 2', 'Enabled (Recommended)');
	$optional = lang('modules enable option 3', 'Enabled (Optional)');
	$disabled = lang('modules enable option 4', 'Disabled');
	$description_1 = lang('modules enable description 1', 'Choose whether or not to enable the module.');
	$description_2 = lang('modules enable description 2', 'Click configure to configure additional options for a specific module.');
	$description_fail = lang('modules enable fail description', 'This module has been forcefully disabled because of dependency issues.');
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// get the enabled setting
		$settings[$module . '_enable'] = setting_module_enable($settings, $module);
		
		// set up config for this module
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
			'type' => 'set',
			'options' => array(
				$module . '_enable' => array(
					'type' => 'boolean',
					'value' => in_array($module, get_required_modules())?true:$settings[$module . '_enable'],
				),
			)
		);
		
		// add some extra info for failed dependencies
		if(dependency($module) == false)
		{
			$options[$module . '_enable']['status'] = 'fail';
			if(!in_array($module, get_required_modules())) $options[$module . '_enable']['description']['list'][] = $description_fail;
			$options[$module . '_enable']['disabled'] = true;
		}
		
		// set up the options for the modules based on required or recommended lists
		if(in_array($module, get_required_modules()))
		{
			$options[$module . '_enable']['options'][$module . '_enable']['options'] = array(
				$required_str,
			);
			$options[$module . '_enable']['options'][$module . '_enable']['disabled'] = true;
		}
		else
		{
			$options[$module . '_enable']['options'][$module . '_enable']['options'] = array(
				(in_array($module, $recommended)?$recommend:$optional),
				$disabled,
			);
		}
		
		// add configure button
		if(function_exists('configure_' . $module))
		{
			$options[$module . '_enable']['options'][] = array(
				'type' => 'button',
				'action' => 'window.location.href=\'' . url('module=admin_modules&configure_module=' . $module) . '\'',
				'value' => 'Configure',
			);
		}
		
		// add dependency info
		if(isset($GLOBALS['modules'][$module]['depends on']))
		{
			// get dependencies
			if(is_string($GLOBALS['modules'][$module]['depends on']) && $GLOBALS['modules'][$module]['depends on'] == $module &&
				function_exists('dependency_' . $module)
			)
				$depends_on = call_user_func_array('dependency_' . $module, array($GLOBALS['settings']));
			else
				$depends_on = $GLOBALS['modules'][$module]['depends on'];
				
			// add to options
			$options[$module . '_enable']['options'][] = array(
				'value' => '<br />Depends on:<br />' . (is_array($depends_on)?implode(', ', $depends_on):'Failed to retrieve dependencies!'),
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
 * Implementation of validate
 * @ingroup validate
 */
function validate_save_configuration($request)
{
	if(isset($request['save_configuration']))
		return true;
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_reset_configuration($request)
{
	if(isset($request['reset_configuration']))
		return true;
}

/**
 * Helper function for saving settings
 */
function modules_save_settings($settings, $force_file = false)
{
	// get default settings so they are not included in the save
	$defaults = settings_get_defaults(array());

	// only write the settings that are not the default
	$new_settings = array();
	foreach($settings as $setting => $value)
	{
		if(!isset($defaults[$setting]) || $value != $defaults[$setting])
		{
			$new_settings[$setting] = $value;
		}
	}

	// if we are using a database store the settings in the administrators profile
	if(dependency('database') && !$force_file)
	{
		$result = _modules_save_db_settings($new_settings);
		
		if($result)
			PEAR::raiseError('The settings have been saved', E_NOTE);
		else
			PEAR::raiseError('There was a problem while saving the settings to the database!', E_USER);
	}
	else
	{
		$result = _modules_save_fs_settings($new_settings);
		
		if($result === -1)
			PEAR::raiseError('Cannot save settings, the settings file is not writable!', E_USER);
		if($result === true)
			PEAR::raiseError('The settings have been saved', E_NOTE);
		else
			PEAR::raiseError('There was a problem with saving the settings in the settings file.', E_USER);
	}
}

/**
 * Helper function for converting an array of settings to a string
 */
function _modules_settings_to_string($settings)
{
	$new_settings = '';
		
	// do the non array settings first
	foreach($settings as $setting => $value)
	{
		if(!is_array($value))
		{
			if(is_string($value))
				$new_settings .= $setting . ' = "' . urlencode($value) . "\"\n";
			elseif(is_bool($value))
				$new_settings .= $setting . ' = "' . (($value)?'true':'false') . "\"\n";
			else
				$new_settings .= $setting . ' = "' . $value . "\"\n";
		}
	}
	
	// now list the array settings
	foreach($settings as $setting => $value)
	{
		if(is_array($value))
		{
			$new_settings .= "\n[" . $setting . "]\n";
			foreach($value as $subsetting => $subvalue)
			{
				$new_settings .= $subsetting . ' = "' . urlencode($subvalue) . "\"\n";
			}
		}
	}
	
	return $new_settings;
}

/**
 * Helper function for saving settings to the database
 */
function _modules_save_db_settings($settings)
{
	// store in database
	$result = $GLOBALS['database']->query(array(
			'UPDATE' => 'users',
			'WHERE' => 'id = -1',
			'VALUES' => array(
				'Settings' => addslashes(serialize($settings)),
			),
		)
	, false);

	return ($result !== false);
}

/**
 * Helper function for saving settings to the filesystem
 */
function _modules_save_fs_settings($settings)
{
	// if the settings file is writable, put the new setting in it
	if(dependency('writable_settings_file'))
	{
		PEAR::raiseError('The settings file is writeable!', E_DEBUG|E_WARN);
		
		// convert settings input to string
		if(is_array($settings))
			$settings = _modules_settings_to_string($settings);
		
		// open settings file for writing
		$fh = fopen(settings_file(), 'w');
	
		if($fh !== false)
		{
			fwrite($fh, ';<?php die(); ?>' . "\n" . $settings);
			fclose($fh);
			
			return true;
		}
		else
			return false;
	}
	else
		return -1;
}

/**
 * Helper function for getting new and changed settings
 */
function modules_get_new_settings($request)
{
	// check for new settings by looping through the request 
	//  and if there is a variable that has a setting function validate and save it
	$settings_changed = false;
	$new_settings = array();
	foreach($request as $setting => $value)
	{
		// check that the input is in fact an attempted setting
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
		if($new_setting != setting($setting))
		{
			$settings_changed = true;
		}
		$GLOBALS['settings'][$setting] = $new_setting;
	}
	
	return $settings_changed;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_modules($request)
{
	// get which module to ouput the configuration for
	$request['configure_module'] = validate($request, 'configure_module');
	$request['save_configuration'] = validate($request, 'save_configuration');
	$request['reset_configuration'] = validate($request, 'reset_configuration');

	if(isset($request['save_configuration']))
	{
		// get changed settings
		$settings_changed = modules_get_new_settings($request);
		
		if($settings_changed == true)
		{
			modules_save_settings($GLOBALS['settings'], true);
		}
	}
	
	// output error if can't write to settings
	if(dependency('writable_settings_file') == false)
		PEAR::raiseError('Cannot save changes made on this page, the settings file is not writable!', E_USER);

	// output configuration page
	$options = call_user_func_array('configure_' . $request['configure_module'], array($GLOBALS['settings'], $request));
	
	// add status to configuration
	if(function_exists('status_' . $request['configure_module']))
	{
		$status = call_user_func_array('status_' . $request['configure_module'], array($GLOBALS['settings']));
		register_output_vars('status', $status);
	}
	
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
