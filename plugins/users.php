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
if(isset($_REQUEST['users']))
{
	if($_REQUEST['users'] == 'add')
	{
		// validate input
		if(db_users::handles(LOCAL_USERS . $_REQUEST['username']))
		{
			// create user folder
			$made = mkdir(LOCAL_USERS . $_REQUEST['username']);
			
			if($made == false)
			{
				$error = 'Cannot create user directory.';
			}
			else
			{
					
				// create database entry
				db_users::handle(LOCAL_USERS . $_REQUEST['username']);
				
				// add password and profile information
				
				// send out confirmation email
			}
		}
	}
	elseif($_REQUEST['users'] == 'remove')
	{
	}
}


?>