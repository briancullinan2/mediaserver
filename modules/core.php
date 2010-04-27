<?php
/**
 * Core module validates core request variables
 * - since many modules are related to getting information from the database
 * - this module will register all the common functions for handling request variables, so the other modules don't have to
 */
 
/**
 * @defgroup register Register Functions
 * All functions that register modules and templates
 * @return an associative array that describes the modules functionality
 * @{
 */
 
/**
 * Create a global variable for storing all the module information
 * @ingroup setup
 */
function setup_register()
{
	$GLOBALS['modules'] = array('index' => array(
		'name' => 'Index',
		'description' => 'Load a module\'s output variables and display the template.',
		'privilage' => 1,
		'path' => LOCAL_ROOT . 'index.php',
		'alter query' => array('limit', 'start', 'direction', 'order_by', 'group_by')
		)
	);
	$GLOBALS['triggers'] = array('session' => array(), 'settings' => array());
	
	// read module list and create a list of available modules	
	setup_register_modules('modules' . DIRECTORY_SEPARATOR);
}

/**
 * Abstracted from setup_modules <br />
 * Generates a list of modules from a specified directory <br />
 * This allows for modules to load more modules, such as admin_tools
 * @ingroup setup
 * @param path The path to load modules from
 * @return An array containing only the modules found in the specified directory, modules loaded are also added to the GLOBAL list of modules
 */
function setup_register_modules($path)
{
	$files = fs_file::get(array('dir' => LOCAL_ROOT . $path, 'limit' => 32000), $count, true);
	
	$modules = array();

	if(is_array($files))
	{
		foreach($files as $i => $file)
		{
			if(is_file($file['Filepath']))
			{
				include_once $file['Filepath'];
				
				// determin module based on path
				$module = basename($file['Filepath']);
				
				// functional prefix so there can be multiple modules with the same name
				$prefix = substr($file['Filepath'], strlen(LOCAL_ROOT), -strlen($module));
				
				// remove slashes and replace with underscores
				$prefix = str_replace(array('/', '\\'), '_', $prefix);
				
				// remove modules_ prefix so as not to be redundant
				if(substr($prefix, 0, 8) == 'modules_') $prefix = substr($prefix, 8);
				
				// remove extension from module name
				$module = substr($module, 0, strrpos($module, '.'));
				
				// call register function
				if(function_exists('register_' . $prefix . $module))
				{
					$modules[$module] = call_user_func_array('register_' . $prefix . $module, array());
					$GLOBALS['modules'][$prefix . $module] = &$modules[$module];
				}
				
				// reorganize the session triggers for easy access
				if(isset($modules[$module]['session']))
				{
					foreach($modules[$module]['session'] as $i => $var)
					{
						$GLOBALS['triggers']['session'][$var][] = $prefix . $module;
					}
				}
				
				// reorganize alter query triggers
				if(isset($modules[$module]['alter query']))
				{
					foreach($modules[$module]['alter query'] as $i => $var)
					{
						$GLOBALS['triggers']['alter query'][$var][] = $prefix . $module;
					}
				}
			}
		}
	}
	
	return $modules;
}

/** 
 * Implementation of register
 */
function register_core()
{
	// register permission requirements
	
	// register the request variables we will be providing validators for
	
	// this module has no output
	return array(
		'name' => 'Core Functions',
		'description' => 'Adds core functionality to site that other common modules depend on.',
		'path' => __FILE__,
		'privilage' => 1
	);
}
/**
 * @}
 */

/**
 * Set up input variables, everything the site needs about the request <br />
 * Validate all variables, and remove the ones that aren't validate
 * @ingroup setup
 */
function setup_validate()
{
	// first fix the REQUEST_URI and pull out what is meant to be pretty dirs
	if(isset($_SERVER['PATH_INFO']))
		$_REQUEST['path_info'] = $_SERVER['PATH_INFO'];
	
	// call rewrite_vars in order to set some request variables
	rewrite_vars($_REQUEST, $_GET, $_POST);
	
	// go through the rest of the request and validate all the variables with the modules they are for
	foreach($_REQUEST as $key => $value)
	{
		if(function_exists('validate_' . $key))
			$_REQUEST[$key] = call_user_func_array('validate_' . $key, array($_REQUEST));
		elseif(isset($GLOBALS['validate_' . $key]) && is_callable($GLOBALS['validate_' . $key]))
			$_REQUEST[$key] = $GLOBALS['validate_' . $key]($_REQUEST);
		else
		{
			unset($_REQUEST[$key]);
			if(isset($_GET[$key])) unset($_GET[$key]);
		}
			
		// set the get variable also, so that when url($_GET) is used it is an accurate representation of the current page
		if(isset($_GET[$key])) $_GET[$key] = $_REQUEST[$key];
	}
	
	// call the session save functions
	session($_REQUEST);
	
	// do not let GoogleBot perform searches or file downloads
	if(NO_BOTS)
	{
		if(preg_match('/.*Googlebot.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			if(basename($_REQUEST['module']) != 'select' && 
				basename($_REQUEST['module']) != 'index' &&
				basename($_REQUEST['module']) != 'sitemap')
			{
				goto('module=sitemap');
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
 * @defgroup session Session Save Functions
 * All functions that save information to the session for later reference
 * @param request The full request array to use for saving request information to the session
 * @return An associative array to be saved to $_SESSION[&lt;module&gt;] = session_select($request);
 * @{
 */
 
/**
 * Save session information based on triggers specified by a module configuration
 */
function session($request = array())
{
	// check modules for vars and trigger a session save
	foreach($request as $key => $value)
	{
		if(isset($GLOBALS['triggers']['session'][$key]))
		{
			foreach($GLOBALS['triggers']['session'][$key] as $i => $module)
			{
				$_SESSION[$module] = call_user_func_array('session_' . $module, array($request));
			}
		}
	}
}

/**
 * @}
 */


/**
 * Make variables available for output in the templates,
 * convert variables to HTML compatible for security
 * @param name name of the variable the template can use to refer to
 * @param value value for the variable, converted to HTML
 * @param append (Optional) append the input value to a pre-existing set of data
 */
function register_output_vars($name, $value, $append = false)
{
	if(isset($GLOBALS['output'][$name]) && $append == false)
	{
		PEAR::raiseError('Variable "' . $name . '" already set!', E_DEBUG);
	}
	if($append == false)
		$GLOBALS['output'][$name] = $value;
	elseif(!isset($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = $value;
	elseif(is_string($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name] = array($GLOBALS['output'][$name], $value);
	elseif(is_array($GLOBALS['output'][$name]))
		$GLOBALS['output'][$name][] = $value;
}

/**
 * This function takes a request as input, and converts it to a pretty url, or makes no changes if mod_rewrite is off <br />
 * this function also supports PATH_INFO, and will convert any available path info into a path
 * @param request The request information in QUERY_STRING format or as an associative array
 * @param not_special If it is set to false, it will not automatically encode the path to HTML
 * @param include_domain will append the website's domain to the path, useful for feeds that are downloaded
 * @param return_array will return the parsed request from any time of inputted request for further formatting
 * @return Returns a htmlspecialchars() string for a specified request
 */
function url($request = array(), $not_special = false, $include_domain = false, $return_array = false)
{
	if(is_string($request))
	{
		if(strpos($request, '?') !== false)
		{
			$request = explode('?', $request);
			$dirs = split('/', $request[0]);
			$request = $request[1];
		}
		
		$arr = explode('&', $request);
		if(count($arr) == 1 && $arr[0] == '')
			$arr = array();
		$request = array();
		foreach($arr as $i => $value)
		{
			$x = explode('=', $value);
			$request[$x[0]] = isset($x[1])?$x[1]:'';
		}
	}
	if(isset($dirs))
	{
		$request = array_merge($request, parse_path_info($dirs));
	}
	if($return_array)
		return $request;
	
	// rebuild link
	if(!isset($request['module']))
		$request['module'] = validate_module(array('module' => isset($GLOBALS['module'])?$GLOBALS['module']:''));
	$path_info = create_path_info($request);
	$link = (($include_domain)?HTML_DOMAIN:'') . HTML_ROOT . $path_info;
	if(count($request) > 0)
	{
		$link .= '?';
		foreach($request as $key => $value)
		{
			$link .= (($link[strlen($link)-1] != '?')?'&':'') . $key . '=' . $value;
		}
	}
	if($not_special)
		return $link;
	else
		return htmlspecialchars($link);
}

/**
 * Change the header location to the specified request
 * @param request The string or array of request variables containing the location to go to
 */
function goto($request)
{
	if(!headers_sent())
	{
		// check if we are forwarding to the same domain
		if(is_string($request) && strpos($request, '://') !== false)
		{
			header('Location: ' . $request);
		}
		// if so, verify all request variables
		else
		{
			header('Location: ' . url($request, true));
		}
		
		// exit now so the page is redirected
		exit;
	}
}

/**
 * Function to call before the template is called, this can also be called from the first time #theme() is called
 * This sets all the register variables as HTML or original content, it also removes all unnecissary variables that might be used to penetrate the site
 */
function set_output_vars()
{
	// set a couple more that are used a lot
	
	// if the search is set, then alway output because any module that uses a get will also use search
	if(isset($_REQUEST['search']))
	{
		output_search($_REQUEST);
	}
	
	// the entire site depends on this
	register_output_vars('module', $_REQUEST['module']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $_REQUEST['cat']);
	
	// some templates refer to the dir to determine their own location
	if(isset($_REQUEST['dir'])) register_output_vars('dir', $_REQUEST['dir']);
	
	// this is just a helper variable for templates to use that only need to save 1 setting
	if(isset($_REQUEST['extra'])) register_output_vars('extra', $_REQUEST['extra']);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url($_GET, true));
	
	// output user information
	register_output_vars('user', $_SESSION['user']);
	
	// register user settings for this template
	if(isset($_SESSION['user']['settings']['templates'][$_REQUEST['template']]))
		register_output_vars('settings', $_SESSION['user']['settings']['templates'][$_REQUEST['template']]);
	// go through and set the defaults
	elseif(isset($GLOBALS['templates'][$_REQUEST['template']]['settings']))
	{
		$settings = array();
		foreach($GLOBALS['templates'][$_REQUEST['template']]['settings'] as $key => $setting)
		{
			if(isset($setting['default']))
				$settings[$key] = $setting['default'];
		}
		register_output_vars('settings', $settings);
	}
	
	// remove everything else so templates can't violate the security
	//   there is no going back from here
	if(isset($_SESSION)) session_write_close();
	
	$dont_remove = array(
		'GLOBALS',
		//'_REQUEST', // allow this because it has been fully validated
		'templates',
		'errors',
		'debug_errors',
		'user_errors',
		'warn_errors',
		'output',
		'alias',
		'alias_regexp',
		'paths',
		'paths_regexp',
		'mte',
		'module',
		'modules',
		'_PEAR_default_error_mode',
		'_PEAR_default_error_options',
		'handlers',
		'tables',
		'ext_to_mime',
		'lists'
	);
	
	foreach($GLOBALS as $key => $value)
	{
		if(in_array($key, $dont_remove) === false)
			unset($GLOBALS[$key]);
	}

	foreach($GLOBALS['output'] as $name => $value)
	{
		$GLOBALS['templates']['vars'][$name] = $value;
		
		$GLOBALS['templates']['html'][$name] = traverse_array($value);
	}
	
	unset($GLOBALS['output']);
}

/**
 * Recursively convert an array of string information to htmlspecialchars(), used by #register_output_vars()
 * @return a multilevel array with all strings converted to HTML compatible
 */
function traverse_array($input)
{
	if(is_string($input))
		return htmlspecialchars($input);
	elseif(is_array($input))
	{
		foreach($input as $key => $value)
		{
			$input[$key] = traverse_array($value);
		}
		return $input;
	}
	else
		return htmlspecialchars((string)$input);
}

/**
 * @defgroup validate Validate Functions
 * All functions that are for validating input variables
 * @param request The full request array incase validation depends on other variables from the request
 * @return NULL if the input is invalid and there is no default, the default value if the input is invalid, the input if it is valid
 * @{
 */
 
/**
 * Implementation of #setup_validate()
 * @return The index module by default, also checks for compatibility based on other request information
 */
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
	// check for ampache compitibility
	elseif(strpos($_SERVER['REQUEST_URI'], '/server/xml.server.php?') !== false)
	{
		return 'ampache';
	}
	else
	{
		$script = basename($_SERVER['SCRIPT_NAME']);
		$script = substr($script, 0, strpos($script, '.'));
		if(isset($GLOBALS['modules'][$script]))
			return $script;
		else
			return 'index';
	}
}

/**
 * Implementation of #setup_validate()
 * @return db_file/fs_file handler by default
 */
function validate_cat($request)
{
	if(isset($request['cat']) && (substr($request['cat'], 0, 3) == 'db_' || substr($request['cat'], 0, 3)))
		$request['cat'] = ((USE_DATABASE)?'db_':'fs_') . substr($request['cat'], 3);
	if(!isset($request['cat']) || !in_array($request['cat'], $GLOBALS['handlers']) || constant($request['cat'] . '::INTERNAL') == true)
		return USE_DATABASE?'db_file':'fs_file';
	return $request['cat'];
}

/**
 * Implementation of #setup_validate()
 * @return Zero by default, any number greater then zero is valid
 */
function validate_start($request)
{
	if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
		return 0;
	return $request['start'];
}

/**
 * Implementation of #setup_validate()
 * @return 15 by default, accepts any positive number
 */
function validate_limit($request)
{
	if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
		return 15;
	return $request['limit'];
}

/**
 * Implementation of #setup_validate()
 * @return Filepath by default, Relevance if performing a search which is a keyword for the #alter_query_search() call, any set of columns defined in the specified handler (cat) is valid
 */
function validate_order_by($request)
{
	$handler = validate_cat($request);
	
	$columns = call_user_func($handler . '::columns');
	
	if( !isset($request['order_by']) || !in_array($request['order_by'], $columns) )
	{
		if(isset($request['search']))
			return 'Relevance';
			
		// make sure if it is a list that it is all valid columns
		$columns = split(',', (isset($request['order_by'])?$request['order_by']:''));
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return 'Filepath';
		else
			return join(',', $columns);
	}
	return $request['order_by'];
}

/**
 * Implementation of #setup_validate()
 * @return NULL by default, any set of columns defined in the handler (cat) are valid
 */
function validate_group_by($request)
{
	$handler = validate_cat($request);
	
	$columns = call_user_func($handler . '::columns');
	
	if( isset($request['group_by']) && !in_array($request['group_by'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		$columns = split(',', $request['group_by']);
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return;
		else
			return join(',', $columns);
	}
	return $request['group_by'];
}

/**
 * Implementation of #setup_validate()
 * @return Ascending (ASC) by default, ASC or DESC are valid
 */
function validate_direction($request)
{
	if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
		return 'ASC';
	return $request['direction'];
}

/**
 * Implementation of #setup_validate()
 * @return NULL by default, any set of columns defined in the handler (cat) are valid
 */
function validate_columns($request)
{
	$handler = validate_cat($request);
	
	$columns = call_user_func($handler . '::columns');
	
	// which columns to search
	if( isset($request['columns']) && !in_array($request['columns'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		if(!is_array($request['columns'])) $request['columns'] = split(',', $request['columns']);
		foreach($request['columns'] as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
				unset($columns[$i]);
		}
		if(count($request['columns']) == 0)
			return;
		else
			return join(',', $request['columns']);
	}
	return $request['columns'];
}

/**
 * Implementation of #setup_validate()
 * @return NULL by default, no validation necessary, used only by templates to control what they display
 */
function validate_extra($request)
{
	if(isset($request['extra']))
		return $request['extra'];
}

/**
 * @}
 */

/**
 * Reverse the affects of #parse_path_info() <br />
 * Use specified request variables to construct a path, that can then be understood and parsed by parse_path_info()
 * @param request the request array from the url() function
 * @return a string containing just the path info from the input request
 */
function create_path_info(&$request)
{
	// use the same algorithm to rebuild the path info
	$path = str_replace('_', '/', $request['module']) . '/';
	
	// make sure the module doesn't actually exists on the web server
	if(file_exists(LOCAL_ROOT . $path))
	{
		// a path without all the underscores replaced would be better then no path at all
		$path = $request['module'] . '/';

		if(file_exists(LOCAL_ROOT . $path))
			return '';
	}
	
	// construct query out of remaining variables
	if(isset($request['cat']) && isset($request['id']) &&
		isset($request[$request['module']]) &&
		isset($request['extra']) && isset($request['filename']))
	{
		$path .= $request['cat'] . '/' . $request['id'] . '/' . 
				$request[$request['module']] . '/' . $request['extra'] . '/' . 
				$request['filename'];
		unset($request['cat']);
		unset($request['id']);
		unset($request[$request['module']]);
		unset($request['extra']);
		unset($request['filename']);
	}
	elseif(isset($request['cat']) && isset($request['id']) &&
			isset($request[$request['module']]) &&
			isset($request['filename']))
	{
		$path .= $request['cat'] . '/' . $request['id'] . '/' . 
				$request[$request['module']] . '/' . $request['filename'];
		unset($request['cat']);
		unset($request['id']);
		unset($request[$request['module']]);
		unset($request['filename']);
	}
	elseif(isset($request['cat']) && isset($request['id']) &&
			isset($request['filename']))
	{
		$path .= $request['cat'] . '/' . $request['id'] . '/' . $request['filename'];
		unset($request['cat']);
		unset($request['id']);
		unset($request['filename']);
	}
	elseif(isset($request['cat']) && isset($request['id']))
	{
		$path .= $request['cat'] . '/' . $request['id']; 
		unset($request['cat']);
		unset($request['id']);
	}
	elseif(isset($request['search']))
	{
		$path .= $request['search']; 
		unset($request['search']);
	}
	unset($request['module']);
	return $path;
}

/**
 * Parse a request from the path
 * @param path_info The part of a request that relects pretty dirs and contains slashes
 * @return all the request information retrieved from the path in an associative array
 */
function parse_path_info($path_info)
{
	$request = array();

	if(!is_array($path_info))
		$dirs = split('/', $path_info);
	else
		$dirs = $path_info;
	
	// remove empty dirs
	foreach($dirs as $i => $value)
	{
		if($value == '')
			unset($dirs[$i]);
	}
	$dirs = array_values($dirs);
	if(count($dirs) > 0)
	{
		// get module from path info
		//   match the module until it doesn't make any more, then apply the rules below
		// remove default module directory just like when the modules are loaded and set up
		if($dirs[0] == 'modules')
			unset($dirs[0]);
		$module = '';
		foreach($dirs as $i => $dir)
		{
			$module .= (($module != '')?'_':'') . $dir;
			if(isset($GLOBALS['modules'][$module]))
			{
				$request['module'] = $module;
				unset($dirs[$i]);
			}
			else
				break;
		}
		$dirs = array_values($dirs);
		switch(count($dirs))
		{
			case 1:
				$request['search'] = '"' . $dirs[0] . '"';
				break;
			case 2:
				$request['cat'] = $dirs[0];
				$request['id'] = $dirs[1];
				break;
			case 3:
				$request['cat'] = $dirs[0];
				$request['id'] = $dirs[1];
				$request['filename'] = $dirs[2];
				break;
			case 4:
				$request['cat'] = $dirs[0];
				$request['id'] = $dirs[1];
				$request[$request['module']] = $dirs[2];
				$request['filename'] = $dirs[3];
				break;
			case 5:
				$request['cat'] = $dirs[0];
				$request['id'] = $dirs[1];
				$request[$request['module']] = $dirs[2];
				$request['extra'] = $dirs[3];
				$request['filename'] = $dirs[4];
				break;
		}
	}

	return $request;
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
	$request['module'] = validate_module($request);
	
	if(isset($request['path_info']))
		$request = array_merge($request, parse_path_info($request['path_info']));
		
	// just about everything uses the cat variable so always validate and add this
	$request['cat'] = validate_cat($request);

	// do some modifications to specific modules being used
	if($request['module'] == 'bt')
	{
		// save the whole request to be used later
		$request['bt_request'] = $request;
	}
	if($request['module'] == 'ampache')
	{
		// a valid action is required for this module
		$request['action'] = validate_action($request);
		
		// rewrite some variables
		if(isset($request['offset'])) rewrite('offset', 'start', $request, $get, $post);
		if(isset($request['filter']) && $request['action'] != 'search_songs') rewrite('filter', 'id', $request, $get, $post);
		elseif(isset($request['filter']))  rewrite('filter', 'search', $request, $get, $post);
	}
}

/**
 * @defgroup alter_query Alter Query Functions
 * All functions that are for altering database queries based on the input variables they support
 * @param request The full request array so the input variables can alter database queries performed by handlers
 * @param props The list of current database properties for reference
 * @return A set of database properties to append to the current query
 * @{
 */

/**
 * Implementation of #alter_query()
 * Core input variables that alter database queries
 */
function alter_query_core($request, $props)
{
	$request['limit'] = validate_limit($request);
	$request['start'] = validate_start($request);
	$props['LIMIT'] = $request['start'] . ',' . $request['limit'];
	
	if(isset($request['group_by'])) $props['GROUP'] = validate_group_by($request);

	// relevance is handled below
	if($request['order_by'] == 'Relevance')
		$props['ORDER'] = 'Filepath DESC';
	else
	{
		if(isset($request['order_trimmed']) && $request['order_trimmed'] == true)
		{
			$props['ORDER'] = 'TRIM(LEADING "a " FROM TRIM(LEADING "an " FROM TRIM(LEADING "the " FROM LOWER( ' . 
								join(' )))), TRIM(LEADING "a " FROM TRIM(LEADING "an " FROM TRIM(LEADING "the " FROM LOWER( ', split(',', $request['order_by'])) . 
								' ))))' . ' ' . $request['direction'];
		}
		else
		{
			$props['ORDER'] = $request['order_by'] . ' ' . $request['direction'];
		}
	}
	
	return $props;
}

/**
 * @}
 */

/**
 * @defgroup output Output Functions
 * All functions that output the module
 * @param request The full request array to use when outputting the module
 * @{
 */

/**
 * Implementation of #output()
 * simply outputs the core
 */
function output_index($request)
{
	output_core($request);
}

/**
 * Implementation of #output()
 * Outputs select by default
 */
function output_core($request)
{
	// output any index like information
	
	// perform a select so files can show up on the index page
	output_select($request);
}

/**
 * @}
 */
