<?php

/**
 * Validate request variables for use
 * @param name name of the variable to be validate
 * @param request the request input containing the information to be validated
 * @return the validated variable value
 */
function validate($request, $key)
{
	// call debug error if validate is being called before the request has been validated
	if(!isset($GLOBALS['validated']))
		raise_error('Validate \'' . $key . '\' being called before the request has been validated!', E_DEBUG);
	
	// call function
	if(function_exists('validate_' . $key))
		return call_user_func_array('validate_' . $key, array($request));
	elseif(isset($GLOBALS['validate_' . $key]) && is_callable($GLOBALS['validate_' . $key]))
		return call_user_func_array($GLOBALS['validate_' . $key], array($request));
	// if it is an attempted setting, keep it for now and let the configure modules module handle it
	elseif(substr($key, 0, 8) == 'setting_')
		return $request[$key];
	
	$result = trigger_key('validate', NULL, $request, $key);
	if($result)
		return $result;
	
	// if a validator isn't found in the configuration
	raise_error('Validate \'' . $key . '\' not found!', E_DEBUG);
	
	return;
}


/**
 * Set up input variables, everything the site needs about the request <br />
 * Validate all variables, and remove the ones that aren't validate
 * @ingroup setup
 */
function validate_request()
{
	//Remove annoying POST error message with the page is refreshed 
	//  better place for this?
	if(isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post')
	{
		session('last_request',  $_REQUEST);
		goto($_SERVER['REQUEST_URI']);
	}
	if($last_request = session('last_request'))
	{
		$_REQUEST = $last_request;
		// set the method just for reference
		$_SERVER['REQUEST_METHOD'] = 'POST';
		session('last_request', NULL);
	}
	
	// first fix the REQUEST_URI and pull out what is meant to be pretty dirs
	if(isset($_SERVER['PATH_INFO']))
		$_REQUEST['path_info'] = $_SERVER['PATH_INFO'];
	
	// call rewrite_vars in order to set some request variables
	rewrite_vars($_REQUEST, $_GET, $_POST);
	
	$GLOBALS['validated'] = array();
	// go through the rest of the request and validate all the variables with the modules they are for
	foreach($_REQUEST as $key => $value)
	{
		$GLOBALS['validated'][] = $key;
		$new_value = validate($_REQUEST, $key);
		if(isset($new_value))
			$_REQUEST[$key] = $new_value;
		else
			unset($_REQUEST[$key]);
			
		// set the get variable also, so that when url($_GET) is used it is an accurate representation of the current page
		if(isset($_GET[$key]) && isset($_REQUEST[$key])) $_GET[$key] = $_REQUEST[$key];
		else
			unset($_GET[$key]);
	}
	
	// call the session save functions
	trigger('session', 'session_set_conditional', $_REQUEST);
	
	// do not let GoogleBot perform searches or file downloads
	if(setting('no_bots'))
	{
		if(preg_match('/.*Googlebot.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			if(basename($_REQUEST['module']) != 'select' && 
				basename($_REQUEST['module']) != 'index' &&
				basename($_REQUEST['module']) != 'sitemap')
			{
				goto('sitemap');
			}
			else
			{
				// don't let google bots perform searches, this takes up a lot of resources
				foreach($_REQUEST as $key => $value)
				{
					if(substr($key, 0, 6) == 'search')
					{
						unset($_REQUEST[$key]);
					}
				}
			}
		}
	}
}

/**
 * Implementation of #setup_validate()
 * @return The index module by default, also checks for compatibility based on other request information
function validate_module($request)
{
	// remove .php extension
	if(isset($request['module']) && substr($request['module'], -4) == '.php')
		$request['module'] = substr($request['module'], 0, -4);
		
	// replace slashes
	if(isset($request['module'])) $request['module'] = str_replace(array('/', '\\'), '_', $request['module']);
	
	// if the module is set then return right away
	if(isset($request['module']) && isset($GLOBALS['modules'][$request['module']]))
	{
		return $request['module'];
	}
	else
	{
		$script = basename($_SERVER['SCRIPT_NAME']);
		$script = substr($script, 0, strpos($script, '.'));
		if(isset($GLOBALS['modules'][$script]))
			return $script;
		else
			return 'core';
	}
}
 */

/**
 * Rewrite variables in to different names including GET and POST
 */
function rewrite($old_var, $new_var, &$request, &$get, &$post)
{
	if(isset($request[$old_var])) $request[$new_var] = $request[$old_var];
	if(isset($get[$old_var])) $get[$new_var] = $get[$old_var];
	if(isset($post[$old_var])) $post[$new_var] = $post[$old_var];
	
	unset($request[$old_var]);
	unset($get[$old_var]);
	unset($post[$old_var]);
}


/**
 * Check for variables to be rewritten for specific modules like @ref modules/ampache.php "Ampache" <br />
 * This allows for libraries such as bttracker to recieve variables with similar names in the right way
 * @param request the full request variables
 * @param get the get params
 * @param post the post variables
 */
function rewrite_vars(&$request, &$get, &$post)
{
	// get path info
	$request['path_info'] = validate($request, 'path_info');

	if($path = get_menu_entry($request['path_info']))
	{
		// call a modules rewrite function for further rewriting
		if(function_exists('rewrite_' . $GLOBALS['menus'][$path]['module']))
			$result = invoke_module('rewrite', $GLOBALS['menus'][$path]['module'], array($request['path_info'], $request));
		else
			$result = invoke_module('rewrite', 'core', array($request['path_info'], $request));
			
		// merge result, but current request takes precedence
		if(isset($result))
			$request = array_merge($result, $request);
	}
	
	// just about everything uses the cat variable so always validate and add this
	$request['handler'] = validate($request, 'handler');

/**
	// do some modifications to specific modules being used
	if($request['module'] == 'bt')
	{
		// save the whole request to be used later
		$request['bt_request'] = $request;
	}
	*/
}
