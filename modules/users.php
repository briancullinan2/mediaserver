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
function setup_users()
{
	$session_user = session('users');
	// prepare the session that stores user information
	if($session_user == false && !isset($_REQUEST['username']))
		$_REQUEST['username'] = 'guest';

	// this will hold a cached list of the users that were looked up
	$GLOBALS['user_cache'] = array();
	
	// get users associated with the keys
	if(isset($session_user['Settings']['keys']))
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'PrivateKey = "' . join('" OR PrivateKey = "', $session_user['Settings']['keys']) . '"',
				'LIMIT' => count($session_user['Settings']['keys'])
			)
		, false);
		
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
	if(isset($session_user)) register_output_vars('user', $session_user);
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
		'settings' => array('local_users', 'username_validation'),
		'depends on' => 'users',
		'database' => array(
			'Username' 		=> 'TEXT',
			'Password' 		=> 'TEXT',
			'Email' 		=> 'TEXT',
			'Settings' 		=> 'TEXT',
			'Privilage'		=> 'INT',
			'PrivateKey'	=> 'TEXT',
			'LastLogin'		=> 'DATETIME',
			'Filepath' 		=> 'TEXT',
		),
		'internal' => true,
		'template' => true,
	);
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
	$settings['local_users'] = setting_local_users($settings);
	$settings['username_validation'] = setting_username_validation($settings);
	
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
 * Implementation of setting
 * @ingroup setting
 */
function setting_username_validation($settings)
{
	if(isset($settings['username_validation']) && preg_match($settings['username_validation'], md5(microtime())) !== false)
		return $settings['username_validation'];
	else
		return '/[a-z][a-z0-9]{4}[a-z0-9]*/i';
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
	if(isset($request['password']) && strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' && ($request['password'] = base64_decode($request['password'], true)) === false)
	{
		// if the previous conditions are not met, then flip the fuck out
		PEAR::raiseError('Password not properly encoded, referrer is not this site!', E_DEBUG|E_USER|E_FATAL);
		
		return array();
	}

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
	return generic_validate_email($request, 'email');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return Any Username
 */
function validate_username($request)
{
	// a little lax here because it will run through the username validator when registering
	return generic_validate_all_safe($request, 'username');
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
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	// handle directories found in the setting('local_users') directory
	//  automatically create a user entry in the database for those directories
	// extract username from path
	if(substr($file, 0, strlen(setting('local_users'))) == setting('local_users'))
	{
		$file = substr($file, strlen(setting('local_users')));
		
		// remove rest of path
		if(strpos($file, '/') !== false)
			$file = substr($file, 0, strpos($file, '/'));
			
		if(preg_match(setting('username_validation'), basename($file)) > 0)
		{
			return true;
		}
	}
	elseif(dirname($file) == '')
	{
		if(preg_match(setting('username_validation'), basename($file)) > 0)
		{
			return true;
		}
	}
	
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
		$username = basename($file);
		
		// check if it is in the database
		$db_user = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'Username = "' . addslashes($username) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_user) == 0 )
		{
			// just set up the user with default information
			//   if they don't use the module, this creates a system user
			return $GLOBALS['database']->query(array('INSERT' => 'users', 'VALUES' => array(
						'Username' => addslashes($username),
						'Password' => '',
						'Email' => '',
						'Settings' => serialize(array()),
						'Privilage' => 1,
						'PrivateKey' => md5(microtime())
					)
				)
			, false);
		}
		elseif($force)
		{
			// not really anything to do here
		}
	}
	
	return false;
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_users($request, &$count, $files = array())
{
	if(count($files) > 0 && !isset($request['selected']))
	{
		$users = array();
		
		// get a list of users to look up
		foreach($files as $index => $file)
		{
			if(handles($file['Filepath'], 'users'))
			{
				// replace virtual paths
				$path = str_replace('\\', '/', $file['Filepath']);
				if(setting('admin_alias_enable') == true) $path = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $path);
				
				if(substr($path, 0, strlen(setting('local_users'))) == setting('local_users'))
				{
					$path = substr($path, strlen(setting('local_users')));
					
					// remove rest of path
					if(strpos($path, '/') !== false)
						$path = substr($path, 0, strpos($path, '/'));
				}
				
				// add to list of users to look up
				$files[$index]['Username'] = $path;
				if(!isset($GLOBALS['user_cache'][$path]))
					$users[] = $path;
			}
		}
		$users = array_unique($users);
		
		// perform query to get all the needed users
		if(count($users) > 0)
		{
			$return = $GLOBALS['database']->query(array(
					'SELECT' => 'users',
					'WHERE' => 'Username = "' . join('" OR Username = "', $users) . '"',
					'LIMIT' => count($users)
				)
			, false);
			
			// replace get for easy lookup
			foreach($return as $i => $user)
			{
				$GLOBALS['user_cache'][$user['Username']] = $user;
			}
			
			// merge user information to each file
			foreach($files as $index => $file)
			{
				if(isset($file['Username']))
					$files[$index] = array_merge($GLOBALS['user_cache'][$file['Username']], $files[$index]);
			}
		}
		
	}
	elseif(isset($request['file']))
	{
		// change some of the default request variables
		$request['order_by'] = 'Username';
		$request['limit'] = 1;
		
		// modify the file variable to use username instead
		$file = str_replace('\\', '/', $request['file']);
		if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		if(substr($file, 0, strlen(setting('local_users'))) == setting('local_users'))
		{
			$file = substr($file, strlen(setting('local_users')));
			
			// remove rest of path
			if(strpos($file, '/') !== false)
				$file = substr($file, 0, strpos($file, '/'));
		}
		
		$request['search_Username'] = '=' . $file . '=';
		
		// unset the fields that aren't needed
		unset($request['file']);
		unset($request['files_id']);
		
		// extract user directory from path
		// add a users information to each file
		if(isset($file) && isset($GLOBALS['user_cache']))
		{
			$files = array(0 => $GLOBALS['user_cache']);
		}
		else
		{
			$files = get_db_file($request, $count, 'db_users');
			
			if(isset($file))
				$GLOBALS['user_cache'][$file] = $files[0];
		}
	}

	// remove restricted variables
	foreach($files as $i => $file)
	{
		unset($files[$i]['Password']);
	}

	return $files;
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
		$db_user = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'Username = "' . addslashes($request['username']) . '"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_user) > 0 )
		{
			if($request['password'] == $db_user[0]['Password'])
			{
				// just incase a template wants to access the rest of the information; include the user
				unset($db_user[0]['Password']);
				
				$save = $db_user[0];
				
				// deserialize settings
				$save['Settings'] = unserialize($save['Settings']);
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
	elseif(setting_installed() && setting('database_enable'))
	{
		// get guest user
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
			
			$save = $db_user[0];
			$save['Settings'] = unserialize($save['Settings']);
		}
	}
	
	// if the save variable hasn't been set yet, use quest account
	if(!isset($save))
	{
		$save = array(
			'Username' => 'guest',
			'Privilage' => 1
		);
	}
	
	// output the user so template can print out login or logout stuff
	register_output_vars('user', $save);
	
	return $save;
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
				$db_user = $GLOBALS['database']->query(array(
						'SELECT' => 'users',
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
					$user_id = handle_db_users(setting('local_users') . $request['username']);
					
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
			$user = session('users');
			$request['required_priv'] = validate($request, 'required_priv');
			if( $user['Username'] != 'guest' )
			{
				if( isset($request['return']) && (!isset($request['required_priv']) || $user['Privilage'] >= $request['required_priv']))
				{
					goto($request['return']);
				}
				else
					PEAR::raiseError('Already logged in!', E_USER);
			}
			if(isset($request['required_priv']) && $request['required_priv'] > $user['Privilage'])
			{
				PEAR::raiseError('You do not have sufficient privilages to view this page!', E_USER);
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


function theme_login()
{
	theme('header');
	
	theme('login_block');
	
	theme('footer');
}

function theme_login_block()
{
	?>	
	<form action="<?php echo url('module=users&users=login' . (isset($GLOBALS['templates']['vars']['return'])?('&return=' . urlencode($GLOBALS['templates']['vars']['return'])):'')); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_register()
{
	theme('header');
	
	?>	
	<form action="<?php echo url('module=users&users=register'); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" /><br />
		E-mail: <input type="text" name="email" value="<?php print isset($GLOBALS['templates']['vars']['email'])?$GLOBALS['templates']['vars']['email']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Register" /><input type="reset" value="Reset" />
		
	</form>
	<?php
	
	theme('footer');
}

function theme_confirmation()
{
	theme('header');
	
	?>Thanks for signing up!<?php
	
	theme('footer');
}