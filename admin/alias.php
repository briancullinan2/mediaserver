<?php

function register_admin_alias()
{
	return array(
		'name' => 'Aliasing',
		'description' => 'Alias the paths from the filesystem to display as differen/less complicated paths when shown to the users.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

function output_admin_alias($request)
{
	// nothing to do here yet
}