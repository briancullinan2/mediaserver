<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_tag_rename()
{
	$tools = array(
		'name' => 'Tag and Rename',
		'description' => 'Rename music files using their ID3 tags or retag music files.',
		'privilage' => 10,
		'path' => __FILE__,
	);
	
	return $tools;
}

