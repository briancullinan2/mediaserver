<?php

// attempt to pass all requests to the appropriate plugins

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	header('Location: ' . generate_href($_GET, true, true));
	exit();
}

// check if plugin exists
if(isset($GLOBALS['plugins'][$_REQUEST['plugin']]))
{
	// check if the current user has access to the plugin

	// make sure user is logged in
	if( isset($GLOBALS['plugins'][$_REQUEST['plugin']]['privilage']) && $_SESSION['user']['Privilage'] < $GLOBALS['plugins'][$_REQUEST['plugin']]['privilage'] )
	{
		// redirect to login page
		header('Location: ' . generate_href(array('plugin' => 'users', 'users' => 'login', 'return' => urlencode(generate_href($_GET, true)), 'required_priv' => $GLOBALS['plugins'][$_REQUEST['plugin']]['privilage']), true));
		
		exit();
	}
	
	$plugin = $_REQUEST['plugin'];
	
	// output plugin
	call_user_func_array('output_' . $_REQUEST['plugin'], array($_REQUEST));
	
	// only display a template for the current plugin if there is one
	if(!isset($GLOBALS['plugins'][$plugin]['notemplate']) || 
			$GLOBALS['plugins'][$plugin]['notemplate'] == false
		)
	{
		theme();
		
		theme('errors');
	}
}

?>
