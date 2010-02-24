<?php

// a simple login script for the admin section

function register_login()
{
	return array(
		'name' => 'login',
		'description' => 'Allows users to log in to the site and access user files and settings.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('username')
	);
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

function output_login($request)
{
	if( $_SESSION['loggedin'] == true )
	{
		if( isset($_REQUEST['return']) && (!isset($_REQUEST['required_priv']) || $_SESSION['privilage'] >= $_REQUEST['required_priv']))
		{
			header('Location: ' . $_REQUEST['return']);
			exit();
		}
	}
	
	if(isset($request['username']))
		register_output_vars('username', $request['username']);
	else
		register_output_vars('username', '');
}
