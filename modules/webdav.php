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

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_pear_webdav()
{
	if(dependency('pear_installed') != false && include_path('HTTP/WebDAV/Server.php') !== false)
		return true;
	else
		return false;
}

/**
 * Implementation of setup
 * @ingroup setup
 */
function setup_webdav()
{
	include_once "HTTP/WebDAV/Server.php";
	
	$GLOBALS['webdav'] = new WebDAV_Server_Monolith();
}

/**
 * Implentation of output
 * @ingroup output
 */
function output_webdav($request)
{
	$server->ServeRequest(dirname(__FILE__) . "/data");
}