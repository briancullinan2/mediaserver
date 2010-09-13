<?php

/**
 * add users
 * remove users
 * view a user profile
 * send messages
 */
 
define('PASSWORD_COMPLEXITY', '/\A(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])\S{6,}\z/');

function register_users()
{
	return array(
		'name' => 'Users',
		'description' => 'Allows for managing and displaying users.',
		'privilage' => 1,
		'session' => array('username'),
		'settings' => array('local_users', 'username_validation', 'admin_password', 'secret'),
		'depends on' => 'users',
		'filter' => array('user'),
		'internal' => true,
		'template' => true,
		'package' => 'core',
		'database' => array(
			'Username' => 'TEXT',
			'Password' => 'TEXT',
			'Email' => 'TEXT',
			'Settings' => 'TEXT',
			'Privilage' => 'INT',
			'PrivateKey' => 'TEXT',
			'LastLogin' => 'DATETIME',
			'Filepath' => 'TEXT',
		),
	);
}

function install_users()
{
	install_table('users', $GLOBALS['modules']['users']['database']);

	// add default users
	$db_user = db_assoc('SELECT * FROM users WHERE Username = "guest" LIMIT 1');
	
	if( count($db_user) == 0 )
	{
		// create guest user
		$fileinfo = array(
			'id' => -2,
			'Username' => 'guest',
			'Password' => '',
			'Email' => 'guest@bjcullinan.com',
			'Settings' => serialize(array()),
			'Privilage' => 1,
			'PrivateKey' => md5(microtime())
		);
		
		$result = db_insert('INSERT INTO users ' . sql_insert($fileinfo), $fileinfo);
	}
	
	$db_user = db_assoc('SELECT * FROM users WHERE Username = "admin" LIMIT 1');
	
	if( count($db_user) == 0 )
	{
		$fileinfo = array(
			'id' => -1,
			'Username' => 'admin',
			'Password' => md5(setting('secret') . 'tmppass'),
			'Email' => 'admin@bjcullinan.com',
			'Settings' => serialize(array()),
			'Privilage' => 10,
			'PrivateKey' => md5(microtime()),
		);
		// create default administrator
		$result = db_insert('INSERT INTO users ' . sql_insert($fileinfo), $fileinfo);
	}
}

function menu_users()
{
	return array(
		'users/%users' => array(
			'callback' => 'output_users',
		),
	);
}

/**
 * Set up the current user and get their settings from the database
 * @ingroup setup
 */
function setup_users()
{
	$session_user = session('users');
	if(!isset($session_user))
	{
		// prepare the session that stores user information
		$session_user = array(
			'Username' => 'guest',
			'Privilage' => 1
		);
		session('users', $session_user);
	}

	// this will hold a cached list of the users that were looked up
	$GLOBALS['user_cache'] = array();
	
	// get users associated with the keys
	if(isset($session_user['Settings']['keys']))
	{
		$return = db_assoc('SELECT * FROM users WHERE PrivateKey=' . 
			implode(' OR PrivateKey=', array_fill(0, count($session_user['Settings']['keys']), '?')) . 
			' LIMIT ' . count($session_user['Settings']['keys'])
		, $session_user['Settings']['keys']);
		
		$session_user['Settings']['keys_usernames'] = array();
		foreach($return as $index => $user)
		{
			$session_user['Settings']['keys_usernames'][] = $user['Username'];
			
			unset($return[$index]['Password']);
		}
		
		$session_user['Settings']['keys_users'] = $return;
		
		// save the session user after retrieving keys
		session('users', $session_user);
	}
	
	// output the user so template can print out login or logout stuff
	register_output_vars('user', $session_user);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_users($settings)
{
	if(setting_installed() == false || setting('database_enable') == false)
		return array('template');
	else
		return array('template', 'database');
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_users()
{
	$status = array();

	if(dependency('database'))
	{
		$status['users'] = array(
			'name' => lang('users status title', 'Users'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('users status description', 'User functionality is supported because the database is properly installed.'),
				),
			),
			'value' => array(
				'text' => array(
					'User functionality available',
				),
			),
		);
	}
	else
	{
		$status['users'] = array(
			'name' => lang('users status title', 'Users'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('users status fail description', 'User functionality is disabled because the database is not configured.'),
				),
			),
			'value' => array(
				'text' => array(
					'User functionality disabled',
				),
			),
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 */
function configure_users($settings, $request)
{
	$settings['local_users'] = setting('local_users');
	$settings['username_validation'] = setting('username_validation');
	
	$options = array();
	
	if(is_writable($settings['local_users']))
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

	$options['username_validation'] = array(
		'name' => 'Username Validation',
		'status' => '',
		'description' => array(
			'list' => array(
				'This option allows you to customize the regular expression that accepts usernames in the registration.',
				'This is usefull if you want your usernames to be in a specific format when users register.'
			),
		),
		'type' => 'text',
		'value' => $settings['username_validation'],
	);
	
	return array('users' => array(
		'name' => 'User File Settings',
		'type' => 'fieldset',
		'options' => $options
	));
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return A 'users' directory withing the site root
 */
function setting_local_users($settings)
{
	$settings['local_root'] = setting('local_root');
	
	if(isset($settings['local_users']) && is_dir($settings['local_users']))
		return $settings['local_users'];
	else
	{
		if(setting('system_type') == 'nix')
			return '/home/';
		elseif(setting('system_type') == 'mac')
			return '/Users/';
		elseif(file_exists(realpath('/') . 'Users'))
			return realpath('/') . 'Users' . DIRECTORY_SEPARATOR;
		else
			return realpath('/') . 'Documents and Settings' . DIRECTORY_SEPARATOR;
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_username_validation($settings)
{
	$settings['username_validation'] = generic_validate_regexp($settings, 'username_validation');
	if(isset($settings['username_validation']))
		return $settings['username_validation'];
	else
		return '/[a-z][a-z0-9]{4}[a-z0-9]*/i';
}

function setting_secret($settings)
{
	if(isset($settings['secret']) && preg_match(PASSWORD_COMPLEXITY, $settings['secret']) != 0)
		return $settings['secret'];
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_admin_password($settings)
{
	if(isset($settings['admin_password']))
	{
		if(substr($settings['admin_password'], 0, 5) == 'hash:')
			return generic_validate_base64($settings, 'admin_password');
			
		elseif(preg_match(PASSWORD_COMPLEXITY, $settings['admin_password']) != 0)
			return 'hash:' . md5(setting('secret') . $settings['admin_password']);
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default
 */
function validate_users($request)
{
	if(isset($request['users']) && in_array($request['users'], array('register', 'remove', 'modify', 'login', 'logout', 'list', 'view')))
		return $request['users'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return an MD5 has of the setting('db_secret') prepended to the inputted password, it can never be decoded or displayed
 */
function validate_password($request)
{
	// if the request method is a get then the password must be base64 encoded
	if(isset($request['password']) && strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' && substr($request['password'], 0, 5) != 'hash:')
	{
		// if the previous conditions are not met, then flip the fuck out
		raise_error('Password not properly encoded, referrer is not this site!', E_DEBUG|E_USER|E_FATAL);
		
		return '';
	}

	if(isset($request['password']))
	{
		if(substr($request['password'], 0, 5) == 'hash:')
			return $request['password'];
		else
			return 'hash:' . md5(setting('secret') . $request['password']);
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any e-mail address
 */
function validate_email($request)
{
	return generic_validate_email($request, 'email');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any Username
 */
function validate_username($request)
{
	if(isset($request['username']))
	{
		// if a file is passed in get the username from the path
		if(substr($request['username'], 0, strlen(setting('local_users'))) == setting('local_users'))
		{
			$start = strlen(setting('local_users'));
			$end =  strpos($request['username'], '/', $start);
			
			// remove rest of path
			if($end !== false)
				$request['username'] = substr($request['username'], $start, $end);
			else
				$request['username'] = basename($request['username']);
		}
		
		// a little lax here because it will run through the username validator when registering
		if(preg_match(setting('username_validation'), $request['username']) != 0)
			return $request['username'];
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any valid return path to the site
 */
function validate_return($request)
{
	return generic_validate_url($request, 'return');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return (Optional) NULL by default, any number indicated what permission level is required to access a particular module
 */
function validate_required_priv($request)
{
	if(isset($request['required_priv']) && is_numeric($request['required_priv']) && $request['required_priv'] >= 0 && $request['required_priv'] <= 10)
		return $request['required_priv'];
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_users($file)
{
	$file = str_replace('\\', '/', $file);
	
	// handle directories found in the setting('local_users') directory
	//  automatically create a user entry in the database for those directories
	$username = validate(array('username' => $file), 'username');
	if(isset($username))
		return true;
	else	
		return false;
}

/** 
 * Implementation of handle
 * @ingroup handle
 */
function add_users($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	if(handles($file, 'users'))
	{
		$username = validate(array('username' => $file), 'username');
		
		// check if it is in the database
		$db_user = db_assoc('SELECT * FROM users WHERE Username=? LIMIT 1', array($username));
		
		if( count($db_user) == 0 )
		{
			// just set up the user with default information
			//   if they don't use the module, this creates a system user
			return db_insert('INSERT INTO users (Username, Settings, Privilage, PrivateKey) VALUES ("?", "?", "?", "?")', array(
				$username,
				serialize(array()),
				1,
				md5(microtime()),
			));
		}
		elseif($force)
		{
			// not really anything to do here
		}
	}
	
	return false;
}

function filter_users($request)
{
	if(isset($request['user']))
	{
		// do not show any user files by default, then allow otherwise
		$sql = 'LEFT(Filepath, ' . strlen(setting('local_users')) . ') != "' . addslashes(setting('local_users')) . '" OR ' . 
			// allow the user directory to be shown
			'Filepath = "' . addslashes(setting('local_users')) . '" OR ' . 
			// allow folders to be seen in user directory
			'(LEFT(Filepath, ' . strlen(setting('local_users')) . ') = "' . addslashes(setting('local_users')) . '" AND LOCATE("/", Filepath, ' . (strlen(setting('local_users')) + 1) . ') = LENGTH(Filepath)) OR ' . 
			// show currently logged in user files
			'LEFT(Filepath, ' . strlen(setting('local_users') . $request['user']['Username'] . '/') . ') = "' . addslashes(setting('local_users') . $request['user']['Username'] . '/') . '" OR ' . 
			// show all public user files
			'SUBSTR(Filepath, ' . strlen(setting('local_users')) . ' + LOCATE("/", SUBSTR(Filepath, ' . (strlen(setting('local_users')) + 1) . ')), 8) = "/public/"';
		
		// show user files for keys that current user has access to
		if(isset($request['user']['Settings']['keys_usernames']) && count($request['user']['Settings']['keys_usernames']) > 0)
		{
			
			foreach($request['user']['Settings']['keys_usernames'] as $i => $username)
			{
				$where_security .= ' OR LEFT(Filepath, ' . strlen(setting('local_users') . $username . '/') . ') = "' . addslashes(setting('local_users') . $username . '/') . '"';
			}
		}
	
		return array('where' => $sql);
	}
}

/** 
 * Implementation of remove_handler
 * @ingroup remove_handler
 */
function remove_users($file)
{
}

/** 
 * Implementation of cleanup_handler
 * @ingroup cleanup_handler
 */
function cleanup_users()
{
}


/**
 * Implementation of session
 * @ingroup session
 * @return the username and password for user validation and reference
 */
function session_users($request)
{
	// validate username and password
	$request['username'] = validate($request, 'username');

	// check if user is logged in
	if( isset($request['username']) && isset($request['password']) && setting_installed() && setting('database_enable') )
	{
		// lookup username in table
		$db_user = db_assoc('SELECT * FROM users WHERE Username=? AND Password=? LIMIT 1', array(
			$request['username'],
			substr($request['password'], 5),
		));
		
		if( !empty($db_user) )
		{
			// just incase a template wants to access the rest of the information; include the user
			unset($db_user[0]['Password']);
			
			$save = $db_user[0];
			
			// deserialize settings
			$save['Settings'] = unserialize($save['Settings']);
		}
		else
			raise_error('Invalid username or password.', E_USER);
	}
	// use guest information
	elseif(setting_installed() && setting('database_enable'))
	{
		// get guest user
		$db_user = db_assoc('SELECT * FROM users WHERE id = -2 LIMIT 1');
		
		if(is_array($db_user) && count($db_user) > 0)
		{
			// just incase a template wants to access the rest of the information; include the user
			unset($db_user[0]['Password']);
			
			$save = $db_user[0];
			$save['Settings'] = unserialize($save['Settings']);
		}
	}
	// use admin account defined in settings
	elseif( isset($request['username']) && isset($request['password']) )
	{
		if($request['username'] == 'admin')
		{
			if(substr($request['password'], 5) == substr(setting('admin_password'), 5))
			{
				$save = array(
					'id' => '-1',
					'Username' => 'admin',
					'Privilage' => 10,
				);
			}
			else
				raise_error('Invalid password.', E_USER);
		}
		else
			raise_error('Invalid username.', E_USER);
	}
	
	// if the save variable hasn't been set yet, use quest account
	if(isset($save))
	{
		// output the user so template can print out login or logout stuff
		register_output_vars('user', $save);
		
		return $save;
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_users($request)
{
	// check for what action we should do
	$request['users'] = validate($request, 'users');
	$request['username'] = validate($request, 'username');
	$request['email'] = validate($request, 'email');
	$request['password'] = validate($request, 'password');
	$request['return'] = validate($request, 'return');
	
	// validate and display regtistration information
	//  also send out registration e-mail here
	switch($request['users'])
	{
		case 'register':
			
			// validate input
			if(handles(setting('local_users') . $request['username'], 'db_users'))
			{
				// make sure the user doesn't already exist
				$db_user = db_assoc('SELECT * FROM users WHERE Username=? LIMIT 1', array($request['username']));
				
				if( count($db_user) > 0 )
					raise_error('User already exists.', E_USER);
			
				// validate other fields
				if(!isset($request['password']) || preg_match(PASSWORD_COMPLEXITY, $request['password']) == 0)
					raise_error('Password not complex enough.', E_USER);
				
				// validate email
				if(!isset($request['email']))
					raise_error('Invalid E-mail address.', E_USER);
				
				// create user folders
				if(!file_exists(setting('local_users') . $request['username']))
				{
					$made = @mkdir(setting('local_users') . $request['username']);
				
					if($made == false)
					{
						raise_error('Cannot create user directory.', E_USER);
					}
				}
			
				@mkdir(setting('local_users') . $request['username'] . DIRECTORY_SEPARATOR . 'public');
				@mkdir(setting('local_users') . $request['username'] . DIRECTORY_SEPARATOR . 'private');
			
				if( count($GLOBALS['user_errors']) == 0 )
				{
					// create database entry
					$user_id = add_users(setting('local_users') . $request['username']);
					
					// add password and profile information
					$result = db_query('UPDATE users SET Password=?, Email=? WHERE id=?', array(
						$request['password'],
						$request['email'],
						$user_id,
					));
					
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
			$user = session('users');
			$request['required_priv'] = validate($request, 'required_priv');
			if( $user['Username'] != 'guest' )
			{
				if( isset($request['return']) && (!isset($request['required_priv']) || $user['Privilage'] >= $request['required_priv']))
				{
					goto($request['return']);
				}
				else
					raise_error('Already logged in!', E_USER);
			}
			if(isset($request['required_priv']) && $request['required_priv'] > $user['Privilage'])
			{
				raise_error('You do not have sufficient privilages to view this page!', E_USER);
			}
		break;
		// remove all cookies and session information
		case 'logout':
			// delete current session
			session_destroy();
			
			// login cookies become irrelevant
			$_COOKIE = array();
			
			// create new session
			session_start();
			
			if( isset($request['return']))
				goto($request['return']);
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
