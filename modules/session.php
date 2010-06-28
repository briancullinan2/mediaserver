<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_session()
{
	return array(
		'name' => lang('session title', 'Session Storage'),
		'description' => lang('session description', 'Control all session functionality and provide a database.'),
		'privilage' => 5,
		'path' => __FILE__,
		'settings' => 'session',
		'depends on' => 'session',
	);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_session($settings)
{
	if(setting_installed() == false || setting('database_enable') == false)
		return array();
	else
		return array('database');
}
