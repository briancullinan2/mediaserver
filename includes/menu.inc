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


function preload_menu()
{
	// define constants for use with menu functions to specify to the template if the menu should be shown
	define('MENU_CALLBACK', 	0);
	define('MENU_LIST', 		1);
	define('MENU_NORMAL', 		2);
	
	if(!isset($GLOBALS['menus']))
		$GLOBALS['menus'] = array();
}

function bootstrap_menu($module)
{
	if(dependency($module))
		$result = invoke_module('menu', $module);
		
	if(isset($result))
		add_menu($module, $result);

	// add default menu items
	if(!isset($GLOBALS['menus'][$module]))
		add_menu($module, array($module => array(
			'name' => get_module($module, 'name'),
			'module' => $module,
			'callback' => 'output_' . $module,
			'type' => MENU_CALLBACK,
		)));
}

function validate_path_info($request)
{
	if(isset($request['path_info']))
	{
		// remove leading/trailing slash
		$request['path_info'] = trim($request['path_info'], '/');
		
		// look up the menu item
		$result = get_menu_entry($request['path_info']);
		if(isset($result))
			return $request['path_info'];
	}
	return 'select';
}

function validate_is_callback($request)
{
	return isset($request['is_callback']) && filter_var($request['is_callback'], FILTER_VALIDATE_BOOLEAN);
}

function validate_menus_only($request)
{
	if(isset($request['menus_only']) && filter_var($request['menus_only'], FILTER_VALIDATE_BOOLEAN))
		return true;
}

function get_menu_entry($current_path)
{
	if(!isset($GLOBALS['menus']))
		setup_menu();

	// find the menu entry that matches the most
	$highest_match = '';
	foreach($GLOBALS['menus'] as $path => $config)
	{
		if($path == '')
			continue;
			
		$path_regexp = preg_replace(array('/\\//i', '/\\/%[a-z][a-z0-9_]*/i'), array('\/', '/[^\/]*?'), addslashes($path));
		if(preg_match('/^' . $path_regexp . '/i', $current_path) > 0)
		{
			// get path that matches most number of variables
			if(substr_count($path, '/') > substr_count($highest_match, '/') || 
				// if the same number of variables choose the one that has the least number of variables in it
				(substr_count($path, '/') == substr_count($highest_match, '/') && substr_count($path, '%') < substr_count($highest_match, '%')) ||
				// highest_match hasn't been set yet
				$highest_match == '')
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
	// check request for path info
	$request['path_info'] = validate($request, 'path_info');
	
	if($path = get_menu_entry($request['path_info']))
	{
		$user = session('users');
        $user['Privilage'] = 10;
		$menu = $GLOBALS['menus'][$path];
		$module = get_module($menu['module']);
		if(isset($menu['template']))
			$template = $menu['template'];
		else
			$template = $module['template'];

		// TODO: fix this, change all permissions to use users.inc module API, redirect to login
		if($user['Privilage'] < $module['privilage'])
		{
			location('users/login?required_priv=' . $module['privilage'] . '&return=' . $request['path_info']);
		}
		// permissions are ok
		if($user['Privilage'] >= $module['privilage'])
		{
			raise_error('Invoking path: ' . $path . ' : ' . $menu['callback'], E_DEBUG);
			// call the callback specified
			if(is_callable($menu['callback']) && setting($menu['module'] . '_enable') != false)
			{
				// if the keys are named, merge with request
				if(isset($menu['arguments']) && array_keys($menu['arguments']) === range(0, count($menu['arguments']) - 1))
					call_user_func_array($menu['callback'], array_merge($menu['arguments'], array($request)));
				// if the keys are numeric, they take the place of arguments
				elseif(isset($menu['arguments']))
					call_user_func_array($menu['callback'], array(array_merge($menu['arguments'], $request)));
				else
					call_user_func_array($menu['callback'], array($request));
			}
			
			// if there are dependency issues
			elseif(!dependency($menu['module']) && !setting($menu['module'] . '_enable'))
			{
				raise_error('The selected module has dependencies that are not met! <a href="' .
					url('admin/module/' . $menu['module']) . '">Configure</a> this module.'
				, E_DEBUG|E_USER);
				
				theme('errors');
				return;
			}
			
			raise_error('Path invokation complete.  Themeing.', E_DEBUG);
		}
		else
			$template = 'menu';

		// if the request is a menu's only request, use the path info and set the template
		if(validate($request, 'menus_only'))
			$template = 'menu';
		// just return because the output function was already called
		if(!$template)
			return;

		// close the session so that other scripts can load
		if(isset($_SESSION)) session_write_close();
		
		// compress the output
		if(setting('deflate') && !headers_sent())
		{
			// this prevents browsers from showing encoding errors
			$contents = ob_get_contents();
			if($contents)
				ob_end_clean();
			raise_error('Deflating.', E_DEBUG);
			ob_start("ob_gzhandler");
			print $contents;
		}
		else
			raise_error('Deflating not supported!', E_DEBUG);
			
		// check module permissions and always do access denied before calling any of the other themes
		if($user['Privilage'] < $module['privilage'])
		{
			raise_error('Access Denied!', E_USER);
			
			theme('login');
			return;
		}
		// if it is set to a callable function to determine the template, then call that
		elseif(is_callable($template))
			call_user_func_array($template, array($request));

		// call the default template based on the module name
		elseif(filter_var($template, FILTER_VALIDATE_BOOLEAN))
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
			$dirs = explode('/', $path);
			$current = '';
			// this can be very exhaustive because if the menu is found later it will just replace it
			foreach($dirs as $i => $item)
			{
				$current .= $item;
				if(!isset($menus[$current]) && !isset($GLOBALS['menus'][$current]))
				{
					$menus[$current] = array(
						'type' => MENU_LIST,
						'name' => ucwords(str_replace('/', ' ', $item)),
						'description' => ucwords(str_replace('/', ' ', $current)) . ' Menu List',
						'callback' => 'output_menu',
						'module' => 'menu',
					);
				}
				$current .= '/';
			}
		}
		elseif($menus[$path]['type'] == MENU_LIST)
		{
			$menus[$path]['callback'] = 'output_menu';
			$menus[$path]['module'] = 'menu';
		}
		
		// add name to menu because its easy to do it here
		if(!isset($config['name']))
			$menus[$path]['name'] = get_module($module, 'name');
			
		if(!isset($config['description']))
			$menus[$path]['description'] = get_module($module, 'description');
			
		// add module to menu info
		if(!isset($menus[$path]['module']))
			$menus[$path]['module'] = $module;
			
		// merge with GLOBAL working copy
		// only merge name and icon if something has overriden then menu, this is for safety
		if(isset($GLOBALS['menus'][$path]))
		{
			if(isset($menus[$path]['name']))
				$GLOBALS['menus'][$path]['name'] = $menus[$path]['name'];
			if(isset($menus[$path]['icon']))
				$GLOBALS['menus'][$path]['icon'] = $menus[$path]['icon'];
		}
		else
			$GLOBALS['menus'][$path] = &$menus[$path];
	}

}

function output_breadcrumbs($current_path)
{
	// create a list of breadcrumbs that can be overrided by other modules
	$breadcrumbs = array();
	$dirs = explode('/', $current_path);
	$path = '';
	foreach($dirs as $i => $dir)
	{
		$path .= (($path != '')?'/':'') . $dir;
		if(isset($GLOBALS['menus'][$path]))
			$breadcrumbs[$path] = $GLOBALS['menus'][$path];
	}
	if(!isset($GLOBALS['output']['breadcrumbs']))
		register_output_vars('breadcrumbs', $breadcrumbs);
}

/**
 * This is the always output function
 */
function output_menus($request)
{
	// set is callback for use in template
	if(validate($request, 'is_callback'))
		register_output_vars('is_callback', true);
		
	// set is callback for use in template
	if(validate($request, 'menus_only'))
		register_output_vars('menus_only', true);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url(array('path_info' => $request['path_info']) + $_GET, true));
	
	$current_path = get_menu_entry($request['path_info']);

	// the entire site depends on this
	register_output_vars('module', $GLOBALS['menus'][$current_path]['module']);
	
	// register all menus for reference
	register_output_vars('all_menus', $GLOBALS['menus']);
	
	// create a tree of all the menus based on path levels
	$menus = array();
	foreach($GLOBALS['menus'] as $path => $config)
	{
		// only add menus that are normal types and menu types
		if($config['type'] == MENU_NORMAL || $config['type'] == MENU_LIST)
		{
			// translate menu
			$config['name'] = lang($config['name'], 'menu ' . machine($path));
			$config['description'] = lang($config['description'], 'menu description ' . machine($path));
			$menus[$path] = $config;
		}
	}
	register_output_vars('menus', $menus);
	
	// always output current menu for use
	$current = get_menu_entry($request['path_info']);

	// registers
	register_output_vars('current_menu', $GLOBALS['menus'][$current]);
	register_output_vars('menu', get_menu($current));
	
	output_breadcrumbs($request['path_info']);
}

function get_menu($current)
{
	$current_menu = array();

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
		{
			// translate menu
			$config['name'] = lang($config['name'], 'menu ' . machine($config['name']));
			$config['description'] = lang($config['description'], 'menu description ' . machine($path));
			
			// add to current menu
			$current_menu[$path] = $config;
		}
	}
	
	return $current_menu;
}

function output_menu($request)
{
	// output menu template instead of template from module
}

function theme__breadcrumbs($crumb = NULL)
{
	?>
	<ul>
		<li><a href="<?php print url('select'); ?>"><?php print setting('html_name'); ?></a></li>
		<?php
		if(count($GLOBALS['output']['breadcrumbs']) == 0)
		{
			?><li><strong><?php print get_module($GLOBALS['output']['module'], 'name'); ?></strong></li><?php
		}
		else
		{
			$count = 0;
			foreach($GLOBALS['output']['breadcrumbs'] as $path  => $menu)
			{
				if($count != count($GLOBALS['output']['breadcrumbs']) - 1)
				{
					?>
					<li><a href="<?php print url($path); ?>"><?php print $menu['name']; ?></a></li>
					<?php
				}
				else
				{
					?><li><strong><?php print $menu['name']; ?></strong></li><?php
				}
				$count++;
			}
		}
	?></ul><?php
}

function theme_tunegazelle_menu()
{
	if(!isset($GLOBALS['output']['menus_only']) || !$GLOBALS['output']['menus_only'])
	{
		theme('header');
		
		theme('menu_block');
		
		theme('footer');
	}
	else
	{
		theme('menu_block');
	}
}

function theme__menu_block($menu = NULL)
{
	if(!isset($menu) && isset($GLOBALS['output']['menu']))
		$menu = $GLOBALS['output']['menu'];
		
	if(isset($menu))
	{
		?><ul><?php
		
		foreach($menu as $path => $config)
		{
			if(!empty($config['icon']))
			{
				?><li><span class="icon" style="background-image:url('<?php print url($config['icon']); ?>');"></span><a href="<?php print url($path); ?>"><?php echo $config['name']; ?></a></li><?php
			}
			else
			{
				?><li><a href="<?php print url($path); ?>"><?php echo $config['name']; ?></a></li><?php
			}
		}
		
		?></ul><?php
	}
}
