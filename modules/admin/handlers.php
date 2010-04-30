<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_handlers()
{
	// create additional functions
	$handler_func = array();
	foreach($GLOBALS['handlers'] as $i => $handler)
	{
		if(constant($handler . '::INTERNAL') == true)
			continue;

		$handler_func[] = $handler . '_enable';
		$GLOBALS['setting_' . $handler . '_enable'] = create_function('$request', 'return setting_handler_enable($request, \'' . $handler . '\');');
	}
	
	return array(
		'name' => 'File Handlers',
		'description' => 'Display a list of file handlers and allow for enabling and disabling.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => $handler_func,
	);
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
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_handlers($settings)
{
	$recommended = array('db_audio', 'db_image', 'db_video');
	$required = array('db_file');
	
	$options = array();
	
	foreach($GLOBALS['handlers'] as $i => $handler)
	{
		if(constant($handler . '::INTERNAL') == true)
			continue;
			
		$settings[$handler . '_enable'] = setting_module_enable($settings, $handler);
		$options[$handler . '_enable'] = array(
			'name' => constant($handler . '::NAME'),
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

