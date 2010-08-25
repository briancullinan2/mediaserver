<?php

function register_menu()
{
	return array(
		'name' => 'Menus',
		'description' => 'Interface for configuring the site menu.',
		'privilage' => 1,
		'path' => __FILE__,
		'package' => 'core',
		'output' => 'output_menus',
		'template' => true,
	);
}


function setup_menu()
{
	// define constants for use with menu functions to specify to the template if the menu should be shown
	define('MENU_CALLBACK', 	0);
	define('MENU_LIST', 		1);
	define('MENU_NORMAL', 		2);
	
	if(!isset($GLOBALS['menus']))
		$GLOBALS['menus'] = array();
		
	// get menus defined by modules
	invoke_all_callback('menu', 'add_menu');

	// loop through modules and process menu items
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($GLOBALS['menus'][$module]))
			continue;
			
		// add default menu items
		if(!isset($GLOBALS['menus'][$module]))
			add_menu($module, array($module => array(
				'name' => $config['name'],
				'module' => $module,
				'callback' => 'output_' . $module,
				'type' => MENU_CALLBACK,
			)));
	}
}

function validate_path_info($request)
{
	if(isset($request['path_info']))
	{
		$result = get_menu_entry($request['path_info']);
		if(isset($result))
			return $request['path_info'];
	}
	return 'select';
}


function get_menu_entry($current_path)
{
	if(!isset($GLOBALS['menus']))
		setup_menu();

	// find the menu entry that matches the most
	$highest_match = '';
	foreach($GLOBALS['menus'] as $path => $config)
	{
		$path_regexp = preg_replace(array('/\\//i', '/\\/%[a-z][a-z0-9_]*/i'), array('\/', '/[^\/]*?'), addslashes($path));
		if(preg_match('/^' . $path_regexp . '/i', $current_path) > 0)
		{
			if(substr_count($path, '/') > substr_count($highest_match, '/') || $highest_match == '')
				$highest_match = $path;
		}
	}
	if($highest_match != '')
		return $highest_match;
}

function get_path(&$request, $index)
{
	// must be an array so the matches can be paired
	if(!is_array($request))
		return '';
		
	// rebuild link
	$result = preg_match_all('/\/(%([a-z][a-z0-9_]*))/i', $index, $matches);
	$path_info = str_replace($matches[1], array_intersect_key($request, array_flip($matches[2])), $index);
	$request = array_diff_key($request, array_flip($matches[2]));
	return $path_info;
}


function invoke_menu($request, $template = false)
{
	// output errors only
	if(isset($request['errors_only']) && $request['errors_only'] == true)
	{
		register_output_vars('errors_only', true);

		theme('errors_block');
		
		// remove old errors from session
		$GLOBALS['user_errors'] = array();
		$GLOBALS['warn_errors'] = array();
		$GLOBALS['debug_errors'] = array();
		$GLOBALS['note_errors'] = array();
		
		return;
	}
	
	// check request for path info
	$request['path_info'] = validate($request, 'path_info');
	if($path = get_menu_entry($request['path_info']))
	{
		$user = session('users');
		$menu = $GLOBALS['menus'][$path];
		$module = $GLOBALS['modules'][$menu['module']];
		if(isset($menu['template']))
			$template = $menu['template'];
		else
			$template = $module['template'];
		
		// check module permissions
		if(isset($module['privilage']) && 
			$user['Privilage'] < $module['privilage'])
		{
			raise_error('Access Denied!', E_USER);
			
			theme('errors');
			return;
		}
		// permissions are ok
		else
		{
			// call the callback specified
			if(is_callable($menu['callback']) && setting($menu['module'] . '_enable') != false)
				call_user_func_array($menu['callback'], array($request));
			// if there are dependency issues
			elseif(dependency($menu['module']) == false && setting($menu['module'] . '_enable') == false)
			{
				raise_error('The selected module has dependencies that are not yet! <a href="' . 
					url('admin/modules/' . $menu['module']) . '">Configure</a> this module.'
				, E_DEBUG|E_USER);
				
				theme('errors');
				return;
			}
		}

		// just return because the output function was already called
		if($template == false)
			return;
		
		// if it is set to a callable function to determine the template, then call that
		elseif(is_callable($template))
			call_user_func_array($template, array($request));

		// call the default template based on the module name
		elseif($template === true || $template === '1')
			theme($menu['module']);
			
		// if it is set to a string then that must be the theme handler for it
		elseif(is_string($template))
			theme($template);
			
		// if there isn't anything else, call the theme function and maybe it will just display the default blank page
		else
			theme();
	}
	else
	{
		header('Status: 404');
		raise_error('Not Found!', E_DEBUG|E_USER);

		theme();
		return;
	}
}


function add_menu($module, $menus)
{
	if(isset($menus))
	{
		// loop through each menu and add default information
		foreach($menus as $path => &$config)
		{
			// reform simple menu definition
			if(is_string($config))
				$menus[$path] = array(
					'name' => $config,
					'callback' => 'output_' . generic_validate_machine_readable(array('callback' => $path), 'callback'),
				);
			
			// set default type
			if(!isset($config['type']))
			{
				// menu entrys with wildcards in them default to callbacks
				if(strpos($path, '%') !== false)
					$menus[$path]['type'] = MENU_CALLBACK;
				// otherwise they default to normal entries
				else
					$menus[$path]['type'] = MENU_NORMAL;
			}
			
			// add intermediate menu lists
			if($menus[$path]['type'] == MENU_NORMAL)
			{
				// loop through each dir and find gaps in menus
				$dirs = split('/', $path);
				$current = '';
				// this can be very exhaustive because if the menu is found later it will just replace it
				foreach($dirs as $i => $item)
				{
					$current .= $item;
					if(!isset($menus[$current]) && !isset($GLOBALS['menus'][$current]))
					{
						$menus[$current] = array(
							'type' => MENU_LIST,
							'name' => ucwords(str_replace('/', ' ', $current)),
							'description' => ucwords(str_replace('/', ' ', $current)) . ' Menu List',
							'callback' => 'output_menu',
							'module' => 'menu',
						);
					}
					$current .= '/';
				}
			}
			
			// add menu to menu because its easy to do it here
			if(!isset($config['name']))
				$menus[$path]['name'] = $GLOBALS['modules'][$module]['name'];
				
			if(!isset($config['description']))
				$menus[$path]['description'] = $GLOBALS['modules'][$module]['description'];
				
			// add module to menu info
			$menus[$path]['module'] = $module;
		}
		$GLOBALS['menus'] = array_merge($GLOBALS['menus'], $menus);
	}
}


/**
 * This is the always output function
 */
function output_menus($request)
{
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url($_GET, true));
	
	$path = get_menu_entry($request['path_info']);

	// the entire site depends on this
	register_output_vars('module', $GLOBALS['menus'][$path]['module']);
	
	// register all menus for reference
	register_output_vars('all_menus', $GLOBALS['menus']);
	
	// create a tree of all the menus based on path levels
	$menus = array();
	foreach($GLOBALS['menus'] as $path => $config)
	{
		// only add menus that are normal types and menu types
		if($config['type'] == MENU_NORMAL || $config['type'] == MENU_LIST)
		{
			$menus[$path] = $config;
		}
	}
	
	register_output_vars('menus', $menus);
}

function output_menu($request)
{
	$current_menu = array();
	
	// output menu template instead of template from module
	$current = get_menu_entry($request['path_info']);

	// only output menus under current menu item
	foreach($GLOBALS['menus'] as $path => $config)
	{
		// only show normal menus and menu lists
		if(($config['type'] == MENU_NORMAL || $config['type'] == MENU_LIST) && 
			// only show menus that begin with the current menu
			substr($path, 0, strlen($current)) == $current &&
			// do not show the current menu item in this list
			$current != $path && 
			// only show menus that are at this level, not lower levels
			strpos($path, '/', strlen($current) + 1) === false
		)
			// add to current menu
			$current_menu[$path] = $config;
	}
	
	// registers
	register_output_vars('current_menu', $GLOBALS['menus'][$current]);
	register_output_vars('menu', $current_menu);
}

