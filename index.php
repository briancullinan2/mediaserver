<?php
// attempt to pass all requests to the appropriate modules

// load template
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// check if it is being run from the command line and display useful setting information
if(isset($argv) && isset($argv[1]) && $argv[1] == '-conf')
{
	$_REQUEST = array(
		'module' => 'admin',
		'get_settings' => true,
	);
}

bootstrap('full');

// check if module exists
if(isset($GLOBALS['modules'][$_REQUEST['module']]))
{
	// check if the current user has access to the module

	// make sure user is logged in
	$user = session('users');
	if(setting_installed() && isset($GLOBALS['modules'][$_REQUEST['module']]['privilage']) && $user['Privilage'] < $GLOBALS['modules'][$_REQUEST['module']]['privilage'])
	{
		// redirect to login page
		goto(array(
			'module' => 'users',
			'users' => 'login',
			'return' => urlencode(url($_GET, true)),
			'required_priv' => $GLOBALS['modules'][$_REQUEST['module']]['privilage']
		));
	}

	// output the module
	output($_REQUEST);
}

// save the errors in the session until they can be printed out
session('errors', array(
	'user' => $GLOBALS['user_errors'],
	'warn' => $GLOBALS['warn_errors'],
	'debug' => $GLOBALS['debug_errors'],
	'note' => $GLOBALS['note_errors'],
));
