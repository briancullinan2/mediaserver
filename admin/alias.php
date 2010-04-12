<?php

/**
 * @ingroup register
 * Implementation of register_plugin
 */
function register_admin_alias()
{
	return array(
		'name' => 'Aliasing',
		'description' => 'Alias the paths from the filesystem to display as differen/less complicated paths when shown to the users.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

/**
 * Implementation of output_plugin
 * @param request The request passed in from the GLOBAL validated request
 * @return nothing, just prepare the variables needed to use in the template
 */
function output_admin_alias($request)
{
	// nothing to do here yet
}