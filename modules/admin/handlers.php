<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_handlers()
{
	return array(
		'name' => lang('handlers title', 'File Handlers'),
		'description' => lang('handlers description', 'Display a list of file handlers and allow for enabling and disabling.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => 'admin_handlers',
	);
}


/**
 * Implementation of setup
 * Scan handlers directory and load all of the handlers that handle files
 * @ingroup setup
 */
function setup_admin_handlers()
{
	// include the handlers
	$files = get_files(array('dir' => setting_local_root() . 'handlers' . DIRECTORY_SEPARATOR, 'limit' => 32000), $count, true);
	foreach($files as $i => $file)
	{
		// get file name
		$class_name = basename($file['Filepath']);
		
		// remove extension
		$class_name = substr($class_name, 0, strrpos($class_name, '.'));
		
		// include all the handlers
		include_once $file['Filepath'];
		
		// call register function
		if(function_exists('register_' . $class_name))
		{
			$GLOBALS['handlers'][$class_name] = call_user_func_array('register_' . $class_name, array());
		}
		
		// create a streamer if it is one
		if(isset($GLOBALS['handlers'][$class_name]['streamer']))
			stream_wrapper_register($GLOBALS['handlers'][$class_name]['streamer'], $GLOBALS['handlers'][$class_name]['streamer']);
	}
	
	// add any handlers that come with modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['database']))
		{
			// add the module to the handlers list
			$handler = array();
			$handler['database'] = $config['database'];
			$handler['name'] = $config['name'];
			$handler['description'] = $config['description'];
			if(isset($config['internal'])) $handler['internal'] = $config['internal'];
			if(isset($config['settings'])) $handler['settings'] = $config['settings'];
			if(isset($config['depends on'])) $handler['depends on'] = $config['depends on'];
			
			$GLOBALS['handlers'][$module] = $handler;
		}
	}
	
	// reorganize handlers to reflect heirarchy
	$GLOBALS['handlers'] = array_merge(array_flip(flatten_handler_dependencies(array_keys($GLOBALS['handlers']))), $GLOBALS['handlers']);
	
	// always make the handler list available to templates
	register_output_vars('handlers', $GLOBALS['handlers']);
	
	// always make the column list available to templates
	register_output_vars('columns', getAllColumns());
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_admin_handlers()
{
	$settings = array();
	
	// create additional functions for handling enabling
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		// do not allow for enabling of internal handlers
		if(is_internal($handler))
			continue;

		// add the functions to allow for enabling of handlers
		$settings[] = $handler . '_enable';
		$GLOBALS['setting_' . $handler . '_enable'] = create_function('$request', 'return setting_handler_enable($request, \'' . $handler . '\');');
	}
	
	return $settings;
}

/**
 * Creates a list of handlers with the order of their dependencies first
 * @param handlers the list of handlers to recursively loop through
 * @return an array of handlers sorted by dependency
 */
function flatten_handler_dependencies($handlers)
{
	$new_handlers = array();
	foreach($handlers as $i => $handler)
	{
		if(isset($GLOBALS['handlers'][$handler]['wrapper']))
		{
			$new_handlers = array_merge($new_handlers, flatten_handler_dependencies(array($GLOBALS['handlers'][$handler]['wrapper'])));
		}
		$new_handlers[] = $handler;
	}
	return array_values(array_unique($new_handlers));
}

/**
 * Implementation of setting, validate all handler_enable settings
 * @ingroup setting
 */
function setting_handler_enable($settings, $handler)
{
	// always enable the requred handlers
	if(in_array($handler, get_required_handlers()))
		return true;
	
	// parse boolean value
	if(isset($settings[$handler . '_enable']))
	{
		if($settings[$handler . '_enable'] === false || $settings[$handler . '_enable'] === 'false')
			return false;
		elseif($settings[$handler . '_enable'] === true || $settings[$handler . '_enable'] === 'true')
			return true;
	}
	return true;
}

/**
 * Helper function
 */
function get_required_handlers()
{
	return array('files');
}

/**
 * Implementation of status, notify the user that the database is not function and therefor this module is disabled
 * @ingroup status
 */
function status_admin_handlers($settings)
{
	$options = array();

	if(dependency('database'))
	{
		$status['database'] = array(
			'name' => lang('database status title', 'Database Status'),
			'status' => '',
			'value' => 'Database configured!',
			'description' => array(
				'list' => array(
					lang('database status description', 'The database is installed properly, therefore, handlers can be configured and used for reading files.'),
				),
			),
		);
	}
	else
	{
		$status['database'] = array(
			'name' => lang('database status title', 'Database Status'),
			'status' => 'fail',
			'value' => 'Configure the database!',
			'description' => array(
				'list' => array(
					lang('database status fail description', 'The database has encountered dependency issues, file handlers can not be configured until the database is properly set up.'),
				),
			),
			// provide a link to the database configureation page?
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_handlers($settings, $request)
{
	$recommended = array('audio', 'image', 'video');
	
	$options = array();
	
	$description_fail = lang('handlers enable fail description', 'This handler has been forcefully disabled because of dependency issues.');
	
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		// get the enabled setting
		$settings[$handler . '_enable'] = setting_module_enable($settings, $handler);
		
		// set up config for this module
		$options[$handler . '_enable'] = array(
			'name' => $GLOBALS['handlers'][$handler]['name'],
			'status' => '',
			'description' => array(
				'list' => array(
					'Choose whether or not to select the ' . $handler . ' handler.',
				),
			),
			'type' => 'set',
			'options' => array(
				$handler . '_enable' => array(
					'type' => 'boolean',
					'value' => in_array($handler, get_required_handlers())?true:$settings[$handler . '_enable'],
				),
			),
		);
		
		// add some extra info for failed dependencies
		if(dependency($handler) == false)
		{
			$options[$handler . '_enable']['status'] = 'fail';
			if(!in_array($handler, get_required_handlers())) $options[$handler . '_enable']['description']['list'][] = $description_fail;
			$options[$handler . '_enable']['disabled'] = true;
		}
		
		// set up the options for the handlers based on required or recommended lists
		if(in_array($handler, get_required_handlers()) || is_internal($handler))
		{
			$options[$handler . '_enable']['options'][$handler . '_enable']['options'] = array(
				'Enabled (Required)',
			);
			$options[$handler . '_enable']['options'][$handler . '_enable']['disabled'] = true;
		}
		else
		{
			$options[$handler . '_enable']['options'][$handler . '_enable']['options'] = array(
				'Enabled ' . (in_array($handler, $recommended)?'(Recommended)':'(Optional)'),
				'Disabled',
			);
		}
		
		// add configure button
		if(function_exists('configure_' . $handler))
		{
			$options[$handler . '_enable']['options'][] = array(
				'type' => 'button',
				'action' => 'window.location.href=\'' . url('module=admin_modules&configure_module=' . $handler) . '\'',
				'value' => 'Configure',
			);
		}
		
		// add dependency info
		if(isset($GLOBALS['handlers'][$handler]['depends on']))
		{
			// get dependencies
			if(is_string($GLOBALS['handlers'][$handler]['depends on']) && $GLOBALS['handlers'][$handler]['depends on'] == $handler &&
				function_exists('dependency_' . $handler)
			)
				$depends_on = call_user_func_array('dependency_' . $handler, array($GLOBALS['settings']));
			else
				$depends_on = $GLOBALS['handlers'][$handler]['depends on'];
				
			// add to options
			$options[$handler . '_enable']['options'][] = array(
				'value' => '<br />Depends on:<br />' . (is_array($depends_on)?implode(', ', $depends_on):'Failed to retrieve dependencies!'),
			);
		}
	}
	
	return $options;
}

