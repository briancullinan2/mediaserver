<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_webdav()
{
	return array(
		'name' => lang('webdav title', 'WebDav Interface'),
		'description' => lang('webdav description', 'Allow users to access files through a WebDav client.'),
		'privilage' => 5,
		'path' => __FILE__,
		'settings' => array(),
		'depends on' => array('pear', 'pear_webdav'),
	);
}
