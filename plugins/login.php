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
}
