<?php

// a simple login script for the admin section

// Variables Used:
//  username
// Shared Variables:
$no_setup = true;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

session_start();

// process post variables
if(isset($_POST['password']))
{
	$_SESSION['password'] = md5(DB_SECRET . $_POST['password']);
}
if(isset($_POST['username']))
{
	$_SESSION['username'] = $_POST['username'];
}

// finally run setup since required session information for logging in a user is set up
setup();

$error = '';

if( $_SESSION['loggedin'] == true )
{
	if( isset($_REQUEST['return']) && (!isset($_REQUEST['required_priv']) || $_SESSION['privilage'] >= $_REQUEST['required_priv']))
	{
		header('Location: ' . $_REQUEST['return']);
		exit();
	}
	elseif(!isset($_REQUEST['required_priv']))
	{
		$error = 'Already logged in.';
	}
	elseif(intval($_REQUEST['required_priv']) > intval($_SESSION['privilage']))
	{
		$error = 'You do not have the required privilages to access this page.';
	}
}
else
{
	$error = 'You must enter a username and password to access this section.';
}

// assign variables for a smarty template to use
if(isset($_REQUEST['username']))
	$GLOBALS['smarty']->assign('username', $_REQUEST['username']);

$GLOBALS['smarty']->assign('error', $error);

// show login template
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	// check to see if there is a template for the action
	if(isset($GLOBALS['templates']['TEMPLATE_LOGIN']))
	{
		$template = $GLOBALS['templates']['TEMPLATE_LOGIN'];
	}
	else
	{
		$template = $GLOBALS['templates']['TEMPLATE_USERS'];
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
