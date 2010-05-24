<?php

// attempt to pass all requests to the appropriate modules

// load template
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// check if it is being run from the command line and display useful setting information
if(isset($argv) && isset($argv[1]) && $argv[1] == '-conf')
{
	foreach($GLOBALS['settings'] as $key => $value)
	{
		print $key . ' = ' . $value . "\n";
	}
	exit;
}

// check if module exists
if(isset($GLOBALS['modules'][$_REQUEST['module']]))
{
	// check if the current user has access to the module

	// make sure user is logged in
	if(setting_installed() && isset($GLOBALS['modules'][$_REQUEST['module']]['privilage']) && $_SESSION['users']['Privilage'] < $GLOBALS['modules'][$_REQUEST['module']]['privilage'])
	{
		// redirect to login page
		goto(array(
			'module' => 'users',
			'users' => 'login',
			'return' => urlencode(url($_GET, true)),
			'required_priv' => $GLOBALS['modules'][$_REQUEST['module']]['privilage']
		));
		
		exit();
	}
	
	// output the module
	output($_REQUEST);
}

// save the errors in the session until they can be printed out
$_SESSION['errors']['user'] = $GLOBALS['user_errors'];
$_SESSION['errors']['warn'] = $GLOBALS['warn_errors'];
$_SESSION['errors']['debug'] = $GLOBALS['debug_errors'];
$_SESSION['errors']['note'] = $GLOBALS['note_errors'];
