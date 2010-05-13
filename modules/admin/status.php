<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_status()
{
	return array(
		'name' => lang('status title', 'Site Status'),
		'description' => lang('status description', 'View the site status reported by all the modules.'),
		'privilage' => 10,
		'path' => __FILE__,
	);
}

/**
 * Implementation of output
 * Display the entire site status
 * @ingroup output
 */
function output_admin_status($request)
{
	$status = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// skip the modules that don't depend on anything
		if(!isset($config['depends on']))
			continue;
			
		// output configuration page
		$module_status = call_user_func_array('status_' . $module, array($GLOBALS['settings']));
		
		if(!is_array($module_status))
		{
			PEAR::raiseError('Something has gone terribly wrong while trying to retrieve the status for \'' . $module . '\'!', E_DEBUG);
			
			// really not much we can do, people might suck at implementing status_
			continue;
		}

		// print out missing keys from the system
		$missing_dependencies = array_diff($config['depends on'], array_keys($module_status));
		$in_depends_not_in_config = array_intersect($missing_dependencies, $config['depends on']);
		$in_config_not_in_depends = array_intersect($missing_dependencies, array_keys($module_status));
		foreach($in_depends_not_in_config as $i => $key)
		{
			PEAR::raiseError('Dependency \'' . $key . '\' listed in dependencies for ' . $module . ' but not listed in the output status configuration!', E_DEBUG);
		}
		foreach($in_config_not_in_depends as $i => $key)
		{
			PEAR::raiseError('Dependency \'' . $key . '\' listed in the output status for ' . $module . ' but not listed in the module dependencies!', E_DEBUG);
		}
		
		// error on interference in keys
		$key_interference = array_intersect($status, $module_status);
		foreach($key_interference as $i => $key)
		{
			PEAR::raiseError('Duplicate status listing in ' . $module . '!', E_DEBUG);
		}
		
		// merge keys to output
		$status = array_merge($status, $module_status);
	}
	
	register_output_vars('status', $status);
}