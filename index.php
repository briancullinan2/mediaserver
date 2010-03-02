<?php

// attempt to pass all requests to the appropriate plugins

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	header('Location: ' . generate_href(array()));
	exit();
}

// check if plugin exists
if(isset($GLOBALS['plugins'][$_REQUEST['plugin']]))
{
	// check if the current user has access to the plugin

	// make sure user in logged in
	if( isset($GLOBALS['plugins'][$_REQUEST['plugin']]['privilage']) && $_SESSION['privilage'] < $GLOBALS['plugins'][$_REQUEST['plugin']]['privilage'] )
	{
		// redirect to login page
		header('Location: ' . generate_href(array('plugin' => 'login', 'return' => $_REQUEST['plugin'], 'required_priv' => $GLOBALS['plugins'][$_REQUEST['plugin']]['privilage']), '', '', '', '', '', true));
		
		exit();
	}
	
	// output plugin
	call_user_func_array('output_' . $_REQUEST['plugin'], array($_REQUEST));

	// set debug errors
	register_output_vars('debug_errors', $GLOBALS['debug_errors']);
	
	// filter out user errors for easy access by templates
	register_output_vars('user_errors', $GLOBALS['user_errors']);
	
	// only display a template for the current plugin if there is one
	if(isset($GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['plugin'])]) && 
		(!isset($GLOBALS['plugins'][$_REQUEST['plugin']]['notemplate']) || 
			$GLOBALS['plugins'][$_REQUEST['plugin']]['notemplate'] == false))
	{
		$template = $GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['plugin'])];
		// select template for the current plugin
		if(getExt($template) == 'php')
		{
			set_output_vars(false);
			@include $template;
		}
		else
		{
			set_output_vars(true);
			$GLOBALS['smarty']->display($template);
		}
	}
}

?>
