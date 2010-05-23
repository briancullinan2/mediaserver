<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_mail()
{
	$tools = array(
		'name' => 'Mailing',
		'description' => 'Send out messages to site users notifying them of changes on the site.',
		'privilage' => 10,
		'path' => __FILE__,
	);
	
	return $tools;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_mail($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

