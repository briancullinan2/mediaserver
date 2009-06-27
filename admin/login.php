<?php

// a simple login script for the admin section

// Variables Used:
//  username
// Shared Variables:

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

$error = '';

if( loggedIn() )
{
	if( isset($_REQUEST['return']) )
	{
		header('Location: ' . $_REQUEST['return']);
		exit();
	}
		
	$error = 'Already logged in as admin.';
}
else
{

	if( isset($_REQUEST['username']) && isset($_REQUEST['password']) )
	{
		if( $_REQUEST['username'] == ADMIN_USER && $_REQUEST['password'] == ADMIN_PASS )
		{
			$_SESSION['username'] = $_REQUEST['username'];
			$_SESSION['password'] = $_REQUEST['password'];
			
			if( isset($_REQUEST['return']) )
			{
				header('Location: ' . $_REQUEST['return']);
				exit();
			}
			
			$error = 'Already logged in as admin.';
		}
		else
		{
			$error = 'Wrong username or password.';
		}
	}
	else
	{
		$error = 'You must enter a username and password to access this section.';
	}
	
}

// assign variables for a smarty template to use
$GLOBALS['smarty']->assign('username', $_REQUEST['username']);

$GLOBALS['smarty']->assign('error', $error);

// show login template
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	// check to see if there is a template for the action
	$template = $GLOBALS['templates']['TEMPLATE_USERS'];
	if(isset($GLOBALS['templates']['TEMPLATE_LOGIN']))
	{
		$template = $GLOBALS['templates']['TEMPLATE_LOGIN'];
	}
	
	// if not use the default users template
	if(getExt($template) == 'php')
		@include $template;
	else
	{
		header('Content-Type: ' . getMime($template));
		$GLOBALS['smarty']->display($template);
	}
}


?>
