<?php

function register_admin_index()
{
	return array(
		'name' => 'Read Me',
		'description' => 'Basic instructions for getting started with the system.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

function output_admin_index($request)
{
	// check for install status?
}