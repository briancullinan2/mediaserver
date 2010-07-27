<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_session()
{
	die('this needs to be fixed');
	return array(
		'name' => lang('session title', 'Session Storage'),
		'description' => lang('session description', 'Control all session functionality and provide a database.'),
		'privilage' => 5,
		'path' => __FILE__,
		'settings' => 'session',
		'depends on' => 'session',
		'package' => 'core',
	);
}
 
/**
 * Set up the triggers for saving a session
 * @ingroup setup
 */
function setup_session($request = array())
{
	/** always begin the session */
	session_start();

	// restore errors if there are errors
	$GLOBALS['user_errors'] = array_merge($GLOBALS['user_errors'], session_get('errors', 'user'));
	$GLOBALS['warn_errors'] = array_merge($GLOBALS['warn_errors'], session_get('errors', 'warn'));
	$GLOBALS['note_errors'] = array_merge($GLOBALS['note_errors'], session_get('errors', 'note'));

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
	if(setting_installed() == false || setting('database_enable') == false)
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
