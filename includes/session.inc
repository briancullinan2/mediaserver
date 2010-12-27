<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_session()
{
	return array(
		'name' => 'Session Storage',
		'description' => 'Control all session functionality and provide a database.',
		'privilage' => 5,
		'path' => __FILE__,
		'settings' => 'session',
		'depends_on' => 'session',
		'package' => 'core',
	);
}

function new_session($secure = false, $id = NULL)
{
    session_destroy();

    if(isset($id))
    {
        if(dirname($id) != '' && dirname($id) != '/')
    		session_save_path(dirname($id));
        if(basename($id) != '')
            session_id(basename($id));

    }
    else
        // regenerate session and set secure cookie
        session_regenerate_id(true);

	session_start();
    session('created', time());
    session('remote_ip', setting('remote_ip'));
    
    if($secure)
    {
        $id = session_id();
        $name = session_name();
        if(setting('enable_https'))
            setcookie($name, $id, time()+session_cache_expire(), NULL, $_SERVER['HTTP_HOST'], true, true);
    }
}


/**
 * Set up the triggers for saving a session
 * @ingroup setup
 */
function setup_session($request = array())
{
	if(!isset($_SESSION))
	{
		/** always begin the session */
		session_start();
	}

    // check access times for expiry
    if(time() - session('created') > 1800 || setting('remote_ip') != session('remote_ip'))
        new_session();

    session('last_activity', time());
}

function session_set_conditional($module, $result)
{
	if(isset($result))
		session($module, $result);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_session($settings)
{
	if(!setting_installed() || !setting('database_enable'))
		return array();
	else
		return array('database');
}

/**
 * @defgroup session Session Save Functions
 * All functions that save information to the session for later reference
 * @param request The full request array to use for saving request information to the session
 * @return An associative array to be saved to $_SESSION[&lt;module&gt;] = session_select($request);
 * @{
 */

/**
 * Save and get information from the session
 * @return the session variable trying to be accessed
 */
function session($varname)
{
	$args = func_get_args();
	
	if(!isset($_SESSION))
	{
		raise_error('Session called after session close!', E_DEBUG);
		return;
	}
	
	if(count($args) > 1)
	{
		// they must be trying to set a value to the session
		/*
		$value = $args[count($args)-1];
		$args[count($args)-1] = NULL;
		
		// allow for cascading calls
		$current = &$_SESSION;
		foreach($args as $i => $varname)
		{
			if(isset($current[$varname]) && $varname !== NULL)
				$current = &$current[$varname];
			// don't return anything if the address it wrong
			else
				return;
		}
		
		// set the value
		$current = $value;
		*/
		$_SESSION[$varname] = $args[1];
	}
	
	if(isset($_SESSION[$varname]))
		return $_SESSION[$varname];
}

/**
 * Helper function for getting cascading session information
 */
function session_get($varname)
{
	$args = func_get_args();
	
	// don't return anything if it does not exist
	if(!isset($_SESSION[$varname]))
		return;

	// allow for cascading calls
	$current = &$_SESSION;
	foreach($args as $i => $varname)
	{
		if(isset($current[$varname]) && $varname !== NULL)
			$current = &$current[$varname];
		else
			break;
	}
	
	return $current;
}

/**
 * @}
 */
