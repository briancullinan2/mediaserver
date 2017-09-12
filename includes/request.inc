<?php

// this is the request processor


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
	if(isset($result))
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
function request_validate()
{
	raise_error('Request validation (before): ' . serialize($_REQUEST), E_DEBUG, $_REQUEST);
	
	//Remove annoying POST error message with the page is refreshed 
	//  better place for this?
	if(setting('request_method') == 'post')
	{
		$_REQUEST['URI'] = $_SERVER['REQUEST_URI'];
		session('last_request',  $_REQUEST);
		if(!isset($_REQUEST['no_redirect']))
			location($_SERVER['REQUEST_URI']);
	}
	if(($last_request = session('last_request')) && $last_request['URI'] == $_SERVER['REQUEST_URI'])
	{
		$_REQUEST = $last_request;
		
		// set the method just for reference
		$_SERVER['REQUEST_METHOD'] = 'POST';
		session('last_request', NULL);
	}
	
	// first fix the REQUEST_URI and pull out what is meant to be pretty dirs
	if(!isset($_REQUEST['path_info']))
	{
		if(isset($_SERVER['PATH_INFO']))
			$_REQUEST['path_info'] = urldecode($_SERVER['PATH_INFO']);
		elseif(isset($_SERVER['REQUEST_URI']))
			$_REQUEST['path_info'] = urldecode(generic_validate_urlpath($_SERVER, 'REQUEST_URI'));
	}
	
	// remove root
	if(substr($_REQUEST['path_info'], 0, strlen(setting('html_root'))) == setting('html_root'))
		$_REQUEST['path_info'] = substr($_REQUEST['path_info'], strlen(setting('html_root')));
	
	// store unaltered request for modules to use
	
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
		if(isset($_GET[$key]) && isset($_REQUEST[$key])) 
			$_GET[$key] = $_REQUEST[$key];
		else
			unset($_GET[$key]);
	}
	
	raise_error('Request validation (after): ' . serialize($_REQUEST), E_DEBUG, $_REQUEST);
	
	// call the session save functions
	trigger('session', 'session_set_conditional', $_REQUEST);
}

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

	if(($path = get_menu_entry($request['path_info'])))
	{
		// merge additional argument overrides for menu entry
		if(isset($GLOBALS['menus'][$path]['request']))
			$request = array_merge($request, $GLOBALS['menus'][$path]['request']);
		
		// call a modules rewrite function for further rewriting
		if(module_implements('rewrite', $GLOBALS['menus'][$path]['module']))
			$result = invoke_module('rewrite', $GLOBALS['menus'][$path]['module'], array($request['path_info'], $request));
		else
			$result = invoke_module('rewrite', 'default', array($request['path_info'], $request));
			
		// merge result
		if(isset($result))
			$request = array_merge($request, $result);
	}
	$request = trigger_key('validate', NULL, $request, NULL);

/**
	// do some modifications to specific modules being used
	if($request['module'] == 'bt')
	{
		// save the whole request to be used later
		$request['bt_request'] = $request;
	}
	*/
}

/**
 * Parse a request from the path
 * @param path_info The part of a request that relects pretty dirs and contains slashes
 * @return all the request information retrieved from the path in an associative array
 */
function rewrite_default($path_info)
{
	$request = array();

	$menu = get_menu_entry($path_info);

	$dirs = explode('/', trim($path_info, '/'));
	
	// assign to variables based on menu entry
	$vars = explode('/', $menu);
	foreach($vars as $i => $var)
	{
		if(substr($var, 0, 1) == '%' && isset($dirs[$i]))
		{
			$var = substr($var, 1);
			$request[$var] = ($i == count($vars)-1)?implode('/', $dirs):$dirs[$i];
		}
		unset($dirs[$i]);
	}

	return $request;
}

/**
 * Generic validator
 */
function generic_validate_numeric($request, $index)
{
	if( isset($request[$index]) && is_numeric($request[$index]))
		return $request[$index];
}

/**
 * Generic validator
 */
function generic_validate_numeric_zero($request, $index)
{
	if( isset($request[$index]) && is_numeric($request[$index]) && $request[$index] >= 0 )
		return $request[$index];
	return 0;
}

/**
 * Generic validator
 */
function generic_validate_numeric_default($request, $index, $default)
{
	if( isset($request[$index]) && is_numeric($request[$index]) && $request[$index] >= 0 )
		return $request[$index];
	return $default;
}

/**
 * Generic validator
 */
function generic_validate_numeric_lower($request, $index, $default, $lower)
{
	if( isset($request[$index]) && is_numeric($request[$index]) && $request[$index] >= $lower )
		return $request[$index];
	return $default;
}

/**
 * Generic validator
 */
function generic_validate_url($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && @parse_url($request[$index]) !== false)
		return $request[$index];
}

/**
 * Generic validator
 */
function generic_validate_regexp($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' &&
		preg_match($request[$index], 'test') !== false
	)
		return $request[$index];
}

/**
 * Generic validator
 */
function generic_validate_email($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match('/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i', $request[$index]) != 0)
		return $request[$index];
}

/**
 * Generic validator
 */
function generic_validate_alphanumeric($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match('/^[a-z0-9]*$/i', $request[$index]) != 0)
		return $request[$index];
}

/**
 * Generic validator
 */
function generic_validate_hex($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match('/^[abcdef0-9]*$/i', $request[$index]) != 0)
		return $request[$index];
}

/**
 * Generic validator, prevents breakout bits, accepts all printable characters
 */
function generic_validate_all_safe($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match('/^[\x20-\x7E]*$/i', $request[$index]) != 0)
		return $request[$index];
}

/**
 * Generic validator, replaces all invalid windows characters with an underscore
 */
function generic_validate_filename($request, $index)
{
	if(isset($request[$index]))
	{
		// replace all invalid characters
		$request[$index] = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '_', $request[$index]);
		if($request[$index] != '')
			return $request[$index];
	}
}

/**
 * Generic validator for machine readable names like function names
 */
function generic_validate_machine_readable($request, $index)
{
	// ensure it is a valid name
	if(isset($request[$index]))
		return strtolower(machine($request[$index]));
}

/**
 * Generic validate for hostname part of URL
 */
function generic_validate_hostname($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match(
				'/^
				[a-z][a-z0-9+\-.]*:\/\/               # Scheme
				([a-z0-9\-._~%!$&\'()*+,;=]+@)?      # User
				(?P<host>[a-z0-9\-._~%]+             # Named or IPv4 host
				|\[[a-z0-9\-._~%!$&\'()*+,;=:]+\])   # IPv6+ host
				/ix', 
			$request[$index], $matches) != 0)
		return $matches[0];
}

/**
 * Generic validate for path part of URL
 */
function generic_validate_urlpath($request, $index)
{
	if(isset($request[$index]) && $request[$index] != '' && preg_match(
				'/^
				# Skip over scheme and authority, if any
				([a-z][a-z0-9+\-.]*:(\/\/[^\/?#]+)?)?
				# Path
				(?P<path>[a-z0-9\-._~%!$&\'()*+,;=:@\/]*)/ix', 
			$request[$index], $matches) != 0 && $matches['path'] != false)
		return $matches['path'];
}

function generic_validate_query_str($request, $index)
{
	if(isset($request[$index]) &&
		preg_match('/^[^?#]+\?([^#]+)/i', $request[$index], $matches) != 0)
		return $matches[1];
}

function generic_validate_base64($request, $index)
{
	if(isset($request[$index]) && 
		preg_match('/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/', $request[$index]) != 0)
		return $request[$index];
}

function generic_validate_dir($request, $index)
{
	if(isset($request[$index]) && is_dir($request[$index]))
	{
		if(substr($request[$index], -strlen(DIRECTORY_SEPARATOR)) != DIRECTORY_SEPARATOR && substr($request[$index], -1) != '/') 
			$request[$index] .= DIRECTORY_SEPARATOR;
		return str_replace(DIRECTORY_SEPARATOR, '/', $request[$index]);
	}
}
