<?php

function register_menu()
{
	return array(
		'name' => lang('menu title', 'Menus'),
		'description' => lang('menu description', 'Interface for configuring the site menu.'),
		'privilage' => 1,
		'path' => __FILE__,
		'package' => 'core',
		'output' => 'output_menu',
	);
}


function setup_menu()
{
	if(!isset($GLOBALS['menus']))
		$GLOBALS['menus'] = array();
		
	// get menus defined by modules
	invoke_all_callback('menu', 'add_menu');

	// loop through modules and process menu items
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// add default menu items
		if(!function_exists('output_' . $module))
			$GLOBALS['menus'][$module] = array(
				'name' => $config['name'],
				'module' => $module,
				'callback' => 'configure_' . $module,
			);
		else
			$GLOBALS['menus'][$module] = array(
				'name' => $config['name'],
				'module' => $module,
				'callback' => 'output_' . $module,
			);
	
		// add intermediate menu items
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
	return 'core';
}


function get_menu_entry($path)
{
	if(!isset($GLOBALS['menus']))
		setup_menu();
	
	$dirs = split('/', $path);

	// find the menu entry that matches the most
	foreach($dirs as $i => $dir)
	{
		foreach($GLOBALS['menus'] as $path => $config)
		{
			if(preg_match('/' . preg_replace(array('/\\//i', '/\\/%[a-z][a-z0-9_]*/i'), array('\/', '/[^\/]*?'), addslashes($path)) . '/i', implode('/', $dirs)) > 0)
				return $path;
		}
	}
}

function get_path(&$request, $index)
{
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
		$menu = $GLOBALS['menus'][$path];
		$module = $GLOBALS['modules'][$menu['module']];
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
				
				theme();
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
		elseif($template == true)
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
	foreach($menus as $path => &$config)
	{
		if(is_string($config))
			$menus[$path] = array(
				'name' => $config,
				'callback' => 'output_' . generic_validate_machine_readable(array('callback' => $path), 'callback'),
			);
		
		// add menu to menu because its easy to do it here
		if(!isset($config['name']))
			$menus[$path]['name'] = $GLOBALS['modules'][$module]['name'];
			
		// add module to menu info
		$menus[$path]['module'] = $module;
	}
	$GLOBALS['menus'] = array_merge($menus, $GLOBALS['menus']);
}


function output_menu($request)
{
	if($path = get_menu_entry($request['path_info']))
	{
		// the entire site depends on this
		register_output_vars('module', $GLOBALS['menus'][$path]['module']);
	}
	register_output_vars('menus', $GLOBALS['menus']);
}


function theme_menu_block()
{
	if(isset($GLOBALS['templates']['vars']['menus']))
	{
		?>
		Menu:<br />
		<ul>
		<?php
		
		foreach($GLOBALS['templates']['vars']['menus'] as $path => $config)
		{
			// this path actually leads to output as is, no need to validate it by using url()
			if(strpos($path, '%') === false)
			{
				?><li><a href="<?php print $path; ?>"><?php echo $config['name']; ?></a></li><?php
			}
		}
		
		?></ul><?php
	}
}

