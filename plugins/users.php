<?php

// add users
// remove users
// view a user profile
// send messages


function setup_users()
{
	// set up user settings
	if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] == false)
	{
		// check if user is logged in
		if( isset($_SESSION['login']['username']) && isset($_SESSION['login']['password']) )
		{
			// lookup username in table
			$db_user = $GLOBALS['database']->query(array(
					'SELECT' => 'users',
					'WHERE' => 'Username = "' . addslashes($_SESSION['login']['username']) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($db_user) > 0 )
			{
				if($_SESSION['login']['password'] == $db_user[0]['Password'])
				{
					$_SESSION['username'] = $_SESSION['login']['username'];
					
					// set up user information in session
					$_SESSION['loggedin'] = true;
					
					// the security level is the most important property
					$_SESSION['privilage'] = $db_user[0]['Privilage'];
					
					// the settings are also very important
					$_SESSION['settings'] = unserialize($db_user[0]['Settings']);
					
					// just incase a template wants to access the rest of the information; include the user
					unset($db_user[0]['Password']);
					unset($db_user[0]['Settings']);
					unset($db_user[0]['Privilage']);
					
					$_SESSION['user'] = $db_user[0];
				}
				else
				{
					PEAR::raiseError('Invalid password.', E_USER);
				}
			}
			else
			{
				PEAR::raiseError('Invalid username.', E_USER);
			}
		}
		// use guest information
		elseif(USE_DATABASE == true)
		{
			$_SESSION['loggedin'] = false;
			
			$db_user = $GLOBALS['database']->query(array(
					'SELECT' => 'users',
					'WHERE' => 'id = -2',
					'LIMIT' => 1
				)
			, false);
			
			if(is_array($db_user) && count($db_user) > 0)
			{
				$_SESSION['username'] = $db_user[0]['Username'];
		
				// the security level is the most important property
				$_SESSION['privilage'] = $db_user[0]['Privilage'];
				
				// the settings are also very important
				$_SESSION['settings'] = unserialize($db_user[0]['Settings']);
				//$_SESSION['settings']['keys'] = array('5a277c44344eaf04e1d92085eabfda02');
				
				// just incase a template wants to access the rest of the information; include the user
				unset($db_user[0]['Password']);
				unset($db_user[0]['Settings']);
				unset($db_user[0]['Privilage']);
				
				$_SESSION['user'] = $db_user[0];
			}
		}
		else
		{
			$_SESSION['username'] = 'guest';
			$_SESSION['privilage'] = 1;
			
		}
	}
	
	// this will hold a cached list of the users that were looked up
	$GLOBALS['user_cache'] = array();
	
	// get users associated with the keys
	if(isset($_SESSION['settings']['keys']))
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => db_users::DATABASE,
				'WHERE' => 'PrivateKey = "' . join('" OR PrivateKey = "', $_SESSION['settings']['keys']) . '"',
				'LIMIT' => count($_SESSION['settings']['keys'])
			)
		, false);
		
		$_SESSION['settings']['keys_usernames'] = array();
		foreach($return as $index => $user)
		{
			$_SESSION['settings']['keys_usernames'][] = $user['Username'];
			
			unset($return[$index]['Password']);
		}
		
		$_SESSION['settings']['keys_users'] = $return;
	}
	
	register_output_vars('loggedin', $_SESSION['loggedin']);
}

function register_users()
{
	return array(
		'name' => 'Users',
		'description' => 'Allows for managing and displaying users.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

function validate_users($request)
{
	if(!isset($request['users']) || !in_array($request['users'], array('register', 'remove', 'modify', 'login', 'logout', 'list', 'view')))
		return 'login';
	else
		return $request['list'];
	
}

function validate_password($request)
{
	return md5(DB_SECRET . $request['password']);
}

function validate_username($request)
{
	return $request['username'];
}

function validate_return($request)
{
	return $request['return'];
}

function validate_required_priv($request)
{
	if(is_numeric($request['required_priv']))
		return $request['required_priv'];
}

function session_login($request)
{
	$save['username'] = $request['username'];
	$save['password'] = @$request['password'];
	
	return $save;
}

function output_users($request)
{
	// check for what action we should do
	$request['users'] = validate_users($request);
	
	// validate and display regtistration information
	//  also send out registration e-mail here
	switch($request['users'])
	{
		case 'register':
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
					PEAR::raiseError('User already exists.', E_USER);
				}
			
				// validate other fields
				//  password
				if(!isset($_REQUEST['password']) || strlen($_REQUEST['password']) < 4 || strlen($_REQUEST['password']) > 16)
				{
					PEAR::raiseError('Password must be between 4 and 16 characters long.', E_USER);
				}
				
				// create user folder
				$made = mkdir(LOCAL_USERS . $_REQUEST['username']);
				
				if($made == false)
				{
					PEAR::raiseError('Cannot create user directory.', E_USER);
				}
			
				if( $error != '' )
				{
						
					// create database entry
					$user_id = db_users::handle(LOCAL_USERS . $_REQUEST['username']);
					
					// add password and profile information
					
					
					// send out confirmation email
				}
			}
		break;
		// allow a user to remove themselves, administrators may also remove themselves
		case 'remove':
			// delete from database
			// remove from filesystem
			// start new session and logout
		break;
		// a variation of register except users may not change certain properties
		case 'modify':
			// cannot modify their username
		break;
		// cache a users login information so they may access the site
		case 'login':
			if( $_SESSION['loggedin'] == true )
			{
				if( isset($request['return']) && (!isset($request['required_priv']) || $_SESSION['privilage'] >= $request['required_priv']))
				{
					header('Location: ' . $request['return']);
					exit();
				}
			}
			
			if(isset($_SESSION['login']['username']))
				register_output_vars('username', $_SESSION['login']['username']);
			else
				register_output_vars('username', '');
	
			if(isset($request['return'])) register_output_vars('return', $request['return']);
		break;
		// remove all cookies and session information
		case 'logout':
			// delete current session
			// login cookies become irrelevant
			// create new session
		break;
		// show a list of users, this may have different administrator requirements
		case 'list':
			// possibly belongs under admin
		break;
		// view information about a user
		case 'view':
			// allow users to view their profile
		break;
	}
		
}

?>