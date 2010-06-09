<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_sync()
{
	$tools = array(
		'name' => 'Synchronize',
		'description' => 'Use the functionality below to synchronize with another media server.',
		'privilage' => 10,
		'path' => __FILE__,
	);
	
	return $tools;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_sync($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

