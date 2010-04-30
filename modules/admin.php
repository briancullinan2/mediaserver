<?php


/**
 * Implementation of register
 * @ingroup register
 */
function register_admin()
{
	return array(
		'name' => 'Administration',
		'description' => 'Basic instructions for getting started with the system.',
		'privilage' => 10,
		'path' => __FILE__,
		'modules' => setup_register_modules('modules' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR)
	);
}

/**
 * Implementation of output
 * Simple wrapper to provide menu items to the administrative modules
 * @ingroup output
 */
function output_admin($request)
{
}