<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_modules()
{
	return array(
		'name' => 'Modules',
		'description' => 'Display a list of modules and allow for enabling and disabling.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

/**
 * Used to configure plugins
 * @ingroup validate
 * @return NULL by default, accepts any module name that is configurable
 */
function validate_configure_module($request)
{
	if(isset($request['configure_module']) && isset($GLOBALS['modules'][$request['configure_module']]) &&
		isset($GLOBALS['modules'][$request['configure_module']]['configurable']) && count($GLOBALS['modules'][$request['configure_module']]['configurable']) > 0
		)
		return $request['configure_module'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_modules($request)
{
	$request['configure_module'] = validate_configure_module($request);
	
	if(isset($request['configure_module']) && function_exists('configure_' . $request['configure_module']))
	{
		$options = call_user_func_array('configure_' . $request['configure_module'], array($request));
		
		register_output_vars('options', $options);
		register_output_vars('configure_module', $request['configure_module']);
	}
}
