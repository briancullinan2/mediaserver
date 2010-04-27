<?php

// attempt to pass all requests to the appropriate modules

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	goto(url($_GET, true, true));
}

// check if module exists
if(isset($GLOBALS['modules'][$_REQUEST['module']]))
{
	// check if the current user has access to the module

	// make sure user is logged in
	if( isset($GLOBALS['modules'][$_REQUEST['module']]['privilage']) && $_SESSION['user']['Privilage'] < $GLOBALS['modules'][$_REQUEST['module']]['privilage'] )
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
