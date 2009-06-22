<?php

// add users
// remove users
// view a user profile
// send messages

// load stuff we might need
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

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
		);
		
		if( count($db_user) > 0 )
		{
			$error = 'User already exists.';
		}
	
		// validate other fields
		//  password
		if(strlen($_REQUEST['password']) < 4 || strlen($_REQUEST['password']) > 16)
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
			db_users::handle(LOCAL_USERS . $_REQUEST['username']);
			
			// add password and profile information
			
			// send out confirmation email
		}
	}
}
// allow a use to remove themselves, administrators may also remove themselves
elseif($_REQUEST['users'] == 'remove')
{
}
// a variation of register except users may not change certain properties
//  like their username
elseif($_REQUEST['users'] == 'modify')
{
}
// cache a users login information so they may access the site
elseif($_REQUEST['users'] == 'login')
{
}
// remove all cookies and session information
elseif($_REQUEST['users'] == 'logout')
{
}
// show a list of users, this may have different administrator requirements
elseif($_REQUEST['users'] == 'list')
{
}
// view information about a user
elseif($_REQUEST['users'] == 'view')
{
}

// select the user == template first, if it does not exist them it is possible the "Users" template contains a handler for each case


?>