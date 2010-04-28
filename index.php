<?php

// attempt to pass all requests to the appropriate modules

// load template
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// check if module exists
if(isset($GLOBALS['modules'][$_REQUEST['module']]))
{
	// check if the current user has access to the module

	// make sure user is logged in
	if((!defined('NOT_INSTALLED') || NOT_INSTALLED == false) && isset($GLOBALS['modules'][$_REQUEST['module']]['privilage']) && $_SESSION['user']['Privilage'] < $GLOBALS['modules'][$_REQUEST['module']]['privilage'] )
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

?>
