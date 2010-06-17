<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_torservices()
{
	$tools = array(
		'name' => 'Torrent Services',
		'description' => 'Provides configuration for Torrenting websites and torrent files.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('torpath'),
	);
	
	return $tools;
}
