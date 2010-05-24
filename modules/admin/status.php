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
		'template' => true,
	);
}

/**
 * Implementation of output
 * Display the entire site status
 * @ingroup output
 */
function output_admin_status($request)
{
	$module_description = lang('module status description', 'This module is available for use.');
	$module_description_fail = lang('module status fail description', 'This module is disabled either due to a manual setting or failed dependencies.');
	
	$status = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// skip the modules that don't depend on anything
		if(!isset($config['depends on']))
			continue;
			
		// output configuration page
		if(function_exists('status_' . $module))
		{
			$module_status = call_user_func_array('status_' . $module, array($GLOBALS['settings']));
			
			// if it is not an array, display debug error
			if(!is_array($module_status))
			{
				PEAR::raiseError('Something has gone terribly wrong while trying to retrieve the status for \'' . $module . '\'!', E_DEBUG);
				
				// really not much we can do, people might suck at implementing status_
				continue;
			}
		}
		else
		{
			// show default status line
			if(dependency($module) != false)
			{
				$module_status[$module] = array(
					'name' => $config['name'],
					'status' => '',
					'description' => array(
						'list' => array(
							$module_description,
							$config['description'],
						),
					),
					'value' => array(
						'text' => array(
							$config['name'] . ' is available for use',
						),
					),
				);
			}
			else
			{
				$module_status[$module] = array(
					'name' => $config['name'],
					'status' => 'fail',
					'description' => array(
						'list' => array(
							$module_description_fail,
							$config['description'],
						),
					),
					'value' => array(
						'text' => array(
							$config['name'] . ' is disabled',
						),
					),
				);
			}
			
			PEAR::raiseError('Status function not defined for \'' . $module . '\', but it has dependencies!', E_DEBUG);
		}
		

		// call the dependency function
		if(is_string($config['depends on']) && $config['depends on'] == $module &&
			function_exists('dependency_' . $config['depends on'])
		)
			$config['depends on'] = call_user_func_array('dependency_' . $module, array($GLOBALS['settings']));

		// print out missing keys from the system
		$missing_dependencies = array_diff($config['depends on'], array_keys($module_status));
		$in_depends_not_in_config = array_intersect($missing_dependencies, $config['depends on']);
		$in_config_not_in_depends = array_intersect($missing_dependencies, array_keys($module_status));
		
		// print out errors
		foreach($in_depends_not_in_config as $i => $key)
		{
			PEAR::raiseError('Dependency \'' . $key . '\' listed in dependencies for ' . $module . ' but not listed in the output status configuration!', E_DEBUG);
		}
		foreach($in_config_not_in_depends as $i => $key)
		{
			PEAR::raiseError('Dependency \'' . $key . '\' listed in the output status for ' . $module . ' but not listed in the module dependencies!', E_DEBUG);
		}
		
		// error on interference in keys
		$key_interference = array_intersect(array_keys($status), array_keys($module_status));
		foreach($key_interference as $i => $key)
		{
			PEAR::raiseError('Duplicate status listing \'' . $key . '\' in ' . $module . '!', E_DEBUG);
		}
		
		// merge keys to output
		$status = array_merge($status, $module_status);
	}
	
	register_output_vars('status', $status);
}