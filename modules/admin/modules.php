<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_modules()
{
	return array(
		'name' => 'Modules',
		'description' => 'Display a list of modules and allow for enabling and disabling.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_modules($request)
{
}
