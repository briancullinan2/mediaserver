<?php


/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_upgrade()
{
	return array(
		'name' => lang('upgrade title', 'Upgrade'),
		'description' => lang('upgrade description', 'Upgrade the system from a previous version.'),
		'privilage' => 10,
		'path' => __FILE__,
		'notemplate' => true,
	);
}


