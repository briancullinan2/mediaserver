<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_bulk_rename()
{
	$tools = array(
		'name' => 'Bulk Rename Utility',
		'description' => 'Rename many files at once.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array(),
		'template' => false,
	);
	
	return $tools;
}
