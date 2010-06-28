<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_tv_metadata()
{
	$tools = array(
		'name' => 'Television Organizer',
		'description' => 'Download show listings and show metadata from TTVDB and rename files on disk.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('myepisodes'),
		'template' => false,
	);
	
	return $tools;
}
