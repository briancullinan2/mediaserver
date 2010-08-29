<?php

function register_module()
{
	return array(
		'name' => 'Modules',
		'description' => 'Interface for managing which modules are enabled.',
		'privilage' => 10,
		'settings' => 'module',
		'template' => true,
		'package' => 'core',
		'output' => 'module_variables'
	);
}


function menu_module()
{
	return array(
		'admin/module/%configure_module' => array(
			'callback' => 'output_module',
		),
		'admin/module' => array(
			'callback' => 'output_module',
			'name' => 'Configure Modules',
		),
	);
}

function setting_module()
{
	$settings = array();
	
	// create functions for checking module_enable
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$settings[] = $module . '_enable';
	}
	
	return $settings;
}



function setup_module()
{
	// create functions for checking module_enable
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// if the module supplys it's own function for checking if it is enabled
		//   this is useful if the enable state depends on more then the dependencies and needs logic to figure it out
		if(!function_exists('setting_' . $module . '_enable'))
		{
			if(!in_array($module, get_required_modules()))
				$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return setting_modules_enable($request, \'' . $module . '\');');
			else
				$GLOBALS['setting_' . $module . '_enable'] = create_function('$request', 'return true;');
		}
	}
}

/**
 * Implementation of setting, validate all module_enable settings
 * @ingroup setting
 */
function setting_modules_enable($settings, $module = 'modules')
{
	// always return true if module is required
	if(in_array($module, get_required_modules()))
		return true;
		
	// check boolean value
	return generic_validate_boolean_true($settings, $module . '_enable');
}

function load_modules($dir)
{
	// merge with global list
	if(!isset($GLOBALS['modules']))
		$GLOBALS['modules'] = array();
		
	$modules = array();
	
	$files = scan_directory($dir, 'is_info', 3);

	foreach($files as $i => $file)
	{
		$config = load_module($file);
		if(isset($config))
			$modules[$config['module']] = $config;
	}
	
	$GLOBALS['modules'] = sort_modules($GLOBALS['modules']);

	return sort_modules($modules);
}

function load_module($path)
{
	if(is_file($path))
	{
		// get filename without extension
		$module = substr(basename($path), 0, strrpos(basename($path), '.'));
		
		// get machine readable name from module
		$module = generic_validate_machine_readable(array('module' => $module), 'module');
		
		// functional prefix so there can be multiple modules with the same name
		if($module == basename(dirname($path)))
			$prefix = substr($path, strlen(setting_local_root()), -(strlen(basename($path)) + strlen($module)));
		else
			$prefix = substr($path, strlen(setting_local_root()), -strlen(basename($path)));
		
		// remove slashes and replace with underscores
		$prefix = generic_validate_machine_readable(array('prefix' => $prefix), 'prefix');
		
		// use output buffer to prevent unwanted output
		ob_start();
		
		// load configuration from info file to allow disabling
		if(ext($path) == 'info' && file_exists(dirname($path) . DIRECTORY_SEPARATOR . $module . '.module'))
		{
			$config = parse_ini_file($path, true);
			
			// point to module path instead
			$path = dirname($path) . DIRECTORY_SEPARATOR . $module . '.module';

			include_once $path;
		}
		// used mostly internally, call register_ function
		elseif(ext($path) == 'module' || ext($path) == 'inc')
		{
			include_once $path;

			if(function_exists('register_' . $module))
				$config = invoke_module('register', $module);
			else
			{
				raise_error('Module \'' . $path . '\' does not contain a register function!', E_DEBUG);
				return;
			}
				
		}
		// return here because there is not configuration for the module
		else
			return;
		
		// check output buffer and report
		$buffer = ob_get_contents();
		if(strlen($buffer) > 0)
			raise_error('Output detected while loading modules \'' . $path . '\'.', E_VERBOSE);
		ob_end_clean();
		
		// set the module name for reference
		$config['module'] = $module;
	
		// set the path to the module
		if(!isset($config['path']))
			$config['path'] = $path;
		
		// set the package if it is not set already
		if(!isset($config['package']))
		{
			if($prefix != '')
				$config['package'] = substr($prefix, 0, -1);
			else
				$config['package'] = 'other';
		}

		// loop through all available triggers and call register
		foreach($GLOBALS['triggers'] as $trigger => $triggers)
		{
			register_trigger($trigger, $config, $module);
		}
		
		$GLOBALS['modules'][$config['module']] = $config;
		
		return $GLOBALS['modules'][$config['module']];
	}
	else
	{
		raise_error('Failed to load \'' . $path . '\'.', E_DEBUG);
		
		return array();
	}
}

function load_include($path)
{
	include_once(include_path($path));
}

function is_module($file)
{
	if(isset($GLOBALS['modules'][$file]))
		return true;
	elseif(handles($file, 'files') && ext($file) == 'module')
		return true;
	else
		return false;
}

function is_info($file)
{
	if(handles($file, 'files') && ext($file) == 'info')
		return true;
	else
		return false;
}

function is_include($file)
{
	if(handles($file, 'files') && ext($file) == 'inc')
		return true;
	else
		return false;
}

function get_required_modules()
{
	return array('menu', 'template', 'select', 'files', 'users', 'session', 'watched', 'module', 'ids', 'settings');
}

function get_dependencies($dependency, $modules_only = false)
{
	$depends = array();
	
	if(isset($GLOBALS['modules'][$dependency]))
	{
		$config = $GLOBALS['modules'][$dependency];
		
		if(isset($config['depends on']))
		{
			// if the depends on field is a string, call the function and use that as the list of dependencies
			if(is_string($config['depends on']) && $config['depends on'] == $dependency)
			{
				if(function_exists('dependency_' . $config['depends on']))
					$depends = invoke_module('dependency', $dependency, array($GLOBALS['settings']));
				else
					raise_error('Function dependency_\'' . $dependency . '\' is specified but the function does not exist.', E_DEBUG);
			}
			// if the depends on is an array pointing to functions
			elseif(is_array($config['depends on']))
				$depends = $config['depends on'];
				
			// filter out modules if option is true
			if($modules_only)
			{
				$depends = array_filter($depends, 'is_module');
			}
		}
	}
	
	return $depends;
}

function get_ordered_dependencies($module, $already_added = array())
{
	$return = array();
	
	if(!in_array($module, $already_added))
	{
		$already_added[] = $module;
		$depends = get_dependencies($module, true);
		foreach($depends as $i => $depend)
		{
			$return = array_merge($return, get_ordered_dependencies($depend, $already_added));
		}
	}
		
	$return[] = $module;
	
	return $return;
}


function sort_modules($modules)
{
	$ordered = array();

	foreach($modules as $module => $config)
	{
		$ordered = array_merge($ordered, get_ordered_dependencies($module));
	}

	// intersect with available module information so as not to create blank entries on merge
	$ordered = array_intersect_key(array_flip(array_unique($ordered)), $modules);

	// merge ordered keys with module configs
	return array_merge($ordered, $modules);
}

/**
 * Function for returning all the modules that match certain criteria
 */
function get_modules($package = NULL)
{
	$modules = array();
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if($package === NULL || $config['package'] == $package)
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for getting all the modules that implement a specified API function
 */
function get_modules_implements($method)
{
	$modules = array();
	
	// check if function exists
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(function_exists($method . '_' . $module))
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for matching properties on a module using regular expression and the serialized function
 */
function get_modules_match($expression)
{
	$modules = array();
	
	// if it is not an object or a string, then serialize it in order to match it against modules
	if(!is_string($expression))
		$expression = '/' . addslashes(serialize($expression)) . '/i';
		
	// make sure it is valid regular expression
	$expression = generic_validate_regexp(array('package' => $expression), 'package');
	
	// if it is valid
	if(isset($expression))
	{
		// loop through all the modules and match expression
		foreach($GLOBALS['modules'] as $module => $config)
		{
			if(preg_match($expression, serialize($config)) != 0)
				$modules[$module] = $config;
		}
	}
	
	return $modules;
}



function invoke_module($method, $module, $args = NULL)
{
	if(function_exists($method . '_' . $module))
	{
		return call_user_func_array($method . '_' . $module, $args);
	}
	else
		raise_error('Invoke \'' . $method . '\' called on \'' . $module . '\' but dependencies not met or function does not exist.', E_VERBOSE);
}


/**
 * Function for invoking an API call on all modules and returning the result
 * @param method Method to call on all modules
 * @param list_return_values list all the return values from each module separately
 * @return true if not listing return values and it succeeded, returns false if any module fails, returns associative array if listing each value
 */
function invoke_all($method)
{
	$args = func_get_args();
	
	// remove method name
	array_shift($args);
	
	raise_error('Modules invoked with \'' . $method . '\'.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if((dependency($module) || in_array($module, get_required_modules())) && function_exists($method . '_' . $module))
		{
			$result = call_user_func_array($method . '_' . $module, $args);
		}
	}
}

function invoke_all_callback($method, $callback)
{
	$args = func_get_args();
	
	// remove method name
	array_shift($args);
	array_shift($args);
	
	raise_error('Modules invoked with \'' . $method . '\' and a callback function supplied.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		unset($result);
		
		// keep track of error count and report which module fired it
		extract(array(
			'user_count' => count($GLOBALS['user_errors']),
			'warn_count' => count($GLOBALS['warn_errors']),
			'note_count' => count($GLOBALS['note_errors'])
		));
		
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if(
			(dependency($module) || in_array($module, get_required_modules())) && 
			function_exists($method . '_' . $module)
		)
		{
			$result = call_user_func_array($method . '_' . $module, $args);
		}
		if(is_callable($callback))
			call_user_func_array($callback, array($module, isset($result)?$result:NULL, $args));
		
		// send debug information to console
		if($user_count < count($GLOBALS['user_errors']))
			raise_error('User error affected by \'' . $module . '\'.', E_DEBUG);
		if($warn_count < count($GLOBALS['warn_errors']))
			raise_error('Warn error affected by \'' . $module . '\'.', E_DEBUG);
		if($note_count < count($GLOBALS['note_errors']))
			raise_error('Note error affected by \'' . $module . '\'.', E_DEBUG);
	}
}
 
function disable_module($module)
{
	if(dependency($module) == false)
	{
		$GLOBALS['settings'][$module . '_enable'] = false;
		// this prevents us from disabling required modules on accident
		$GLOBALS['settings'][$module . '_enable'] = setting($module . '_enable');
	}
}


/**
 * Implementation of dependency
 * @ingroup dependency
 * @true or false if the settings file is writeable, so that this module can write to it
 */
function dependency_writable_settings($settings)
{
	if(setting_installed() == false)
		return false;
	return is_writable(setting('settings_file')) || setting('database_enable') == true;
}

/**
 * Implementation of dependencies
 */
function status_admin_modules($settings)
{
	$status = array();
	
	// settings permission
	if(dependency('writable_settings'))
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
			'value' => setting('settings_file'),
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
			'value' => setting('settings_file'),
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_module($settings, $request)
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
	
	foreach(get_modules() as $module => $config)
	{
		// get the enabled setting
		$settings[$module . '_enable'] = setting_modules_enable($settings, $module);
		
		$key = 'setting_' . $module . '_enable';
			
		// if the package is core and the modules are required put them in a separate fieldset
		if($config['package'] == 'core' && in_array($module, get_required_modules()))
			$config['package'] = 'core_required';
			
		// set up the fieldset for each package
		if(!isset($options[$config['package']]))
		{
			$options[$config['package']] = array(
				'type' => 'fieldset',
				'options' => array(),
				'collapsible' => true,
				'name' => ($config['package'] == 'core_required')
							?
							'Core (Required)'
							:
							(($config['package'] == 'core')
								?
								'Core (Optional)'
								:
								(isset($GLOBALS['modules'][$config['package']])
									?
									$GLOBALS['modules'][$config['package']]['name']
									:
									ucwords(str_replace('_', ' ', $config['package']))
								)
							),
			);
			if($config['package'] == 'core_required')
				$options[$config['package']]['collapsed'] = true;
		}
		
		// set up config for this module
		$new_config = array(
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
				$key => array(
					'type' => 'boolean',
					'value' => in_array($module, get_required_modules())?true:$settings[$module . '_enable'],
				),
			)
		);
		
		// add some extra info for failed dependencies
		if(dependency($module) == false)
		{
			$new_config['status'] = 'fail';
			if(!in_array($module, get_required_modules()))
				$new_config['description']['list'][] = $description_fail;
			$new_config['options'][$key]['disabled'] = true;
		}
		
		// set up the options for the modules based on required or recommended lists
		if(in_array($module, get_required_modules()))
		{
			$new_config['options'][$key]['options'] = array(
				$required_str,
			);
			$new_config['options'][$key]['disabled'] = true;
		}
		else
		{
			$new_config['options'][$key]['options'] = array(
				(in_array($module, $recommended)?$recommend:$optional),
				$disabled,
			);
		}
		
		// add configure button
		if(function_exists('configure_' . $module))
		{
			$new_config['options'][] = array(
				'type' => 'button',
				'action' => 'window.location.href=\'' . url('admin/module/' . $module) . '\'',
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
				$depends_on = invoke_module('dependency', $module, array($GLOBALS['settings']));
			else
				$depends_on = $GLOBALS['modules'][$module]['depends on'];
				
			// add to options
			$new_config['options'][] = array(
				'value' => '<br />Depends on:<br />' . (is_array($depends_on)?implode(', ', $depends_on):'Failed to retrieve dependencies!'),
			);
		}
		
		// add new config to the fieldset
		$options[$config['package']]['options'][$module . '_enable'] = $new_config;
	}

	return $options;
}

/**
 * Used to configure modules
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
			raise_error('Configuration function \'' . $request['configure_module'] . '\' does not exist!', E_DEBUG);
			return 'admin_modules';
		}
		return $request['configure_module'];
	}
	else
		return 'module';
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
function module_save_settings($settings, $force_file = false)
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
		$result = _module_save_db_settings($new_settings);
		
		if($result)
			raise_error('The settings have been saved', E_NOTE);
		else
			raise_error('There was a problem while saving the settings to the database!', E_USER);
	}
	else
	{
		$result = _module_save_fs_settings($new_settings);
		
		if($result === -1)
			raise_error('Cannot save settings, the settings file is not writable!', E_USER);
		if($result === true)
			raise_error('The settings have been saved', E_NOTE);
		else
			raise_error('There was a problem with saving the settings in the settings file.', E_USER);
	}
}

/**
 * Helper function for converting an array of settings to a string
 */
function _module_settings_to_string($settings)
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
function _module_save_db_settings($settings)
{
	// store in database
	$result = db_query('UPDATE users SET Settings=? WHERE id=-1', array(serialize($settings)));

	return ($result !== false);
}

/**
 * Helper function for saving settings to the filesystem
 */
function _module_save_fs_settings($settings)
{
	// if the settings file is writable, put the new setting in it
	if(dependency('writable_settings_file'))
	{
		raise_error('The settings file is writeable!', E_DEBUG|E_WARN);
		
		// convert settings input to string
		if(is_array($settings))
			$settings = _module_settings_to_string($settings);
		
		// open settings file for writing
		$fh = fopen(setting('settings_file'), 'w');
	
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
function module_get_new_settings($request)
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
			raise_error('Setting_ function for \'' . $setting . '\' does not exist!', E_DEBUG);
			
		// make sure the new setting is different from the current setting
		if($new_setting != setting($setting))
		{
			$settings_changed = true;
		}
		$GLOBALS['settings'][$setting] = $new_setting;
	}
	
	return $settings_changed;
}

function module_variables($request)
{
	// always make the column list available to templates
	register_output_vars('columns', get_all_columns());
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_module($request)
{
	// get which module to ouput the configuration for
	$request['configure_module'] = validate($request, 'configure_module');
	$request['save_configuration'] = validate($request, 'save_configuration');
	$request['reset_configuration'] = validate($request, 'reset_configuration');

	if(isset($request['save_configuration']))
	{
		// get changed settings
		$settings_changed = module_get_new_settings($request);
		
		if($settings_changed == true)
		{
			module_save_settings($GLOBALS['settings'], true);
		}
	}
	
	// output error if can't write to settings
	if(dependency('writable_settings') == false)
		raise_error('Cannot save changes made on this page, the settings file is not writable!', E_USER);

	// output configuration page
	$options = invoke_module('configure', $request['configure_module'], array($GLOBALS['settings'], $request));
	
	// add status to configuration
	$status = invoke_module('status', $request['configure_module'], array($GLOBALS['settings']));
	if(isset($status))
		register_output_vars('status', $status);
	
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
		raise_error('Option \'' . $key . '\' listed in settings for ' . $request['configure_module'] . ' but not listed in the output options configuration!', E_VERBOSE);
	}
	foreach($in_config_not_in_settings as $i => $key)
	{
		raise_error('Option \'' . $key . '\' listed in the output options for ' . $request['configure_module'] . ' but not listed in the module config!', E_VERBOSE);
	}
	
	register_output_vars('options', $options);
	register_output_vars('configure_module', $request['configure_module']);
}
