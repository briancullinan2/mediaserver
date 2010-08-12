<?php


/**
 * Helper function
 */
function get_required_modules()
{
	return array('core', 'index', 'users', 'select', 'settings', 'file', 'template');
}


/**
 * Abstracted from setup_modules <br />
 * Generates a list of modules from a specified directory <br />
 * This allows for modules to load more modules, such as admin_tools
 * @ingroup setup
 * @param path The path to load modules from
 * @return An array containing only the modules found in the specified directory, modules loaded are also added to the GLOBAL list of modules
 */
function load_modules($path)
{
	if(is_dir(setting_local_root() . $path))
	{
		$files = get_filesystem(array(
			'dir' => setting_local_root() . $path,
			'depth' => 3,
			'limit' => 32000,
			'match' => '/\.info$/i',
		), $count);
	}
	elseif(is_file(setting_local_root() . $path))
		$files = array(get_info_files(setting_local_root() . $path));

	if(!is_array($files))
	{
		raise_error('Error loading modules from \'' . $path . '\'.', E_DEBUG);
		
		return array();
	}

	$modules = array();
	
	// loop through files and add modules to global list
	foreach($files as $i => $file)
	{
		$config = load_module($file['Filepath']);
		$modules[$config['module']] = $config;
	}
	
	// merge with global list
	if(!isset($GLOBALS['modules']))
		$GLOBALS['modules'] = array();
	$GLOBALS['modules'] = array_merge($modules, $GLOBALS['modules']);
	
	$GLOBALS['modules'] = sort_modules($GLOBALS['modules']);

	return sort_modules($modules);
}

function load_module($path)
{
	if(is_file($path))
	{
		// get filename without extension
		$module = substr(basename($path), 0, strrpos(basename($path), '.'));
		
		// get machine readable name from module
		$module = generic_validate_machine_readable(array('module' => $module), 'module');
		
		// functional prefix so there can be multiple modules with the same name
		$prefix = substr($path, strlen(setting_local_root()), -strlen(basename($path)));
		
		// remove slashes and replace with underscores
		$prefix = generic_validate_machine_readable(array('prefix' => $prefix), 'prefix');
		
		// use output buffer to prevent unwanted output
		ob_start();
			
		// call register function
		if(substr($path, -5) == '.info')
		{
			$config = parse_ini_file($path, true);
			
			include_once dirname($path) . DIRECTORY_SEPARATOR . $module . '.php';
		}
		elseif(substr($path, -4) == '.php')
		{
			include_once $path;
			
			if(function_exists('register_' . $module))
				$config = invoke_module('register', $module);
			else
				raise_error('Module \'' . $path . '\' does not contain a register function!', E_DEBUG);
				
		}
		
		// check output buffer and report
		$buffer = ob_get_contents();
		if(strlen($buffer) > 0)
			raise_error('Output detected while loading modules \'' . $path . '\'.', E_VERBOSE);
		ob_end_clean();
		
		// set the module name for reference
		$config['module'] = $module;
	
		// set the path to the module
		if(!isset($config['path']))
			$config['path'] = dirname($path) . DIRECTORY_SEPARATOR . $module . '.php';
		
		// set the package if it is not set already
		if(!isset($config['package']))
		{
			if($prefix != '')
				$config['package'] = substr($prefix, 0, -1);
			else
				$config['package'] = 'other';
		}
		
		// loop through all available triggers and call register
		foreach($GLOBALS['triggers'] as $trigger => $triggers)
		{
			register_trigger($trigger, $config, $module);
		}
		
		return $config;
	}
	else
	{
		raise_error('Failed to load \'' . $path . '\'.', E_DEBUG);
		
		return array();
	}
}

function is_module($module)
{
	return isset($GLOBALS['modules'][$module]);
}

function get_dependencies($dependency, $modules_only = false)
{
	$depends = array();
	
	if(isset($GLOBALS['modules'][$dependency]))
	{
		$config = $GLOBALS['modules'][$dependency];
		
		if(isset($config['depends on']))
		{
			if(is_string($config['depends on']) && $config['depends on'] == $dependency &&
				function_exists('dependency_' . $config['depends on'])
			)
				$depends = invoke_module('dependency', $dependency, array($GLOBALS['settings']));
			elseif(is_array($config['depends on']))
				$depends = $config['depends on'];
			else
				$depends = array();
				
			// filter out modules if option is true
			if($modules_only)
			{
				$depends = array_filter($depends, 'is_module');
			}
		}
	}
	
	return $depends;
}

function get_ordered_dependencies($module, $already_added = array())
{
	$return = array();
	
	if(!in_array($module, $already_added))
	{
		$already_added[] = $module;
		$depends = get_dependencies($module, true);
		foreach($depends as $i => $depend)
		{
			$return = array_merge($return, get_ordered_dependencies($depend, $already_added));
		}
	}
		
	$return[] = $module;
	
	return $return;
}


function sort_modules($modules)
{
	$ordered = array('core');

	foreach($modules as $module => $config)
	{
		$ordered = array_merge($ordered, get_ordered_dependencies($module));
	}
	
	// intersect with available module information so as not to create blank entries on merge
	$ordered = array_intersect_key(array_flip(array_unique($ordered)), $modules);

	// merge ordered keys with module configs
	return array_merge($ordered, $modules);
}

function load_include($path)
{
}

/**
 * Function for returning all the modules that match certain criteria
 */
function get_modules($package = NULL)
{
	$modules = array();
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if($package === NULL || $config['package'] == $package)
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for getting all the modules that implement a specified API function
 */
function get_modules_implements($method)
{
	$modules = array();
	
	// check if function exists
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(function_exists($method . '_' . $module))
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for matching properties on a module using regular expression and the serialized function
 */
function get_modules_match($expression)
{
	$modules = array();
	
	// if it is not an object or a string, then serialize it in order to match it against modules
	if(!is_string($expression))
		$expression = '/' . addslashes(serialize($expression)) . '/i';
		
	// make sure it is valid regular expression
	$expression = generic_validate_regexp(array('package' => $expression), 'package');
	
	// if it is valid
	if(isset($expression))
	{
		// loop through all the modules and match expression
		foreach($GLOBALS['modules'] as $module => $config)
		{
			if(preg_match($expression, serialize($config)) != 0)
				$modules[$module] = $config;
		}
	}
	
	return $modules;
}

function invoke_module($method, $module, $args = NULL)
{
	/*$args = array();
	for($i = 0; $i < func_num_args(); $i++)
	{
		if($i > 1)
			$args[] = &func_get_arg($i);
	}
	*/
	//$args = func_get_args();
	//unset($args[1]);
	//unset($args[0]);
	if(function_exists($method . '_' . $module))
	{
		return call_user_func_array($method . '_' . $module, $args);
	}
	else
		raise_error('Invoke \'' . $method . '\' called on \'' . $module . '\' but dependencies not met or function does not exist.', E_VERBOSE);
}

/**
 * Function for invoking an API call on all modules and returning the result
 * @param method Method to call on all modules
 * @param list_return_values list all the return values from each module separately
 * @return true if not listing return values and it succeeded, returns false if any module fails, returns associative array if listing each value
 */
function invoke_all($method)
{
	$args = func_get_args();
	
	// remove method name
	unset($args[0]);
	
	raise_error('Modules invoked with \'' . $method . '\'.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if((dependency($module) || in_array($module, get_required_modules())) && function_exists($method . '_' . $module))
		{
			$result = call_user_func_array($method . '_' . $module, $args);
		}
	}
}

function invoke_all_callback($method, $callback)
{
	$args = func_get_args();
	
	// remove method name
	unset($args[1]);
	unset($args[0]);
	
	raise_error('Modules invoked with \'' . $method . '\' and a callback function supplied.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// keep track of error count and report which module fired it
		extract(array(
			'user_count' => count($GLOBALS['user_errors']),
			'warn_count' => count($GLOBALS['warn_errors']),
			'note_count' => count($GLOBALS['note_errors'])
		));
		
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if((dependency($module) || in_array($module, get_required_modules())) && function_exists($method . '_' . $module))
		{
			$result = call_user_func_array($method . '_' . $module, $args);
			
			if(is_callable($callback))
				call_user_func_array($callback, array($module, $result, $args));
		}
		
		// send debug information to console
		if($user_count < count($GLOBALS['user_errors']))
			raise_error('User error affected by \'' . $module . '\'.', E_DEBUG);
		if($warn_count < count($GLOBALS['warn_errors']))
			raise_error('Warn error affected by \'' . $module . '\'.', E_DEBUG);
		if($note_count < count($GLOBALS['note_errors']))
			raise_error('Note error affected by \'' . $module . '\'.', E_DEBUG);
	}
}
 
function disable_module($module)
{
	if(dependency($module) == false)
	{
		$GLOBALS['settings'][$module . '_enable'] = false;
		// this prevents us from disabling required modules on accident
		$GLOBALS['settings'][$module . '_enable'] = setting($module . '_enable');
	}
}
