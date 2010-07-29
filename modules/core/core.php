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
 * @}
 */

function menu_core()
{
	return array(
		'core/%cat/%id/%core/%extra/%filename' => array(
			'callback' => 'core',
		),
		'core/%cat/%id/%core/%filename' => array(
			'callback' => 'core',
		),
		'core/%cat/%id/%filename' => array(
			'callback' => 'core',
		),
		'core/%cat/%id' => array(
			'callback' => 'core',
		),
		'core/%search' => array(
			'callback' => 'core',
		),
	);
}

/**
 * Create a global variable for storing all the module information
 * @ingroup setup
 */
function setup_core()
{
	$verbose = setting('verbose');

	// do some extra error stuff since we made it to this point
	if($verbose === 2)
	{
		set_error_handler('php_to_PEAR_Error', E_ALL | E_STRICT);
		error_reporting(E_ALL);
	}
	elseif($verbose === true)
	{
		set_error_handler('php_to_PEAR_Error', E_ALL);
		error_reporting(E_ERROR);
	}
	else
		error_reporting(E_ERROR);
	
	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
	
	// setup the session first thing
	setup_session();
	
	// set up the mime information
	setup_mime();

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

			// if it is not an array debug message
			if(!isset($GLOBALS['modules'][$module]['depends on']))
				$depends_on = array();
			else
				$depends_on = $GLOBALS['modules'][$module]['depends on'];
			
			// if the dependency field is set to the module name, and a valid callback exists, call that to determine list of dependencies
			if(is_string($depends_on) && $depends_on == $module &&
				function_exists('dependency_' . $module)
			)
			{
				$depends_on = invoke_module('dependency', $module, array($GLOBALS['settings']));
			}

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
function load_modules($path)
{
	if(is_dir(setting_local_root() . $path))
	{
		$files = get_files(array(
			'dir' => setting_local_root() . $path,
			'depth' => 3,
			'limit' => 32000,
			'match' => '/\.info$/i',
		), $count, true);
	}
	elseif(is_file(setting_local_root() . $path))
		$files = array(get_files_info(setting_local_root() . $path));

	$modules = array();
	
	// loop through files and add modules to global list
	if(is_array($files))
	{
		foreach($files as $i => $file)
		{
			if(is_file($file['Filepath']))
			{
				// determin module based on path
				$module = basename($file['Filepath']);
				
				// functional prefix so there can be multiple modules with the same name
				$prefix = substr($file['Filepath'], strlen(setting_local_root()), -strlen($module));
				
				// remove slashes and replace with underscores
				$prefix = str_replace(array('/', '\\'), '_', $prefix);
				
				// remove extension from module name
				$module = substr($module, 0, strrpos($module, '.'));
				
				// call register function
				if(substr($file['Filename'], -5) == '.info')
				{
					$modules[$module] = parse_ini_file($file['Filepath'], true);
					
					include_once dirname($file['Filepath']) . DIRECTORY_SEPARATOR . $module . '.php';
				}
				elseif(substr($file['Filename'], -4) == '.php')
				{
					// be a little more cautious about the buffer
					ob_start();
					
					include_once $file['Filepath'];
					
					if(function_exists('register_' . $module))
						$modules[$module] = invoke_module('register', $module);
						
					ob_end_clean();
				}
				
				// set the package if it is not set already
				if(!isset($modules[$module]['package']))
				{
					if($prefix != '')
						$modules[$module]['package'] = substr($prefix, 0, -1);
					else
						$modules[$module]['package'] = 'other';
				}
				
				// set the path to the module
				if(!isset($modules[$module]['path']))
					$modules[$module]['path'] = dirname($file['Filepath']) . DIRECTORY_SEPARATOR . $module . '.php';
				
				register_trigger('session', $modules[$module], $module);
				
				register_trigger('alter_query', $modules[$module], $module);
				
				register_trigger('output', $modules[$module], $module);
				
				register_trigger('validate', $modules[$module], $module);
				
				// save in globals
				$GLOBALS['modules'][$module] = &$modules[$module];
			}
		}
	}

	$GLOBALS['modules'] = array_merge(array_flip(flatten_module_dependencies(array_keys($GLOBALS['modules']))), $GLOBALS['modules']);
	
	return $modules;
}


/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default, set to true to record all notices
 */
function setting_verbose($settings)
{
	$verbose = generic_validate_boolean_false($settings, 'verbose');

	if(isset($settings['verbose']) && ($settings['verbose'] === "2" || $settings['verbose'] === 2))
		return 2;
	else
		return $verbose;
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
	$settings = setting('settings_file');

	// make sure the database isn't being used and failed
	return (file_exists($settings) && is_readable($settings) && (!isset($GLOBALS['database']) || dependency('database') != false));
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return Always returns the parent directory of the current modules or admin directory
 */
function setting_local_root()
{
	return dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR;
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
	$settings['html_domain'] = setting('html_domain');
	$settings['local_root'] = setting('local_root');
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
	return generic_validate_boolean_false($settings, 'debug_mode');
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default
 */
function setting_recursive_get($settings)
{
	return generic_validate_boolean_false($settings, 'recursive_get');
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return true by default
 */
function setting_no_bots($settings)
{
	return generic_validate_boolean_true($settings, 'no_bots');
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
	return true;
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
	
	if(isset($config))
	{	
		// if the depends on field is a string, call the function and use that as the list of dependencies
		if(isset($config['depends on']) && is_string($config['depends on']))
		{	
			if($config['depends on'] == $dependency && function_exists('dependency_' . $config['depends on']))
			{
				$config['depends on'] = invoke_module('dependency', $dependency, array($GLOBALS['settings']));
			}
			else
			{
				raise_error('Function dependency_\'' . $dependency . '\' is specified but the function does not exist.', E_DEBUG);
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
					raise_error('Dependency \'' . $depend . '\' already checked when checking the dependencies of \'' . $dependency . '\'.', E_VERBOSE);
					
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
	elseif(isset($GLOBALS['dependency_' . $dependency]) && is_callable($GLOBALS['dependency_' . $dependency]))
		return call_user_func_array($GLOBALS['dependency_' . $dependency], array($GLOBALS['settings']));
	else
		raise_error('Dependency \'' . $dependency . '\' not defined!', E_DEBUG);
		
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
	$settings['local_root'] = setting('local_root');
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
	$settings['system_type'] = setting('system_type');
	$settings['local_root'] = setting('local_root');
	$settings['html_root'] = setting('html_root');
	$settings['html_domain'] = setting('html_domain');
	$settings['html_name'] = setting('html_name');
	$settings['debug_mode'] = setting('debug_mode');
	$settings['recursive_get'] = setting('recursive_get');
	$settings['no_bots'] = setting('no_bots');
	$settings['buffer_size'] = setting('buffer_size');
	
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
 * @}
 */

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
		raise_error('Variable "' . $name . '" already set!', E_DEBUG);
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
			raise_error('The path \'' . $request . '\' is invalid!', E_DEBUG);
	}
	
	// if the link is a string, we need to convert it to an array for processing
	if(is_string($request))
	{
		// get query part if there it one for processing
		$query = generic_validate_query_str(array('query' => $request), 'query');
		
		// get the path part of the request
		$path = generic_validate_urlpath(array('path' => $request), 'path');		
	
		// get the menu path
		$menu = get_menu_entry($request);

		// if path is not set, show an error so it can be fixed
		if(!isset($menu))
		{
			$menu = 'core';
			raise_error('Malformed URL!', E_DEBUG);
		}
		
		// invoke rewriting
		if(function_exists('rewrite_' . $GLOBALS['menus'][$menu]['module']))
			$request = invoke_module('rewrite', $GLOBALS['menus'][$menu]['module'], array($path));
		else
			$request = invoke_module('rewrite', 'core', array($path));
		
		// process the query part
		//   this is done here because fragment takes precedence over path
		//   this allows for calling an output function modified request input
		$arr = explode('&', $query);
		if(count($arr) == 1 && $arr[0] == '')
			$arr = array();
		
		// loop through all the query string and generate our new request array
		foreach($arr as $i => $value)
		{
			// split each part of the query string into name value pairs
			$x = explode('=', $value);
			
			// set each part of the query string in our new request array
			$request[$x[0]] = urldecode(isset($x[1])?$x[1]:'');
		}
	}
	else
	{
		// remove urlencoding from array
		foreach($request as $key => $value)
		{
			$request[$key] = urldecode((string)$value);
		}
	}
	
	// if the caller functionality would like an array returned for further processing such as in theme() return now
	if($return_array)
		return $request;
	
	// check with mod rewrite if paths can actually be printed out as directories
	if(setting('modrewrite') == true && isset($path))
		$path_info = get_path($request, $menu);
	else
		$path_info = '';

	// generate query string
	$query = '?';
	foreach($request as $key => $value)
	{
		$query .= (($query != '?')?'&':'') . $key . '=' . urlencode($value);
	}
	
	// generate a link, with optional domain the html root and path info prepended
	$link = (($include_domain)?setting('html_domain'):'') . 
		setting('html_root') . 
		$path_info . (($query != '?')?$query:'');
	
	// optionally return a non html special chars converted URL
	if($not_special)
		return $link;
	else
		return htmlspecialchars($link, ENT_QUOTES);
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
		
	}
	else
	{
		register_output_vars('redirect', url($request, true));
	
		theme('redirect');	
	}
	
	// exit now so the page is redirected
	exit;
}

/**
 * Implementation of always output
 * @ingroup output
 */
function core_variables($request)
{
	// set a couple more that are used a lot
	$request['cat'] = validate($request, 'cat');
	$request['group_by'] = validate($request, 'group_by');
	$request['group_index'] = validate($request, 'group_index');
	$request['start'] = validate($request, 'start');
	$request['limit'] = validate($request, 'limit');
	
	// always make the module list available to templates
	register_output_vars('modules', $GLOBALS['modules']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $request['cat']);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url($_GET, true));
	
	// some request variables used primarily by the database
	register_output_vars('group_by', $request['group_by']);
	register_output_vars('group_index', $request['group_index']);
	register_output_vars('start', $request['start']);
	register_output_vars('limit', $request['limit']);
	
	// always make the column list available to templates
	register_output_vars('columns', getAllColumns());
}

/**
 * Function to call before the template is called, this can also be called from the first time #theme() is called
 * This sets all the register variables as HTML or original content, it also removes all unnecissary variables that might be used to penetrate the site
 */
function set_output_vars()
{
	// triggers for always output can also be set
	trigger('output', NULL, $_REQUEST);	

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
		'settings',
		'triggers',
		'menus',
		'validated',
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
		return htmlspecialchars($input, ENT_QUOTES);
	elseif(is_array($input))
	{
		foreach($input as $key => $value)
		{
			$input[$key] = traverse_array($value);
		}
		return $input;
	}
	else
		return htmlspecialchars((string)$input, ENT_QUOTES);
}

/**
 * @defgroup validate Validate Functions
 * All functions that are for validating input variables
 * @param request The full request array incase validation depends on other variables from the request
 * @return NULL if the input is invalid and there is no default, the default value if the input is invalid, the input if it is valid
 * @{
 */

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
 * Implementation of #setup_validate()
 * @return The index module by default, also checks for compatibility based on other request information
 */
/*
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
			return 'core';
	}
}
*/

/**
 * Generic validator
 */
function generic_validate_boolean($request, $index)
{
	return validate($request, $index, 'boolean_true');
}

/**
 * Generic validator
 */
function generic_validate_boolean_true($request, $index)
{
	if(isset($request[$index]))
	{
		if($request[$index] == true || $request[$index] === 'true')
			return true;
		elseif($request[$index] == false || $request[$index] === 'false')
			return false;
	}
	return true;
}

/**
 * Generic validator
 */
function generic_validate_boolean_false($request, $index)
{
	if(isset($request[$index]))
	{
		if($request[$index] == true || $request[$index] === 'true')
			return true;
		elseif($request[$index] == false || $request[$index] === 'false')
			return false;
	}
	return false;
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
	if(isset($request[$index]) && $request[$index] != '' && @preg_match($request[$index], 'test') !== false)
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
	if(isset($request[$index]))
		$request[$index] = preg_replace('/[^a-z0-9]/i', '_', $request[$index]);
		
	// ensure it is a valid name
	if(preg_match('/^[a-z][a-z0-9_]*$/i', $request[$index]) != 0)
		return strtolower($request[$index]);
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

/**
 * Implementation of validate
 * @return false by default
 */
function validate_errors_only($request)
{
	return generic_validate_boolean_false($request, 'errors_only');
}

/**
 * Implementation of #setup_validate()
 * @return files handler by default, accepts any valid handler is database is used, or any valid handler that contains a get_handler_info() function if database is not used
 */
function validate_cat($request)
{
	// check if it exists
	if(isset($request['cat']) && is_handler($request['cat']) && !is_internal($request['cat']))
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
	return generic_validate_numeric_zero($request, 'start');
}

/**
 * Implementation of #setup_validate()
 * @return 15 by default, accepts any positive number
 */
function validate_limit($request)
{
	return generic_validate_numeric_default($request, 'limit', 15);
}

/**
 * Implementation of #setup_validate()
 * @return Filepath by default, Relevance if performing a search which is a keyword for the #alter_query_search() call, any set of columns defined in the specified handler (cat) is valid
 */
function validate_order_by($request)
{
	$handler = validate($request, 'cat');
	
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
	$handler = validate($request, 'cat');
	
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
	$handler = validate($request, 'cat');
	
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
	return generic_validate_all_safe($request, 'extra');
}

/**
 * @}
 */

/**
 * Parse a request from the path
 * @param path_info The part of a request that relects pretty dirs and contains slashes
 * @return all the request information retrieved from the path in an associative array
 */
function rewrite_core($path_info)
{
	$request = array();

	$menu = get_menu_entry($path_info);

	$dirs = split('/', $path_info);

	// assign to variables based on menu entry
	$vars = split('/', $menu);
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
	$request['cat'] = validate($request, 'cat');

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
function alter_query_core($request, &$props)
{
	$request['limit'] = validate($request, 'limit');
	$request['start'] = validate($request, 'start');
	$request['order_by'] = validate($request, 'order_by');
	$request['direction'] = validate($request, 'direction');
	
	$props['LIMIT'] = $request['start'] . ',' . $request['limit'];
	
	if(isset($request['group_by'])) 
	{
		$props['GROUP'] = validate($request, 'group_by');
		
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
 * Outputs select by default
 */
function output_core($request)
{
	// output any index like information
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
	
	// perform a select so files can show up on the index page
	output_select($request);
	
	theme('index');
}

/**
 * @}
 */

function theme_index()
{
	theme('select');
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


function theme_redirect()
{
	theme('header');
	
	?>You are being redirected...<?php
	
	theme('footer');
}

function theme_redirect_block()
{
	if(isset($GLOBALS['templates']['html']['redirect']))
	{
		?><META HTTP-EQUIV="refresh" CONTENT="1;URL=<?php print $GLOBALS['templates']['html']['redirect']; ?>"><?php
	}
}

function theme_header()
{
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print setting('html_name'); ?> : <?php print htmlspecialchars($GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name']); ?></title>
</head>

<body>
<h1><?php print htmlspecialchars($GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name']); ?></h1>
	<?php
	
	theme('errors');
}

function theme_footer()
{
	if($GLOBALS['templates']['vars']['module'] != 'users')
		theme('login_block');
	
	?>
	Modules:
<ul>
<?php
foreach($GLOBALS['modules'] as $name => $module)
{
	if($module['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
		continue;
										
	if(!function_exists('output_' . $name))
		$link = 'admin/modules/' . $name;
	else
		$link = $name;
									
	?><li><a href="<?php print url($link); ?>"><?php echo $module['name']; ?></a></li><?php
}
?>
</ul>
</body>
</html>
	<?php
}

function theme_errors()
{
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><span style="color:#C00"><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	if(count($GLOBALS['warn_errors']) > 0)
	{
		?><span style="color:#CC0"><?php
		foreach($GLOBALS['warn_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	if(count($GLOBALS['note_errors']) > 0)
	{
		?><span style="color:#00C"><?php
		foreach($GLOBALS['note_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	$GLOBALS['note_errors'] = array();
	$GLOBALS['warn_errors'] = array();
	$GLOBALS['user_errors'] = array();
	$GLOBALS['debug_errors'] = array();
}

function theme_errors_block()
{
	theme('errors');
}


function theme_default()
{
	theme('header');
	
	?><h1>Module: <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name']; ?></h1>
	This page requires special parameters that have not been set.  This default page is a placeholder.<?php
	
	theme('errors_block');

	theme('footer');
}