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
		'settings' => array(),
		'depends on' => array('settings')
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
	$files = get_filesystem(array('dir' => setting_local_root() . 'handlers' . DIRECTORY_SEPARATOR, 'limit' => 32000), $count);
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
	
	// create additional functions for handling enabling
	$handler_func = array();
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		// do not allow for enabling of internet handlers
		if(is_internal($handler))
			continue;

		// add the functions to allow for enabling of handlers
		$handler_func[] = $handler . '_enable';
		$GLOBALS['setting_' . $handler . '_enable'] = create_function('$request', 'return setting_handler_enable($request, \'' . $handler . '\');');
	}
	
	$GLOBALS['modules']['admin_handlers']['settings'] = $handler_func;
	
	// always make the handler list available to templates
	register_output_vars('handlers', $GLOBALS['handlers']);
	
	// always make the column list available to templates
	register_output_vars('columns', getAllColumns());
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
function configure_admin_handlers($settings)
{
	$recommended = array('db_audio', 'db_image', 'db_video');
	$required = array('db_file');
	
	$options = array();
	
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(is_internal($handler))
			continue;
			
		$settings[$handler . '_enable'] = setting_module_enable($settings, $handler);
		$options[$handler . '_enable'] = array(
			'name' => $GLOBALS['handlers'][$handler]['name'],
			'status' => '',
			'description' => array(
				'list' => array(
					'Choose whether or not to select the ' . $handler . ' handler.',
				),
			),
			'type' => 'boolean',
			'value' => in_array($handler, $required)?true:$settings[$handler . '_enable'],
		);
		
		if(in_array($handler, $required))
		{
			$options[$handler . '_enable']['options'] = array(
				'Enabled (Required)',
			);
			$options[$handler . '_enable']['disabled'] = true;
		}
		else
		{
			$options[$handler . '_enable']['options'] = array(
				'Enabled ' . (in_array($handler, $recommended)?'(Recommended)':'(Optional)'),
				'Disabled',
			);
		}
	}
	
	return $options;
}

