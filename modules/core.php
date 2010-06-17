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
 * Implementation of register
 */
function register_core()
{
	// register permission requirements
	
	// register the request variables we will be providing validators for
	
	// this module has no output
	return array(
		'name' => lang('core title', 'Core Functions'),
		'description' => lang('core description', 'Adds core functionality to site that other common modules depend on.'),
		'path' => __FILE__,
		'privilage' => 1,
		'settings' => array(
			'installed', 'system_type', 'local_root', 'settings_file', 'modrewrite', 
		),
		'depends on' => array(
			'memory_limit', 'writable_system_files', 'pear_installed',
			// move these to the proper handlers when it is implemented
			'getid3_installed', 'curl_installed', 'extjs_installed'
		),
		'always output' => 'core_variables',
	);
}
/**
 * @}
 */

/**
 * Create a global variable for storing all the module information
 * @ingroup setup
 */
function setup_core()
{
	// include a couple of modules ahead of time
	// language must be included so that we can translate module definitions
	include_once setting_local_root() . 'modules' . DIRECTORY_SEPARATOR . 'language.php';

	// include the database module, because it acts like a module, but is kept in the includes directory
	include_once setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'database.php';

	// include the settings module ahead of time because it contains 1 needed function setting_settings_file()
	include_once setting_local_root() . 'modules' . DIRECTORY_SEPARATOR . 'settings.php';

	// add a couple of modules that don't have register functions, yet?
	$GLOBALS['modules'] = array(
		'index' => array(
			'name' => lang('index title', 'Index'),
			'description' => lang('index description', 'Load a module\'s output variables and display the template.'),
			'privilage' => 1,
			'path' => dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'index.php',
			'alter query' => array('limit', 'start', 'direction', 'order_by', 'group_by'),
			'settings' => array(
				'html_domain', 'html_root', 'html_name', 'tmp_dir',
				'debug_mode', 'recursive_get', 'no_bots', 'buffer_size', 'verbose'
			),
			'depends on' => array('template'),
			'template' => true,
		),
		'database' => register_database(),
	);
	$GLOBALS['triggers'] = array(
		'session' => array(),
		'settings' => array(),
		'always output' => array(),
		'alter query' => array()
	);
	
	// set up the mime information
	setup_mime();
	
	// read module list and create a list of available modules	
	setup_register_modules('modules' . DIRECTORY_SEPARATOR);
	
	$shuffle = array_keys($GLOBALS['modules']);
	shuffle($shuffle);
	
	$GLOBALS['modules'] = array_merge(array_flip($shuffle), $GLOBALS['modules']);
	
	// resort modules to reflect their dependencies, aka inefficient sort
	$GLOBALS['modules'] = array_merge(array_flip(flatten_module_dependencies(array_keys($GLOBALS['modules']))), $GLOBALS['modules']);

	// always make the module list available to templates
	register_output_vars('modules', $GLOBALS['modules']);
}

/**
 * Creates a list of modules with the order of their dependencies first
 * @param modules the list of modules to recursively loop through
 * @return an array of modules sorted by dependency
 */
function flatten_module_dependencies($modules, $already_added = array())
{
	$new_modules = array('core');
	foreach($modules as $i => $module)
	{
		// only deal with modules
		if(!isset($GLOBALS['modules'][$module]))
			continue;
		
		if(isset($GLOBALS['modules'][$module]['depends on']) && !in_array($module, $already_added))
		{
			// add to list to prevent recursion
			$already_added[] = $module;
			
			// if the dependency field is set to the module name, and a valid callback exists, call that to determine list of dependencies
			if(is_string($GLOBALS['modules'][$module]['depends on']) && $GLOBALS['modules'][$module]['depends on'] == $module &&
				function_exists('dependency_' . $module)
			)
				$depends_on = call_user_func_array('dependency_' . $module, array($GLOBALS['settings']));
			else
				$depends_on = $GLOBALS['modules'][$module]['depends on'];
				
			// if it is not an array debug message
			if(!is_array($depends_on))
				$depends_on = array();
			
			// call flatten based on modules dependencies first
			$new_modules = array_merge($new_modules, flatten_module_dependencies($depends_on, $already_added));
		}
		$new_modules[] = $module;
	}
	return array_values(array_unique($new_modules));
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
	$files = get_files(array('dir' => setting_local_root() . $path, 'limit' => 32000), $count, true);
	
	$modules = array();
	
	// loop through files and add modules to global list
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
				$prefix = substr($file['Filepath'], strlen(setting_local_root()), -strlen($module));
				
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
				if(isset($modules[$module]['session']) && is_array($modules[$module]['session']))
				{
					foreach($modules[$module]['session'] as $i => $var)
					{
						if(is_numeric($i))
							$GLOBALS['triggers']['session'][$var][$module] = 'session_' . $prefix . $module;
						elseif(is_callable($var))
							$GLOBALS['triggers']['session'][$i][$module] = $var;
					}
				}
				
				// reorganize alter query triggers
				if(isset($modules[$module]['alter query']) && is_array($modules[$module]['alter query']))
				{
					foreach($modules[$module]['alter query'] as $i => $var)
					{
						if(is_numeric($i))
							$GLOBALS['triggers']['alter query'][$var][$module] = 'alter_query_' . $prefix . $module;
						elseif(is_callable($var))
							$GLOBALS['triggers']['alter query'][$i][$module] = $var;
					}
				}
				
				// reorganize alter query triggers
				if(isset($modules[$module]['always output']) && is_array($modules[$module]['always output']))
				{
					// for named arrays, the key variable will call the named function
					foreach($modules[$module]['always output'] as $i => $var)
					{
						if(is_numeric($i))
							$GLOBALS['triggers']['always output'][$var][$module] = 'output_' . $prefix . $module;
						elseif(is_callable($var))
							$GLOBALS['triggers']['always output'][$i][$module] = $var;
					}
				}
			}
		}
	}
	
	return $modules;
}


/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default, set to true to record all notices
 */
function setting_verbose($settings)
{
	if(isset($settings['verbose']))
	{
		if($settings['verbose'] === true || $settings['verbose'] === 'true')
			return true;
		elseif($settings['verbose'] === false || $settings['verbose'] === 'false')
			return false;
	}
	return false;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 'win' by default if it can't determine the OS
 */
function setting_system_type($settings)
{
	if(isset($settings['system_type']) && ($settings['system_type'] == 'mac' || $settings['system_type'] == 'nix' || $settings['system_type'] == 'win'))
		return $settings['system_type'];
	else
	{
		if(realpath('/') == '/')
		{
			if(file_exists('/Users/'))
				return 'mac';
			else
				return 'nix';
		}
		else
			return 'win';
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return Always returns true if there is a settings file and it is readable
 */
function setting_installed()
{
	$settings = setting_settings_file();
	// make sure the database isn't being used and failed
	return (file_exists($settings) && is_readable($settings) && (setting('database_enable') == false || dependency('database') != false));
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return Always returns the parent directory of the current modules or admin directory
 */
function setting_local_root()
{
	return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The domain information set by the SERVER by default
 */
function setting_html_domain($settings)
{
	if(isset($settings['html_domain']) && @parse_url($settings['html_domain']) !== false)
		return $settings['html_domain'];
	else
		return strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['HTTP_HOST'] . (($_SERVER['SERVER_PORT'] != 80)?':' . $_SERVER['SERVER_PORT']:'');
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The working path to this installer minus the server path
 */
function setting_html_root($settings)
{
	$settings['html_domain'] = setting_html_domain($settings);
	$settings['local_root'] = setting_local_root($settings);
	if(substr($_SERVER['DOCUMENT_ROOT'], -1) != '/' && substr($_SERVER['DOCUMENT_ROOT'], -1) != '\\') $_SERVER['DOCUMENT_ROOT'] .= DIRECTORY_SEPARATOR;
	
	if(isset($settings['html_root']) && @parse_url($settings['html_domain'] . $settings['html_root']) !== false)
		return $settings['html_root'];
	else
		return '/' . ((substr($settings['local_root'], 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'])?substr($settings['local_root'], strlen($_SERVER['DOCUMENT_ROOT'])):'');
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_html_name($settings)
{
	if(isset($settings['html_name']))
		return $settings['html_name'];
	else
		return 'Brian\'s Media Website';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default
 */
function setting_debug_mode($settings)
{
	if(isset($settings['debug_mode']))
	{
		if($settings['debug_mode'] === true || $settings['debug_mode'] === 'true')
			return true;
		elseif($settings['debug_mode'] === false || $settings['debug_mode'] === 'false')
			return false;
	}
	return false;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default
 */
function setting_recursive_get($settings)
{
	if(isset($settings['recursive_get']))
	{
		if($settings['recursive_get'] === true || $settings['recursive_get'] === 'true')
			return true;
		elseif($settings['recursive_get'] === false || $settings['recursive_get'] === 'false')
			return false;
	}
	return false;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return true by default
 */
function setting_no_bots($settings)
{
	if(isset($settings['no_bots']))
	{
		if($settings['no_bots'] === true || $settings['no_bots'] === 'true')
			return true;
		elseif($settings['no_bots'] === false || $settings['no_bots'] === 'false')
			return false;
	}
	return true;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 16MB by default
 */
function setting_buffer_size($settings)
{
	if(isset($settings['buffer_size']['value']) && isset($settings['buffer_size']['multiplier']) && 
		is_numeric($settings['buffer_size']['value']) && is_numeric($settings['buffer_size']['multiplier'])
	)
		$settings['buffer_size'] = $settings['buffer_size']['value'] * $settings['buffer_size']['multiplier'];
	
	if(isset($settings['buffer_size']) && is_numeric($settings['buffer_size']) && $settings['buffer_size'] > 0)
		return $settings['buffer_size'];
	else
		return 2*1024*8;
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_modrewrite($settings)
{
	return (isset($_REQUEST['modrewrite']) && $_REQUEST['modrewrite'] == true);
}

/**
 * @defgroup dependency Dependency Functions
 * All functions that allow the system to check other system settings for module dependencies
 * @param settings the settings for dependencies to take advantage of, but not necissarily contrained to
 * @return Usually true or false, in some cases, returns status information about the dependency
 * for example, the db_code handler depends on a syntax highlighter, it can use either PEAR or Geshi
 * for highlighting code, so the dependency function would return false, if neither is installed, 
 * but may return a path to the library is one of them is installed, or true if both are installed.
 * This can be interpreted by the configuration page and offer an option if both are installed, or fail if it is false
 * @{
 */
 
/**
 * Abstracted to prevent errors and display debug information, this is minimally useful since modules call their own dependencies
 * @param dependency Either the name of a module, or the name of a dependency
 * @return A dependency, unless the input is a module, then it returns true or false if the module's dependencies are all satisfied
 */
function dependency($dependency, $ignore_setting = false, $already_checked = array())
{
	// check if the caller is trying to verify the dependencies of another module
	if(isset($GLOBALS['modules'][$dependency]))
	{
		$config = $GLOBALS['modules'][$dependency];
	}
	// check to see if the dependecy is a handler
	elseif(isset($GLOBALS['handlers'][$dependency]))
	{
		$config = $GLOBALS['handlers'][$dependency];
	}

	if(isset($config))
	{	
		// if the depends on field is a string, call the function and use that as the list of dependencies
		if(isset($config['depends on']) && is_string($config['depends on']))
		{	
			if($config['depends on'] == $dependency && function_exists('dependency_' . $config['depends on']))
			{
				$config['depends on'] = call_user_func_array('dependency_' . $dependency, array($GLOBALS['settings']));
			}
			else
			{
				PEAR::raiseError('Function dependency_\'' . $dependency . '\' is specified but the function does not exist.', E_DEBUG);
			}
		}
		
		if(isset($config['depends on']) && is_array($config['depends on']))
		{
			// now loop through the modules dependencies only, and make sure they are all met
			foreach($config['depends on'] as $i => $depend)
			{
				//  uses a backup check to prevent recursion and errors if there is
				if(in_array($depend, $already_checked))
				{
					// log the repitition
					PEAR::raiseError('The dependency \'' . $depend . '\' has already been verified when checking the dependencies of \'' . $dependency . '\'!', E_DEBUG);
					
					// checking it twice is unnessicary since it should have failed already
					continue;
				}
				
				// check for false strictly, anything else should be taken as status information
				//  this is also recursive so that if one module fails everything that depends on it will fail
				$already_checked[] = $depend;
				// only ignore first dependency request
				if(dependency($depend, false, $already_checked) === false)
				{
					// no need to continue as it only takes 1 to fail
					return false;
				}
			}
		}
		
		// return false if a module is disabled
		if(isset($GLOBALS['settings'][$dependency . '_enable']) && $GLOBALS['settings'][$dependency . '_enable'] == false && $ignore_setting == false)
			return false;
		
		// if it has gotten this far through all the disproofs then it must be satisfied
		return true;
	}
	
	
	// call dependency function
	if(function_exists('dependency_' . $dependency))
		return call_user_func_array('dependency_' . $dependency, array($GLOBALS['settings']));
	if(isset($GLOBALS['dependency_' . $dependency]) && is_callable($GLOBALS['dependency_' . $dependency]))
		return $GLOBALS['dependency_' . $dependency]($GLOBALS['settings']);
	else
		PEAR::raiseError('Dependency \'' . $dependency . '\' not defined!', E_DEBUG);
		
	return false;
}
 
/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if the system has enough memory to operate properly
 */
function dependency_memory_limit()
{
	return (intval(ini_get('memory_limit')) >= 96);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if there are any critical files to the system that are writable and could cause a security threat
 */
function dependency_writable_system_files($settings)
{
	// try to make this function return false
	if(dependency('writable_settings_file') != false)
		return false;
		
	
	return true;
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false whether or not pear is installed
 */
function dependency_pear_installed($settings)
{
	return (include_path('PEAR.php') !== false);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if getID3() is installed
 */
function dependency_getid3_installed($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	return file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.lib.php');
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if snoopy library is installed in the include directory
 */
function dependency_curl_installed($settings)
{
	return function_exists('curl_init');
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if EXT JS library is installed in the plain templates directory for use by other templates
 */
function dependency_extjs_installed($settings)
{
	return file_exists(setting_local_root() . 'templates' . DIRECTORY_SEPARATOR . 'plain' . DIRECTORY_SEPARATOR . 'extjs' . DIRECTORY_SEPARATOR . 'ext-all.js');
}

/**
 * @}
 */

/**
 * @defgroup configure Configure Functions
 * All functions that allow configuring of settings for a module
 * @param settings The request array that contains values for configuration options
 * @return an associative array that describes the configuration options
 * @{
 */

/**
 * Implementation of configure
 */
function configure_index($settings, $request)
{
	$settings['html_root'] = setting_html_root($settings);
	$settings['html_domain'] = setting_html_domain($settings);
	$settings['html_name'] = setting_html_name($settings);
	$settings['debug_mode'] = setting_debug_mode($settings);
	$settings['recursive_get'] = setting_recursive_get($settings);
	$settings['no_bots'] = setting_no_bots($settings);
	$settings['buffer_size'] = setting_buffer_size($settings);
	
	$options = array();
	
	// domain and root
	$options['html_domain'] = array(
		'name' => lang('html domain title', 'HTML Domain'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('html domain description 1', 'This is the path that you would like to access the site.'),
				lang('html domain description 2', 'This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.'),
			),
		),
		'type' => 'text',
		'value' => $settings['html_domain'],
	);

	$options['html_root'] = array(
		'name' => lang('html root title', 'HTML Root'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('html root description 1', 'This is the directory that the site is accessed through.'),
				lang('html root description 2', 'This allows the site to run along site another website, in the specified directory.  This is needed so that templates can find the right path to images and styles.'),
				lang('html root description 3', 'This path must also end with the HTTP separator /.'),
				lang('html root description 4', 'The server reports the DOCUMENT ROOT is ' . $_SERVER['DOCUMENT_ROOT']),
			),
		),
		'type' => 'text',
		'value' => $settings['html_root'],
	);

	// site name
	$options['html_name'] = array(
		'name' => lang('html name title', 'Site Name'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('html name description', 'Some templates can display a name for this media server.  Set this here.'),
			),
		),
		'type' => 'text',
		'value' => $settings['html_name'],
	);
	
	$options['debug_mode'] = array(
		'name' => lang('debug mode title', 'Debug Mode'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('debug mode description 1', 'Debug mode is used by many templates to display debugging options on the page.'),
				lang('debug mode description 2', 'This is usefull for viewing information about file system and database problems and to test if the system is running properly.'),
			),
		),
		'type' => 'boolean',
		'value' => $settings['debug_mode'],
		'options' => array(
			lang('debug mode option 1', 'Turn Debug Mode On'),
			lang('debug mode option 2', 'Do Not Use Debug Mode'),
		)
	);
	
	$options['recursive_get'] = array(
		'name' => lang('recursive get title', 'Deep Select'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('recursive get description 1', 'This tells to system whether or not it should read directories on the fly and recursively.'),
				lang('recursive get description 2', 'If some files in a directory haven\'t been loaded, this will load them when the directory is accessed.'),
				lang('recursive get description 3', 'On large systems, this could cause page response to be VERY SLOW.  This option is not recommended for system where files change a lot.'),
			),
		),
		'type' => 'boolean',
		'value' => $settings['recursive_get'],
		'options' => array(
			lang('recursive get option 1', 'Turn Deep Select On'),
			lang('recursive get option 2', 'Do Not Use Deep Select'),
		)
	);
	
	$options['no_bots'] = array(
		'name' => lang('no bots title', 'Robots Handling'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('no bots description 1', 'Some services like Google like to scan websites.  This option will prevent robots from downloading and scanning files on your site.'),
				lang('no bots description 2', 'This will also enable robots to view a customizable sitemap.php module that provides them with the information they deserve.'),
			),
		),
		'type' => 'boolean',
		'value' => $settings['no_bots'],
		'options' => array(
			lang('no bots option 1', 'Disable Robots'),
			lang('no bots option 2', 'Allow Robots to Scan my Files'),
		)
	);
	
	$options['buffer_size'] = array(
		'name' => lang('buffer size title', 'Buffer Size'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('buffer size description', 'Some modules and modules require open file streams of a specific size.  This allows you to set what size these streams should try to remain below.'),
			),
		),
		'type' => 'filesize',
		'value' => $settings['buffer_size'],
	);
	
	return $options;
}

/**
 * @defgroup status Status Functions
 * All functions that return the output configuration for the dependencies listed in the module's config, for use on the configuration page and the status page
 * @param settings The request array that contains values for configuration options
 * @return an associative array that describes the configuration options
 * @{
 */

/**
 * Implementation of dependencies
 */
function status_core($settings)
{
	$status = array();

	if(dependency('memory_limit'))
	{
		$status['memory_limit'] = array(
			'name' => 'Memory Limit',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that the set memory limit is enough to function properly.',
					'This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.',
					'PHP reports that the set memory_limit is ' . ini_get('memory_limit') . '.',
				),
			),
			'disabled' => true,
			'value' => array(
				'link' => array(
					'url' => 'http://php.net/manual/en/ini.core.php',
					'text' => 'PHP Core INI Settings',
				),
			),
		);
	}
	else
	{
		$status['memory_limit'] = array(
			'name' => 'Memory Limit',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that the set memory limit is NOT ENOUGH for the system to function properly.',
					'This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.',
					'PHP reports that the set memory_limit is ' . ini_get('memory_limit') . '.',
				),
			),
			'disabled' => true,
			'value' => array(
				'link' => array(
					'url' => 'http://php.net/manual/en/ini.core.php',
					'text' => 'PHP Core INI Settings',
				),
			),
		);
	}
	
	if(dependency('writable_system_files'))
	{
		$status['writable_system_files'] = array(
			'name' => 'Writeable System Files',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that the the correct permissions are set on critical system files.',
				),
			),
			'disabled' => true,
			'value' => 'Permissions OK',
		);
	}
	else
	{
		$status['writable_system_files'] = array(
			'name' => 'Writeable System Files',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'There are permission problems with critical system files!',
				),
			),
			'disabled' => true,
			'value' => 'You must correct the following permission problems:',
		);
	}
	
	
	// move these to proper handlers
	$pear_libs = array('File/Archive.php' => 'File_Archive', 'MIME/Type.php' => 'MIME_Type');
	$not_installed = array();
	$installed = array();
	foreach($pear_libs as $lib => $link)
	{
		if(include_path($lib) === false)
		{
			$not_installed[] = array(
				'link' => array(
					'url' => 'http://pear.php.net/package/' . $link,
					'text' => $link,
				),
			);
		}
		else
		{
			$installed[] = array(
				'link' => array(
					'url' => 'http://pear.php.net/package/' . $link,
					'text' => $link,
				),
			);
		}
	}
	
	if(dependency('pear_installed'))
	{
		$status['pear_installed'] = array(
			'name' => 'PEAR Installed',
			'status' => (count($not_installed) > 0)?'warn':'',
			'description' => array(
				'list' => array(
					'The system has detected that PEAR is installed properly.',
					'The PEAR library is an extensive PHP library that provides common functions for modules and modules in the site.',
				),
			),
			'value' => array(
				'text' => array(
					'PEAR Detected',
				),
			),
		);
		
		if(count($not_installed) > 0)
		{
			$status['pear_installed']['value']['text'][] = 'However, the following packages must be installed:';
			$status['pear_installed']['value']['text'][] = array('list' => $not_installed);
		}
		else
		{
			$status['pear_installed']['value']['text'][] = 'The following required packages are also installed:';
			$status['pear_installed']['value']['text'][] = array('list' => $installed);
		}
	}
	else
	{
		$status['pear_installed'] = array(
			'name' => 'PEAR Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that PEAR is NOT INSTALLED.',
					'The PEAR library is an extensive PHP library that provides common functions for modules and modules in the site.',
				),
			),
			'value' => array(
				'text' => array(
					array(
						'link' => array(
							'url' => 'http://pear.php.net/',
							'text' => 'Get PEAR',
						),
					),
					'As well as the following libraries:',
					array('list' => $not_installed),
				),
			),
		);
	}
	
	
	if(dependency('getid3_installed'))
	{
		$status['getid3_installed'] = array(
			'name' => 'getID3() Library',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that getID3() library is installed in the includes directory.',
					'getID3() is a library for reading file headers for MP3s and many different file formats.',
				),
			),
			'type' => 'label',
			'value' => 'getID3() Library detected',
		);
	}
	else
	{
		$status['getid3_installed'] = array(
			'name' => 'getID3() Library Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that getID3() Library is NOT INSTALLED.',
					'The root of the getID3() library must be placed in &lt;site root&gt;/include/getid3/',
					'getID3() is a library for reading file headers for MP3s and many different file formats.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://www.smarty.net/',
					'text' => 'Get ID3()',
				),
			),
		);
	}
	
	if(dependency('curl_installed'))
	{
		$status['curl_installed'] = array(
			'name' => 'cUrl API',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that the cUrl API is installed in the includes directory.',
					'cUrl is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.',
				),
			),
			'type' => 'label',
			'value' => 'cUrl detected',
		);
	}
	else
	{
		$status['curl_installed'] = array(
			'name' => 'cUrl API Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that the cUrl API is NOT INSTALLED.',
					'cUrl is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://php.net/manual/en/book.curl.php',
					'text' => 'Get cUrl',
				),
			),
		);
	}

	if(dependency('extjs_installed'))
	{
		$status['extjs_installed'] = array(
			'name' => 'EXT JS',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that EXT JS is installed in the templates/plain/extjs directory.',
					'EXT JS is a javascript library for creating windows and toolbars in templates, this library can be used across all templates.',
				),
			),
			'type' => 'label',
			'value' => 'EXT JS Detected',
		);
	}
	else
	{
		$status['extjs_installed'] = array(
			'name' => 'EXT JS Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that EXT JS is NOT INSTALLED.',
					'The EXT JS root folder must be placed in &lt;site root&gt;/templates/plain/extjs/',
					'EXT JS is a javascript library for creating windows and toolbars in templates, this library can be used across all templates.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://www.extjs.com/',
					'text' => 'Get EXT JS',
				),
			),
		);
	}
	
	
	return $status;
}

/**
 * @}
 */

/**
 * Implementation of configure. Checks for convert path
 * @ingroup configure
 */
function configure_core($settings, $request)
{
	$settings['system_type'] = setting_system_type($settings);
	$settings['local_root'] = setting_local_root($settings);
	
	$options = array();
	
	// system type
	$options['system_type'] = array(
		'name' => lang('system type title', 'System Type'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('system type description 1', 'The system has detected that you are running ' . (($settings['system_type']=='win')?'Windows':(($settings['system_type']=='nix')?'Linux or Unix':'Mac OS')) . '.'),
				lang('system type description 2', 'If this is not correct, you must specify the system type so the media server can be optimized for your system.'),
			),
		),
		'type' => 'select',
		'value' => $settings['system_type'],
		'options' => array(
			'win' => 'Windows',
			'nix' => 'Linux',
			'mac' => 'Mac',
		),
	);
	
	if(setting_modrewrite($settings))
	{
		$options['modrewrite'] = array(
			'name' => 'Mod_Rewrite Enabled',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that you have mod_rewrite enabled.',
					'Mod_rewrite is used by some templates and modules to make the paths look prettier.',
				),
			),
			'disabled' => true,
			'value' => array(
				'link' => array(
					'url' => 'http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html',
					'text' => 'Mod_Rewrite Instructions',
				),
			),
		);
	}
	else
	{
		$options['modrewrite'] = array(
			'name' => 'Mod_Rewrite Enabled',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'The system has detected that you do not have mod_rewrite enabled.  Please follow the link for instructions on enabling mod_rewrite.',
					'Mod_rewrite is used by some templates and modules to make the paths look prettier.',
				),
			),
			'disabled' => true,
			'value' => array(
				'link' => array(
					'url' => 'http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html',
					'text' => 'Mod_Rewrite Instructions',
				),
			),
		);
	}
	
	return $options;
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
		// validate every single input
		if(function_exists('validate_' . $key))
			$_REQUEST[$key] = call_user_func_array('validate_' . $key, array($_REQUEST));
		elseif(isset($GLOBALS['validate_' . $key]) && is_callable($GLOBALS['validate_' . $key]))
			$_REQUEST[$key] = $GLOBALS['validate_' . $key]($_REQUEST);
		// if it is an attempted setting, keep it for now and let the configure modules module handle it
		elseif(substr($key, 0, 8) == 'setting_')
			$_REQUEST[$key] = $_REQUEST[$key];
		else
			$_REQUEST[$key] = NULL;
		
		// if there is no validator
		if(!isset($_REQUEST[$key]))
		{
			// unset it to prevent anything from using the input
			unset($_REQUEST[$key]);
			if(isset($_GET[$key])) unset($_GET[$key]);
		}
			
		// set the get variable also, so that when url($_GET) is used it is an accurate representation of the current page
		if(isset($_GET[$key])) $_GET[$key] = $_REQUEST[$key];
	}
	
	// call the session save functions
	setup_session($_REQUEST);
	
	// do not let GoogleBot perform searches or file downloads
	if(setting('no_bots'))
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
 * Helper function for validating all variables in a given input
 */
function core_validate_request($request)
{
	
	// go through the rest of the request and validate all the variables with the modules they are for
	foreach($request as $key => $value)
	{
		// validate every single input
		if(function_exists('validate_' . $key))
			$request[$key] = call_user_func_array('validate_' . $key, array($request));
		elseif(isset($GLOBALS['validate_' . $key]) && is_callable($GLOBALS['validate_' . $key]))
			$request[$key] = $GLOBALS['validate_' . $key]($request);
		// if it is an attempted setting, keep it for now and let the configure modules module handle it
		elseif(substr($key, 0, 8) == 'setting_')
			$request[$key] = $request[$key];
		else
			$request[$key] = NULL;
		
		// if there is no validator
		if(!isset($request[$key]))
		{
			// unset it to prevent anything from using the input
			unset($request[$key]);
		}
	}
	
	return $request;
}

 
/**
 * Set up the triggers for saving a session
 * @ingroup setup
 */
function setup_session($request = array())
{
	// check modules for vars and trigger a session save
	foreach($request as $key => $value)
	{
		if(isset($GLOBALS['triggers']['session'][$key]))
		{
			foreach($GLOBALS['triggers']['session'][$key] as $module => $function)
			{
				$save = call_user_func_array($function, array($request));
				// only save when something has changed
				if(isset($save))
					$_SESSION[$module] = $save;
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
 * Save and get information from the session
 * @return the session variable trying to be accessed
 */
function session($varname)
{
	$args = func_get_args();
	
	if(count($args = 2))
	{
		// they must be trying to set a value to the session
	}
	
	if(isset($_SESSION[$varname]))
		return $_SESSION[$varname];
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
	// check if the link is offsite, if so just make sure it is valid and return
	if(is_string($request) && strpos($request, '://') !== false)
	{
		if(parse_url($request) !== false)
			return $request;
		else
			PEAR::raiseError('The path \'' . $request . '\' is invalid!', E_DEBUG);
	}
	
	// if the link is a string, we need to convert it to an array for processing
	if(is_string($request))
	{
		// if the question mark is detected, there must be some amount of path info
		if(strpos($request, '?') !== false)
		{
			// split up the link into path info and query string
			$request = explode('?', $request);
			
			// save the path info for later usage
			$dirs = split('/', $request[0]);
			
			// set the request back to the query string for processing
			$request = $request[1];
		}
		
		// split up the query string by amersands
		$arr = explode('&', $request);
		if(count($arr) == 1 && $arr[0] == '')
			$arr = array();
		$request = array();
		
		// loop through all the query string and generate our new request array
		foreach($arr as $i => $value)
		{
			// split each part of the query string into name value pairs
			$x = explode('=', $value);
			
			// set each part of the query string in our new request array
			$request[$x[0]] = isset($x[1])?$x[1]:'';
		}
		
		// if the first item contains slashes it must be a part of the directory, fix this
		if(count($request) > 0)
		{
			// make sure the keys don't contain slashes, that would be weird
			$keys = array_keys($request);
			if(isset($keys[0]) && strpos($keys[0], '/') !== false)
			{
				// set the path
				$dirs = split('/', $keys[0]);
				
				// remove the weird key
				unset($request[$keys[0]]);
			}
		}
	}
	
	// add the path info to the request array
	if(isset($dirs))
	{
		// add the path info in this order so that the path can be used 
		//   and any modifications to the path variables can be specified in the query string
		$request = array_merge($request, parse_path_info($dirs));
	}
	
	// if the caller functionality would like an array returned for further processing such as in theme() return now
	if($return_array)
		return $request;
	
	// rebuild link
	// always add the module to the path
	if(!isset($request['module']))
		$request['module'] = validate_module(array('module' => isset($GLOBALS['module'])?$GLOBALS['module']:''));
		
	// if this option is available, add the path into back on to the front of the query string,
	//   maybe it will be only path information after this step
	//   the request is pass by reference so the request can be altered when variables are added to the path info
	$path_info = create_path_info($request);
	
	// generate a link, with optional domain the html root and path info prepended
	$link = (($include_domain)?setting('html_domain'):'') . 
		setting('html_root') . 
		$path_info;
		
	// add other variables to the query string
	if(count($request) > 0)
	{
		$link .= '?';
		
		// loop through each variable still existing in the reuqest and add it to the query info on the link
		foreach($request as $key => $value)
		{
			$link .= (($link[strlen($link)-1] != '?')?'&':'') . $key . '=' . $value;
		}
	}
	
	// optionally return a non html special chars converted URL
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
 * Implementation of always output
 * @ingroup output
 */
function core_variables($request)
{
	// set a couple more that are used a lot
	$request['module'] = validate_module($request);
	$request['cat'] = validate_cat($request);
	$request['group_by'] = validate_group_by($request);
	$request['group_index'] = validate_group_index($request);
	$request['start'] = validate_start($request);
	$request['limit'] = validate_limit($request);
	
	// the entire site depends on this
	register_output_vars('module', $request['module']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $request['cat']);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url($_GET, true));
	
	// some request variables used primarily by the database
	register_output_vars('group_by', $request['group_by']);
	register_output_vars('group_index', $request['group_index']);
	register_output_vars('start', $request['start']);
	register_output_vars('limit', $request['limit']);
}

/**
 * Function to call before the template is called, this can also be called from the first time #theme() is called
 * This sets all the register variables as HTML or original content, it also removes all unnecissary variables that might be used to penetrate the site
 */
function set_output_vars()
{
	// modules can specify variables to trigger their output function if they should always be outputted, even if that module isn't being called directly
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(isset($config['always output']) && $config['always output'] === true)
			call_user_func_array('output_' . $module, array($_REQUEST));
		elseif(isset($config['always output']) && is_callable($config['always output']))
			call_user_func_array($config['always output'], array($_REQUEST));
	}
	
	// triggers for always output can also be set
	foreach($_REQUEST as $key => $value)
	{
		if(isset($GLOBALS['triggers']['always output'][$key]))
		{
			foreach($GLOBALS['triggers']['always output'][$key] as $module => $function)
			{
				$_SESSION[$module] = call_user_func_array($function, array($_REQUEST));
			}
		}
	}

	// do not remove these variables
	$dont_remove = array(
		'GLOBALS',
		//'_REQUEST', // allow this because it has been fully validated
		'_SESSION', // purely for error handling
		'templates',
		'errors',
		'language_buffer',
		'debug_errors',
		'user_errors',
		'warn_errors',
		'note_errors',
		'output',
		'alias', // these are needed for validating paths in templates
		'alias_regexp',
		'paths',
		'paths_regexp', //
		'module',
		'modules',
		'_PEAR_default_error_mode',
		'_PEAR_default_error_options',
		'handlers',
		'tables',
		'ext_to_mime',
		'lists',
		'settings'
	);

	// unset all other globals to prevent templates from using them
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
 * Implementation of validate
 * @ingroup validate
 * @return false by default
 */
function validate_errors_only($request)
{
	if(isset($request['errors_only']))
	{
		if($request['errors_only'] === true || $request['errors_only'] === 'true')
			return true;
		elseif($request['errors_only'] === false || $request['errors_only'] === 'false')
			return false;
	}
	return false;
}

/**
 * Implementation of #setup_validate()
 * @return files handler by default, accepts any valid handler is database is used, or any valid handler that contains a get_handler_info() function if database is not used
 */
function validate_cat($request)
{
	// check if it exists
	if(isset($request['cat']) && in_array($request['cat'], array_keys($GLOBALS['handlers'])))
	{
		// if the database is not used, then to only categories that can be used for processing
		//   are the ones with a get_handler_info() function attached, this rules out, but is not limited to
		//   handlers that only operate on some other database such as wrappers
		if(setting('database_enable') == false && function_exists('get_' . $request['cat'] . '_info'))
			return $request['cat'];
		// the database is used, so return any valid handler
		elseif(setting('database_enable') != false)
			return $request['cat'];
		// the database is not used, and there is not get_handler_info function, return default
		else
			return 'files';
	}
	else
		return 'files';
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
	
	$columns = columns($handler);
	
	if( !isset($request['order_by']) || !in_array($request['order_by'], $columns) )
	{
		if(isset($request['search']))
			return 'Relevance';
			
		// make sure if it is a list that it is all valid columns
		$columns = split(',', (isset($request['order_by'])?$request['order_by']:''));
		foreach($columns as $i => $column)
		{
			if(!in_array($column, columns($handler)))
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
	
	$columns = columns($handler);
	
	if( isset($request['group_by']) && !in_array($request['group_by'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		$columns = split(',', $request['group_by']);
		foreach($columns as $i => $column)
		{
			if(!in_array($column, columns($handler)))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return;
		else
			return join(',', $columns);
	}
	elseif(isset($request['group_by']))
		return $request['group_by'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return false by default
 */
function validate_group_index($request)
{
	if(isset($request['group_index']))
	{
		if($request['group_index'] === true || $request['group_index'] === 'true')
			return true;
		elseif($request['group_index'] === false || $request['group_index'] === 'false')
			return false;
		elseif(is_string($request['group_index']))
			return strtolower($request['group_index'][0]);
	}
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
	
	$columns = columns($handler);
	
	// which columns to search
	if( isset($request['columns']) && !in_array($request['columns'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		if(!is_array($request['columns'])) $request['columns'] = split(',', $request['columns']);
		foreach($request['columns'] as $i => $column)
		{
			if(!in_array($column, columns($handler)))
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
	
	// do not use pretty paths before the site is configured
	if(!setting('modrewrite'))
		return '';
	
	// make sure the module doesn't actually exists on the web server
	if(file_exists(setting_local_root() . $path))
	{
		// a path without all the underscores replaced would be better then no path at all
		$path = $request['module'] . '/';

		if(file_exists(setting_local_root() . $path))
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
	// always add a module
	$request['module'] = validate_module($request);
	
	if(isset($request['path_info']))
	{
		// get path info
		$path = parse_path_info($request['path_info']);
		
		// merge path info with get as well as request
		$get = array_merge($path, $get);
		
		// merge path info, but request variables take precedence
		$request = array_merge($request, $path);
	}
		
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
	$request['order_by'] = validate_order_by($request);
	$request['direction'] = validate_direction($request);
	
	$props['LIMIT'] = $request['start'] . ',' . $request['limit'];
	
	if(isset($request['group_by'])) 
	{
		$props['GROUP'] = validate_group_by($request);
		
		if(isset($request['group_index']) && $request['group_index'] === true)
		{
			$props['GROUP'] = 'SUBSTRING(' . $props['GROUP'] . ', 1, 1)';
		}
		elseif(isset($request['group_index']) && is_string($request['group_index']))
		{
			$props['WHERE'][] = 'LEFT(' . $props['GROUP'] . ', 1) = "' . addslashes($request['group_index']) . '"';
			unset($props['GROUP']);
		}
	}

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
 * Implementation of status
 * @ingroup status
 */
function status_index()
{
}

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
	if(isset($request['errors_only']) && $request['errors_only'] == true)
	{
		register_output_vars('errors_only', true);
		
		// remove old errors from session
		$_SESSION['errors']['user'] = array();
		$_SESSION['errors']['warn'] = array();
		$_SESSION['errors']['note'] = array();

		theme('errors');
		
		exit;
	}
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

function theme_index()
{
	?>
	There are <?php print $GLOBALS['templates']['html']['total_count']; ?> result(s).<br />
	Displaying items <?php print $GLOBALS['templates']['html']['start']; ?> to <?php print $GLOBALS['templates']['html']['start'] + $GLOBALS['templates']['html']['limit']; ?>.
	<br />
	<?php
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><span style="color:#C00"><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			?><b><?php print $error->message; ?></b><br /><?php
		}
		?></span><?php
	}
	
	theme('pages');
	?>
	<br />
	<form name="select" action="{$get}" method="post">
		<input type="submit" name="select" value="All" />
		<input type="submit" name="select" value="None" />
		<p style="white-space:nowrap">
		Select<br />
		On : Off<br />
		<?php
		theme('files');
		?>
		<input type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php
		
	theme('pages');
	
	?>
	<br /><br />Select a Template:<br />
	<?php
	
	theme('template_block');
}

function theme_pages()
{
	$item_count = count($GLOBALS['templates']['vars']['files']);
	$page_int = $GLOBALS['templates']['vars']['start'] / $GLOBALS['templates']['vars']['limit'];
	$lower = $page_int - 8;
	$upper = $page_int + 8;
	$GLOBALS['templates']['vars']['total_count']--;
	$pages = floor($GLOBALS['templates']['vars']['total_count'] / $GLOBALS['templates']['vars']['limit']);
	$prev_page = $GLOBALS['templates']['vars']['start'] - $GLOBALS['templates']['vars']['limit'];
	if($pages > 0)
	{
		if($lower < 0)
		{
			$upper = $upper - $lower;
			$lower = 0;
		}
		if($upper > $pages)
		{
			$lower -= $upper - $pages;
			$upper = $pages;
		}
		
		if($lower < 0)
			$lower = 0;
		
		if($GLOBALS['templates']['vars']['start'] > 0)
		{
			if($GLOBALS['templates']['vars']['start'] > $GLOBALS['templates']['vars']['limit'])
			{
			?>
			<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>">First</a>
			<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $prev_page); ?>">Prev</a>
			<?php
			}
			else
			{
			?>
			<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>">First</a>
			<?php
			}
			?> | <?php
		}
		
		for($i = $lower; $i < $upper + 1; $i++)
		{
			if($i == $page_int)
			{
				?><b><?php print $page_int + 1; ?></b><?
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . ($i * $GLOBALS['templates']['vars']['limit'])); ?>"><?php print $i + 1; ?></a>
				<?php
			}
		}
		
		if($GLOBALS['templates']['vars']['start'] <= $GLOBALS['templates']['vars']['total_count'] - $GLOBALS['templates']['vars']['limit'])
		{
			?> | <?php
			$last_page = floor($GLOBALS['templates']['vars']['total_count'] / $GLOBALS['templates']['vars']['limit']) * $GLOBALS['templates']['vars']['limit'];
			$next_page = $GLOBALS['templates']['vars']['start'] + $GLOBALS['templates']['vars']['limit'];
			if($GLOBALS['templates']['vars']['start'] < $GLOBALS['templates']['vars']['total_count'] - 2 * $GLOBALS['templates']['vars']['limit'])
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $next_page); ?>">Next</a>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
		}
	}
}
