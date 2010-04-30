<?php


/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_upgrade()
{
	return array(
		'name' => 'Upgrade',
		'description' => 'Upgrade the system from a previous version.',
		'privilage' => 10,
		'path' => __FILE__,
		'notemplate' => true,
	);
}


