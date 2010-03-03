<?php

// Core plugin validates core request variables
//   since many plugins are related to getting information from the database
//   this plugin will register all the common functions for handling request variables, so the other plugins don't have to

function register_core()
{
	// register permission requirements
	
	// register the request variables we will be providing validators for
	
	// this plugin has no output
	return array(
		'name' => 'core',
		'description' => 'Adds core functionality to site that other common plugins depend on.',
		'path' => __FILE__
	);
}

// this makes all the variables available for output
function register_output_vars($name, $value)
{
	$GLOBALS['output'][$name] = $value;
}

// this function takes a request as input, and based on the .htaccess rules, converts it to a pretty url, or makes no changes if mod_rewrite is off
function generate_href($request = array(), $notspecial = false, $include_domain = false)
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
			$request[$x[0]] = $x[1];
		}
	}
	
	if(isset($dirs))
	{
		$request = array_merge($request, rewrite_vars(array('path_info' => $dirs)));
	}
	
	// rebuild link
	$link = (($include_domain)?HTML_DOMAIN:'') . HTML_ROOT . '?';
	foreach($request as $key => $value)
	{
		if($key == 'return')
		{
			// remove return in order to create a valid url
			if(isset($_REQUEST['return']))
			{
				$return = $_REQUEST['return'];
				unset($_REQUEST['return']);
			}
			$value = urlencode(generate_href($_GET, true));
			if(isset($return)) $_REQUEST['return'] = $return;
		}
		$link .= (($link[strlen($link)-1] != '?')?'&':'') . $key . '=' . $value;
	}
	if($not_special)
		return $link;
	else
		return htmlspecialchars($link);
}

function set_output_vars($smarty)
{
	// set a couple more that are used a lot

	// set debug errors
	register_output_vars('debug_errors', $GLOBALS['debug_errors']);
	
	// filter out user errors for easy access by templates
	register_output_vars('user_errors', $GLOBALS['user_errors']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $_REQUEST['cat']);
	
	// some templates refer to the dir to determine their own location
	if(isset($_REQUEST['dir')) register_output_vars('cat', $_REQUEST['dir']);
	
	// this is just a helper variable for templates to use that only need to save 1 setting
	if(isset($_REQUEST['extra')) register_output_vars('extra', $_REQUEST['extra']);
	
	// remove everything else so templates can't violate the security
	//   there is no going back from here
	if(isset($_SESSION)) session_write_close();
	
	$dont_remove = array('GLOBALS', 'templates', 'smarty', 'output', 'template', 'alias', 'alias_regexp', 'paths', 'paths_regexp', 'mte');
	foreach($GLOBALS as $key => $value)
	{
		if(in_array($key, $dont_remove) === false)
			unset($GLOBALS[$key]);
	}
	
	if($smarty)
	{
		foreach($GLOBALS['output'] as $name => $value)
		{
			$GLOBALS['smarty']->assign($name, $value);
		}
	}
	else
	{
		foreach($GLOBALS['output'] as $name => $value)
		{
			$GLOBALS['templates']['vars'][$name] = $value;
		}
	}
	
	unset($GLOBALS['output']);
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
		$columns = split(',', $request['columns']);
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
	return $request['columns'];
}

// Redirect unknown file and folder requests to recognized protocols and other plugins.
function validate_plugin($request)
{
	// remove .php extension
	if(isset($request['plugin']) && substr($request['plugin'], -4) == '.php')
		$request['plugin'] = substr($request['plugin'], 0, -4);
	
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

function rewrite_vars($request)
{
	$request['plugin'] = validate_plugin($request);
	
	if(isset($request['path_info']))
	{
		if(!is_array($request['path_info']))
			$dirs = split('/', $request['path_info']);
		else
			$dirs = $request['path_info'];
		$request['plugin'] = validate_plugin(array('plugin' => $dirs[0]));
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
	
	// just about everything uses the cat variable so always validate and add this
	$request['cat'] = validate_cat($request);

	if($request['plugin'] == 'bt')
	{
		// save the whole request to be used later
		$request['bt_request'] = $_REQUEST;
	}
	if($request['plugin'] == 'ampache')
	{
		// rewrite some variables
		if(isset($request['offset'])) $request['start'] = $request['offset'];
		if(isset($request['filter'])) $request['id'] = $request['filter'];
	}

	return $request;
}

function validate_extra($request)
{
	if(isset($request['extra']))
		return $request['extra'];
}

function output_index($request)
{
	// output any index like information
	
	// perform a select so files can show up on the index page
	output_select($request);
}
