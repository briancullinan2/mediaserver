<?php

// Core plugin validates core request variables
//   since many plugins are related to getting information from the database
//   this plugin will register all the common functions for handling request variables, so the other plugins don't have to

function setup_plugins()
{
	$GLOBALS['plugins'] = array('index' => array(
		'name' => 'Index',
		'description' => 'Load a plugin\'s output variables and display the template.',
		'privilage' => 1,
		'path' => LOCAL_ROOT . 'index.php'
		)
	);
	$GLOBALS['triggers'] = array('session' => array(), 'settings' => array());
	
	// read plugin list and create a list of available plugins	
	load_plugins('plugins' . DIRECTORY_SEPARATOR);
}

function load_plugins($path)
{
	$files = fs_file::get(array('dir' => LOCAL_ROOT . $path, 'limit' => 32000), $count, true);
	
	$plugins = array();

	if(is_array($files))
	{
		foreach($files as $i => $file)
		{
			if(is_file($file['Filepath']))
			{
				include_once $file['Filepath'];
				
				// determin plugin based on path
				$plugin = basename($file['Filepath']);
				
				// functional prefix so there can be multiple plugins with the same name
				$prefix = substr($file['Filepath'], strlen(LOCAL_ROOT), -strlen($plugin));
				
				// remove slashes and replace with underscores
				$prefix = str_replace(array('/', '\\'), '_', $prefix);
				
				// remove plugins_ prefix so as not to be redundant
				if($prefix == 'plugins_') $prefix = '';
				
				// remove extension from plugin name
				$plugin = substr($plugin, 0, strrpos($plugin, '.'));
				
				// call register function
				if(function_exists('register_' . $prefix . $plugin))
				{
					$plugins[$plugin] = call_user_func_array('register_' . $prefix . $plugin, array());
					$GLOBALS['plugins'][$prefix . $plugin] = &$plugins[$plugin];
				}
				
				// reorganize the session triggers for easy access
				if(isset($plugins[$plugin]['session']))
				{
					foreach($plugins[$plugin]['session'] as $i => $var)
					{
						$GLOBALS['triggers']['session'][$var][] = $prefix . $plugin;
					}
				}
			}
		}
	}
	
	return $plugins;
}

// this is used to set up the input variables
function setup_input()
{
	// first fix the REQUEST_URI and pull out what is meant to be pretty dirs
	if(isset($_SERVER['PATH_INFO']))
		$_REQUEST['path_info'] = $_SERVER['PATH_INFO'];
	
	// call rewrite_vars in order to set some request variables
	rewrite_vars($_REQUEST, $_GET, $_POST);
	
	// go through the rest of the request and validate all the variables with the plugins they are for
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
			
		// set the get variable also, so that when generate_href($_GET) is used it is an accurate representation of the current page
		if(isset($_GET[$key])) $_GET[$key] = $_REQUEST[$key];
	}
	
	// check plugins for vars and trigger a session save
	foreach($_REQUEST as $key => $value)
	{
		if(isset($GLOBALS['triggers']['session'][$key]))
		{
			foreach($GLOBALS['triggers']['session'][$key] as $i => $plugin)
			{
				$_SESSION[$plugin] = call_user_func_array('session_' . $plugin, array($_REQUEST));
			}
		}
	}
	
	// do not let GoogleBot perform searches or file downloads
	if(NO_BOTS)
	{
		if(preg_match('/.*Googlebot.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			if(basename($_REQUEST['plugin']) != 'select' && 
				basename($_REQUEST['plugin']) != 'index' &&
				basename($_REQUEST['plugin']) != 'sitemap')
			{
				header('Location: ' . generate_href(array('plugin' => 'sitemap')));
				exit;
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

function register_core()
{
	// register permission requirements
	
	// register the request variables we will be providing validators for
	
	// this plugin has no output
	return array(
		'name' => 'Core Functions',
		'description' => 'Adds core functionality to site that other common plugins depend on.',
		'path' => __FILE__,
		'privilage' => 1
	);
}

// this makes all the variables available for output
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

function href($request = array(), $not_special = false, $include_domain = false, $return_array = false)
{
	return generate_href($request, $not_special, $include_domain, $return_array);
}

// this function takes a request as input, and based on the .htaccess rules, converts it to a pretty url, or makes no changes if mod_rewrite is off
function generate_href($request = array(), $not_special = false, $include_domain = false, $return_array = false)
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
	$link = (($include_domain)?HTML_DOMAIN:'') . HTML_ROOT . '?';
	foreach($request as $key => $value)
	{
		$link .= (($link[strlen($link)-1] != '?')?'&':'') . $key . '=' . $value;
	}
	if($not_special)
		return $link;
	else
		return htmlspecialchars($link);
}

function set_output_vars()
{
	// set a couple more that are used a lot
	
	// if the search is set, then alway output because any plugin that uses a get will also use search
	if(isset($_REQUEST['search']))
	{
		output_search($_REQUEST);
	}
	
	// the entire site depends on this
	register_output_vars('plugin', $_REQUEST['plugin']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $_REQUEST['cat']);
	
	// some templates refer to the dir to determine their own location
	if(isset($_REQUEST['dir'])) register_output_vars('dir', $_REQUEST['dir']);
	
	// this is just a helper variable for templates to use that only need to save 1 setting
	if(isset($_REQUEST['extra'])) register_output_vars('extra', $_REQUEST['extra']);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', href($_GET, true));
	
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
		'plugin',
		'plugins',
		'_PEAR_default_error_mode',
		'_PEAR_default_error_options',
		'modules',
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

function validate_cat($request)
{
	if(isset($request['cat']) && (substr($request['cat'], 0, 3) == 'db_' || substr($request['cat'], 0, 3)))
		$request['cat'] = ((USE_DATABASE)?'db_':'fs_') . substr($request['cat'], 3);
	if(!isset($request['cat']) || !in_array($request['cat'], $GLOBALS['modules']) || constant($request['cat'] . '::INTERNAL') == true)
		return USE_DATABASE?'db_file':'fs_file';
	return $request['cat'];
}

function validate_start($request)
{
	if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
		return 0;
	return $request['start'];
}

function validate_limit($request)
{
	if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
		return 15;
	return $request['limit'];
}

function validate_order_by($request)
{
	$module = validate_cat($request);
	
	$columns = call_user_func($module . '::columns');
	
	if( !isset($request['order_by']) || !in_array($request['order_by'], $columns) )
	{
		if(isset($request['search']))
			return 'Relevance';
			
		// make sure if it is a list that it is all valid columns
		$columns = split(',', (isset($request['order_by'])?$request['order_by']:''));
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($module . '::columns')))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return 'Filepath';
		else
			return join(',', $columns);
	}
	return $request['order_by'];
}

function validate_group_by($request)
{
	$module = validate_cat($request);
	
	$columns = call_user_func($module . '::columns');
	
	if( isset($request['group_by']) && !in_array($request['group_by'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		$columns = split(',', $request['group_by']);
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($module . '::columns')))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return;
		else
			return join(',', $columns);
	}
	return $request['group_by'];
}

function validate_direction($request)
{
	if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
		return 'ASC';
	return $request['direction'];
}

function validate_columns($request)
{
	$module = validate_cat($request);
	
	$columns = call_user_func($module . '::columns');
	
	// which columns to search
	if( isset($request['columns']) && !in_array($request['columns'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		if(!is_array($request['columns'])) $request['columns'] = split(',', $request['columns']);
		foreach($request['columns'] as $i => $column)
		{
			if(!in_array($column, call_user_func($module . '::columns')))
				unset($columns[$i]);
		}
		if(count($request['columns']) == 0)
			return;
		else
			return join(',', $request['columns']);
	}
	return $request['columns'];
}

// Redirect unknown file and folder requests to recognized protocols and other plugins.
function validate_plugin($request)
{
	// remove .php extension
	if(isset($request['plugin']) && substr($request['plugin'], -4) == '.php')
		$request['plugin'] = substr($request['plugin'], 0, -4);
		
	// replace slashes
	if(isset($request['plugin'])) $request['plugin'] = str_replace(array('/', '\\'), '_', $request['plugin']);
	
	// if the plugin is set then return right away
	if(isset($request['plugin']) && isset($GLOBALS['plugins'][$request['plugin']]))
	{
		return $request['plugin'];
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
		if(isset($GLOBALS['plugins'][$script]))
			return $script;
		else
			return 'index';
	}
}

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
		// get plugin from path info
		$request['plugin'] = $dirs[0];
		switch(count($dirs))
		{
			case 2:
				$request['search'] = '"' . $dirs[1] . '"';
				break;
			case 3:
				$request['cat'] = $dirs[1];
				$request['id'] = $dirs[2];
				break;
			case 4:
				$request['cat'] = $dirs[1];
				$request['id'] = $dirs[2];
				$request['filename'] = $dirs[3];
				break;
			case 5:
				$request['cat'] = $dirs[1];
				$request['id'] = $dirs[2];
				$request[$request['plugin']] = $dirs[3];
				$request['filename'] = $dirs[4];
				break;
			case 6:
				$request['cat'] = $dirs[1];
				$request['id'] = $dirs[2];
				$request[$request['plugin']] = $dirs[3];
				$request['extra'] = $dirs[4];
				$request['filename'] = $dirs[5];
				break;
		}
	}
	
	return $request;
}

function rewrite($old_var, $new_var, &$request, &$get, &$post)
{
	if(isset($request[$old_var])) $request[$new_var] = $request[$old_var];
	if(isset($get[$old_var])) $get[$new_var] = $get[$old_var];
	if(isset($post[$old_var])) $post[$new_var] = $post[$old_var];
	
	unset($request[$old_var]);
	unset($get[$old_var]);
	unset($post[$old_var]);
}

function rewrite_vars(&$request, &$get, &$post)
{
	$request['plugin'] = validate_plugin($request);
	
	if(isset($request['path_info']))
		$request = array_merge($request, parse_path_info($request['path_info']));
		
	// just about everything uses the cat variable so always validate and add this
	$request['cat'] = validate_cat($request);

	// do some modifications to specific plugins being used
	if($request['plugin'] == 'bt')
	{
		// save the whole request to be used later
		$request['bt_request'] = $request;
	}
	if($request['plugin'] == 'ampache')
	{
		// a valid action is required for this plugin
		$request['action'] = validate_action($request);
		
		// rewrite some variables
		if(isset($request['offset'])) rewrite('offset', 'start', $request, $get, $post);
		if(isset($request['filter']) && $request['action'] != 'search_songs') rewrite('filter', 'id', $request, $get, $post);
		elseif(isset($request['filter']))  rewrite('filter', 'search', $request, $get, $post);
	}
}

function validate_extra($request)
{
	if(isset($request['extra']))
		return $request['extra'];
}

function validate_filename($request)
{
	// just return the same, this is only used for pretty dirs and compatibility
	if(isset($request['filename']))
		return $request['filename'];
}

function output_index($request)
{
	// output any index like information
	
	// perform a select so files can show up on the index page
	output_select($request);
}
