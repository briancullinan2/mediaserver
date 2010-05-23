<?php


/**
 * Implementation of register
 * @ingroup register
 */
function register_admin()
{
	return array(
		'name' => lang('admin title', 'Administration'),
		'description' => lang('admin description', 'Basic instructions for getting started with the system.'),
		'privilage' => 10,
		'path' => __FILE__,
		'modules' => setup_register_modules('modules' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR)
	);
}

/**
 * Implementation of output
 * Simple wrapper to provide menu items to the administrative modules
 * @ingroup output
 */
function output_admin($request)
{
	// check for dependency problems so we can show an error
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// skip the modules that don't depend on anything
		if(!isset($config['depends on']))
			continue;
			
		// output configuration page
		if(function_exists('status_' . $module))
		{
			$module_status = call_user_func_array('status_' . $module, array($GLOBALS['settings']));
			
			if(is_array($module_status))
			{
				foreach($module_status as $key => $status)
				{
					if(isset($status['status']) && $status['status'] == 'fail')
					{
						// all it takes is one
						PEAR::raiseError('There is an error in the site status!', E_USER);
						
						return;
					}
				}
			}
		}
	}
}