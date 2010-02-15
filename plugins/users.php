<?php

define('USERS_PRIV', 				1);

// add users
// remove users
// view a user profile
// send messages

// load stuff we might need
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if( $_SESSION['privilage'] < USERS_PRIV )
{
	// redirect to login page
	header('Location: ' . HTML_ROOT . 'plugins/login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . USERS_PRIV);
	
	exit();
}

// add category
if(!isset($_REQUEST['users']))
	$_REQUEST['users'] = 'login';

db_users::handles(LOCAL_USERS);
exit;

// check for what action we should do
if(!isset($_REQUEST['users']))
	$_REQUEST['users'] == 'list'

// validate and display regtistration information
//  also send out registration e-mail here
switch($_REQUEST['users'] == 'register')
{
	// validate input
	if(db_users::handles(LOCAL_USERS . $_REQUEST['username']))
	{
		// make sure the user doesn't already exist
		$db_user = $GLOBALS['database']->query(array(
				'SELECT' => self::DATABASE,
				'WHERE' => 'Username = "' . addslashes($_REQUEST['username']) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_user) > 0 )
		{
			$error = 'User already exists.';
		}
	
		// validate other fields
		//  password
		if(!isset($_REQUEST['password']) || strlen($_REQUEST['password']) < 4 || strlen($_REQUEST['password']) > 16)
		{
			$error = 'Password must be between 4 and 16 characters long.';
		}
		
		// create user folder
		$made = mkdir(LOCAL_USERS . $_REQUEST['username']);
		
		if($made == false)
		{
			$error = 'Cannot create user directory.';
		}
	
		if( $error != '' )
		{
				
			// create database entry
			$user_id = db_users::handle(LOCAL_USERS . $_REQUEST['username']);
			
			// add password and profile information
			
			
			// send out confirmation email
		}
	}
}
// allow a use to remove themselves, administrators may also remove themselves
elseif($_REQUEST['users'] == 'remove')
{
	// delete from database
	// remove from filesystem
	// start new session and logout
}
// a variation of register except users may not change certain properties
elseif($_REQUEST['users'] == 'modify')
{
	// cannot modify their username
}
// cache a users login information so they may access the site
elseif($_REQUEST['users'] == 'login')
{
}
// remove all cookies and session information
elseif($_REQUEST['users'] == 'logout')
{
	// delete current session
	// login cookies become irrelevant
	// create new session
}
// show a list of users, this may have different administrator requirements
elseif($_REQUEST['users'] == 'list')
{
	// possibly belongs under admin
}
// view information about a user
elseif($_REQUEST['users'] == 'view')
{
	// allow users to view their profile
}

// select the user == template first, if it does not exist them it is possible the "Users" template contains a handler for each case
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	// check to see if there is a template for the action
	$template = $GLOBALS['templates']['TEMPLATE_USERS'];
	if(isset($GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['users'])]))
	{
		$template = $GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['users'])];
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