<?php

/**
 * add users
 * remove users
 * view a user profile
 * send messages
 */

/**
 * Set up the current user and get their settings from the database
 * @ingroup setup
 */
function setup_user()
{
	// check if user is logged in
	if( isset($_SESSION['users']['username']) && isset($_SESSION['users']['password']) && setting('use_database') == true )
	{
		// lookup username in table
		$db_user = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'Username = "' . addslashes($_SESSION['users']['username']) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_user) > 0 )
		{
			if($_SESSION['users']['password'] == $db_user[0]['Password'])
			{
				// just incase a template wants to access the rest of the information; include the user
				unset($db_user[0]['Password']);
				
				$_SESSION['user'] = $db_user[0];
				
				// deserialize settings
				$_SESSION['user']['Settings'] = unserialize($_SESSION['user']['Settings']);
			}
			else
			{
				unset($_SESSION['users']);
				PEAR::raiseError('Invalid password.', E_USER);
			}
		}
		else
		{
			unset($_SESSION['users']);
			PEAR::raiseError('Invalid username.', E_USER);
		}
	}
	// use guest information
	elseif(setting('use_database') == true)
	{
		$db_user = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'id = -2',
				'LIMIT' => 1
			)
		, false);
		
		if(is_array($db_user) && count($db_user) > 0)
		{
			// just incase a template wants to access the rest of the information; include the user
			unset($db_user[0]['Password']);
			
			$_SESSION['user'] = $db_user[0];
			$_SESSION['user']['Settings'] = unserialize($_SESSION['user']['Settings']);
		}
		else
		{
			$_SESSION['user'] = array(
				'Username' => 'guest',
				'Privilage' => 1
			);
		}
	}
	else
	{
		$_SESSION['user'] = array(
			'Username' => 'admin',
			'Privilage' => 10
		);
	}
	
	// this will hold a cached list of the users that were looked up
	$GLOBALS['user_cache'] = array();
	
	// get users associated with the keys
	if(isset($_SESSION['user']['Settings']['keys']))
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => db_users::DATABASE,
				'WHERE' => 'PrivateKey = "' . join('" OR PrivateKey = "', $_SESSION['user']['Settings']['keys']) . '"',
				'LIMIT' => count($_SESSION['user']['Settings']['keys'])
			)
		, false);
		
		$_SESSION['user']['Settings']['keys_usernames'] = array();
		foreach($return as $index => $user)
		{
			$_SESSION['user']['Settings']['keys_usernames'][] = $user['Username'];
			
			unset($return[$index]['Password']);
		}
		
		$_SESSION['user']['Settings']['keys_users'] = $return;
	}
}

/**
 * Implementation of register
 * @ingroup register
 */
function register_users()
{
	return array(
		'name' => 'Users',
		'description' => 'Allows for managing and displaying users.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('username'),
		'settings' => array('local_users'),
	);
}

/**
 * Implementation of configure
 */
function configure_users($settings)
{
	$settings['local_users'] = setting_local_users($settings);
	
	$options = array();
	
	if(is_writable($request['local_users']))
	{
		$options['local_users'] = array(
			'name' => 'User Files',
			'status' => '',
			'description' => array(
				'list' => array(
					'This directory will be used for uploaded user files.  This will also be included in the directories that are watched by the server.',
				),
			),
			'type' => 'text',
			'value' => $settings['local_users'],
		);
	}
	else
	{
		$options['local_users'] = array(
			'name' => 'User Files',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that this directory does not exist or is not writable.',
					'Please correct this error by entering a directory path that exists and is writable by the web server',
				),
			),
			'type' => 'text',
			'value' => $settings['local_users'],
		);
	}
	
	return $options;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return A 'users' directory withing the site root
 */
function setting_local_users($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	
	if(isset($settings['local_users']) && is_dir($settings['local_users']))
		return $settings['local_users'];
	else
		return $settings['local_root'] . 'users' . DIRECTORY_SEPARATOR;
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return 'login' by default
 */
function validate_users($request)
{
	if(!isset($request['users']) || !in_array($request['users'], array('register', 'remove', 'modify', 'login', 'logout', 'list', 'view')))
		return 'login';
	else
		return $request['users'];
	
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return an MD5 has of the setting('db_secret') prepended to the inputted password, it can never be decoded or displayed
 */
function validate_password($request)
{
	if(isset($request['password']))
		return md5(setting('db_secret') . $request['password']);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any e-mail address
 */
function validate_email($request)
{
	if(isset($request['email']))
		return $request['email'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any Username
 */
function validate_username($request)
{
	if(isset($request['username']))
		return $request['username'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any valid return path to the site
 */
function validate_return($request)
{
	if(isset($request['return']))
		return $request['return'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return (Optional) NULL by default, any number indicated what permission level is required to access a particular module
 */
function validate_required_priv($request)
{
	if(is_numeric($request['required_priv']) && $request['required_priv'] >= 0 && $request['required_priv'] <= 10)
		return $request['required_priv'];
}

/**
 * Implementation of session
 * @ingroup session
 * @return the username and password for user validation and reference
 */
function session_users($request)
{
	$save = array();
	
	// only save it to the session if the user is logging in
	$request['users'] = validate_users($request);
	if($request['users'] == 'login')
	{
		$save['username'] = $request['username'];
		$save['password'] = @$request['password'];
	}
	
	return $save;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_users($request)
{
	// check for what action we should do
	$request['users'] = validate_users($request);
	$request['username'] = validate_username($request);
	$request['email'] = validate_email($request);
	$request['password'] = validate_password($request);
	$request['return'] = validate_return($request);
	
	// validate and display regtistration information
	//  also send out registration e-mail here
	switch($request['users'])
	{
		case 'register':
			
			// validate input
			if(db_users::handles(setting('local_users') . $request['username']))
			{
				// make sure the user doesn't already exist
				$db_user = $GLOBALS['database']->query(array(
						'SELECT' => db_users::DATABASE,
						'WHERE' => 'Username = "' . addslashes($request['username']) . '"',
						'LIMIT' => 1
					)
				, false);
				
				if( count($db_user) > 0 )
				{
					PEAR::raiseError('User already exists.', E_USER);
				}
			
				// validate other fields
				// validate email
				if(!isset($request['email']) || preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/', $request['email']) === false)
				{
					PEAR::raiseError('Invalid E-mail address.', E_USER);
				}
				
				// create user folders
				if(!file_exists(setting('local_users') . $request['username']))
				{
					$made = @mkdir(setting('local_users') . $request['username']);
				
					if($made == false)
					{
						PEAR::raiseError('Cannot create user directory.', E_USER);
					}
				}
			
				@mkdir(setting('local_users') . $request['username'] . DIRECTORY_SEPARATOR . 'public');
				@mkdir(setting('local_users') . $request['username'] . DIRECTORY_SEPARATOR . 'private');
			
				if( count($GLOBALS['user_errors']) == 0 )
				{
					// create database entry
					$user_id = db_users::handle(setting('local_users') . $request['username']);
					
					// add password and profile information
					$result = $GLOBALS['database']->query(array(
						'UPDATE' => 'users',
						'VALUES' => array(
							'Password' => addslashes($request['password']),
							'Email' => $request['email']
						),
						'WHERE' => 'id=' . $user_id
					), false);
					
					// send out confirmation email
					ob_start();
					theme('confirmation');
					$confirmation = ob_get_contents();
					ob_end_clean();
					
					mail($request['email'], 'E-Mail Confirmation for ' . setting('html_name'), $confirmation);
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
			if( $_SESSION['user']['Username'] != 'guest' )
			{
				if( isset($request['return']) && (!isset($request['required_priv']) || $_SESSION['user']['Privilage'] >= $request['required_priv']))
				{
					goto($request['return']);
				}
				else
					PEAR::raiseError('Already logged in!', E_USER);
			}
			$request['required_priv'] = validate_required_priv($request);
			if(isset($request['required_priv']) && $request['required_priv'] > $_SESSION['user']['Privilage'])
			{
				PEAR::raiseError('You do not have sufficient privilages to view this page!', E_USER);
			}
		break;
		// remove all cookies and session information
		case 'logout':
			// delete current session
			session_destroy();
			
			// login cookies become irrelevant
			
			// create new session
			session_start();
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
	
	register_output_vars('users', $request['users']);
	if(isset($request['username'])) register_output_vars('username', $request['username']);
	if(isset($request['email'])) register_output_vars('email', $request['email']);
	if(isset($request['return'])) register_output_vars('return', $request['return']);
}

