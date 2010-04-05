<?php

function register_admin()
{
	return array(
		'name' => 'Administration',
		'description' => 'Basic instructions for getting started with the system.',
		'privilage' => 1,
		'path' => __FILE__,
		'plugins' => load_plugins('admin' . DIRECTORY_SEPARATOR)
	);
}

function output_admin($request)
{
	$request['admin'] = validate_admin($request);

	// check for install status?
	if(isset($request['admin']))
		register_output_vars('admin', $request['admin']);
}