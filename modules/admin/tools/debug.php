<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_debug()
{
	$tools = array(
		'name' => 'Debug Tools',
		'description' => 'Tools for displaying debug information for modules and the site as a whole.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Remote Proceedure Call',
				'description' => 'Make function calls remotely for running scripts and managing database entries.',
				'privilage' => 10,
				'path' => __FILE__
			),
		)
	);
	
	return $tools;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_debug($request)
{
	$request['subtool'] = validate($request, 'subtool');
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

