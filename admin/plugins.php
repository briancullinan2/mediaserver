<?php

function register_admin_plugins()
{
	return array(
		'name' => 'Admin Plugins',
		'description' => 'Display a list of plugins and allow for enabling and disabling.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

function output_admin_plugins($request)
{
	register_output_vars('plugins', $GLOBALS['plugins']);
}
