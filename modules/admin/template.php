<?php

function register_admin_template()
{
	return array(
		'name' => 'Admin Templates',
		'description' => 'Display information about templates and provide options for enabling and disabling templates.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

function output_admin_template($request)
{

}
