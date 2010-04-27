<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_template()
{
	return array(
		'name' => 'Templates',
		'description' => 'Display information about templates and provide options for enabling and disabling templates.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_template($request)
{

}
