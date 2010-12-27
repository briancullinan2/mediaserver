<?php

/**
 * allow templates to save settings
 * get all template settings from database
 * parse out selected template setting
 * return settings in manner that the template understands
 * templates can submit settings using the config options for the template
 *   define the setting name and type in a key array
 *   types will be specified in the documentation (aligns with form types), text, int, radio, checkbox)
 */

function register_settings()
{
	return array(
		'name' => 'General Settings',
		'description' => 'Contains basic settings for site operation.',
		'privilage' => 10,
		'settings' => array('database_reset', 'all_reset', 'installed', 'system_type', 'local_root', 'settings_file', 'modrewrite', 
			'html_domain', 'html_root', 'html_name', 'deep_select', 'buffer_size',
		),
		'package' => 'core',
		'template' => false,
		'depends_on' => 'settings',
	);
}

function dependency_settings()
{
	$depends_on = array('memory_limit', 'writable_system_files', 'pear_installed', 
		'getid3_installed', 'curl_installed', 'extjs_installed');
	
	if(setting('database_enable'))
		$depends_on[] = 'database';
	
	return $depends_on;
}

/**
 * Helper function
 */
function preload_settings()
{
	// get system settings from a few different locations
	if(file_exists(setting_settings_file()))
	{
		if(($GLOBALS['settings'] = parse_ini_file(setting_settings_file(), true)))
		{
			// awesome settings are loaded properly
			foreach($GLOBALS['settings'] as $name => $value)
			{
				if(is_array($value))
				{
					foreach($value as $subsetting => $subvalue)
					{
						$GLOBALS['settings'][$name][$subsetting] = urldecode($subvalue);
					}
				}
				else
					$GLOBALS['settings'][$name] = urldecode($value);
			}
		}
	}
	
	// this stores a list of all the settings from the modules
	if(!isset($GLOBALS['all_settings']))
		$GLOBALS['all_settings'] = array();
		
	// the include must have failed
	if(!isset($GLOBALS['settings']) || empty($GLOBALS['settings']))
	{
		$GLOBALS['settings'] = array();
		
		// try and forward them to the install page
		if(!isset($_REQUEST['module'])) $_REQUEST['module'] = 'admin_install';

        raise_error('Site settings not configured, use the admin_password setting to see what\'s wrong!', E_DEBUG|E_USER);
	}
}

/**
 * set up all the default site settings
 * @ingroup setup
 */
function setup_settings()
{
	// load settings from database
	if(setting_installed() && setting('database_enable'))
	{
		$return = db_assoc('SELECT * FROM users WHERE id = -1 LIMIT 1');
			
		// make sure the query succeeded
		if(is_array($return) && count($return) > 0)
		{
			// merge the settings from the database
			$db_settings = unserialize($return[0]['Settings']);
			$GLOBALS['settings'] = array_merge($db_settings, $GLOBALS['settings']);
		}
		else
			raise_error('There was an error getting the administrative settings from the database!', E_DEBUG);
	}
	
	// merge everything with the default settings
	$GLOBALS['settings'] = array_merge(get_default_settings($GLOBALS['settings']), $GLOBALS['settings']);
}

function bootstrap_settings($module)
{
	// loop through the modules and call settings functions on them if they are set to callbacks
	$settings = get_settings($module);
	
	$GLOBALS['modules'][$module]['settings'] = $settings;
	
	// merge new settings with total list of settings
	if(is_array($settings))
	{
		$setting_module_pairs = array_fill_keys($settings, $module);
		$GLOBALS['all_settings'] = array_merge($setting_module_pairs, $GLOBALS['all_settings']);
	}
	else
		raise_error('Settings is not an array for module \'' . $module . '\'.', E_DEBUG);
}

function get_settings($module)
{
	// if it is a module but has no settings, return empty array
	$settings = get_module($module, 'settings');

	if(!isset($settings))
		return array();

	if(is_string($settings) && is_callable('setting_' . $settings))
	{
		return call_user_func_array('setting_' . $settings, array($GLOBALS['settings']));
	}
	else
		return $settings;
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 * @return possible settings file paths in order of preference to the system, false if file doesn't exist anywhere
 */
function setting_settings_file()
{
	// return file from where it is supposed to be
	$port = isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:'80';
	$domain = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'localhost';
	$dirs = array(
		$port . '.' . $domain,
		$domain,
		'default',
	);

	// check other directories if it doesn't exist there
	foreach($dirs as $i => $dir)
	{
		$settings = setting_local_root() . 'sites' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'settings.php';
		if(file_exists($settings))
			return $settings;
	}

	return $settings;
}

function setting_local_root()
{
	return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
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

function setting_enable_https($settings)
{
	if(!isset($settings['enable_https']))
		return false;
	else
		return ($settings['enable_https'] && $settings['enable_https'] !== 'false');
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The domain information set by the SERVER by default
 */
function setting_html_domain($settings)
{
	if(!isset($_SERVER['SERVER_PROTOCOL']))
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
	if(!isset($_SERVER['HTTP_HOST']))
		$_SERVER['HTTP_HOST'] = 'localhost';
	if(!isset($_SERVER['SERVER_PORT']))
		$_SERVER['SERVER_PORT'] = 80;
	if(isset($settings['html_domain']) && @parse_url($settings['html_domain']) !== false)
		return $settings['html_domain'];
	else
		return strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['HTTP_HOST'] . (($_SERVER['SERVER_PORT'] != 80)?':' . $_SERVER['SERVER_PORT']:'');
}

function setting_request_method()
{
	if(isset($_SERVER['REQUEST_METHOD']))
		return strtolower($_SERVER['REQUEST_METHOD']);
	else
		return 'get';
}

function setting_remote_ip()
{
    if(isset($_SERVER['REMOTE_ADDR']))
        return $_SERVER['REMOTE_ADDR'];
    else
        return NULL;
}

function setting_agent()
{
	if(isset($_SERVER['HTTP_USER_AGENT']))
		return $_SERVER['HTTP_USER_AGENT'];
	else
		return 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
}

function setting_agent_simple()
{
	if(preg_match('/.*(mozilla).*/i', setting('agent'), $matches) !== 0)
		return 'mozilla';
	elseif(preg_match('/.*(windows-media-player|NSplayer|WMPCE).*/i', setting('agent'), $matches) !== 0)
		return 'windows';
	elseif(preg_match('/.*(iTunes).*/i', setting('agent'), $matches) !== 0)
		return 'itunes';
	elseif(preg_match('/.*(mobile).*/i', setting('agent'), $matches) !== 0)
		return 'mobile';
	elseif(preg_match('/.*(vlc).*/i', setting('agent'), $matches) !== 0)
		return 'vlc';
	elseif(preg_match('/.*Googlebot.*/i', setting('agent'), $matches) !== 0)
		return 'googlebot';
	else
		return 'mozilla';
}

function setting_deflate()
{
	if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false &&
                function_exists('ob_gzhandler')
	)
		return true;
	else
		return false;
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
	$settings['local_root'] = str_replace(DIRECTORY_SEPARATOR, '/', $settings['local_root']);

	if(isset($settings['html_root']) && @parse_url($settings['html_domain'] . $settings['html_root']) !== false)
		return $settings['html_root'];
	else
		return dirname($_SERVER['SCRIPT_NAME']) . '/' .
			(setting('modrewrite')?'':'index.php/');
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
function setting_deep_select($settings)
{
	if(isset($settings['deep_select']) &&
        ($settings['deep_select'] === 0 || $settings['deep_select'] == 1 || $settings['deep_select'] == 2))
		return intval($settings['deep_select']);
	else
		return false;
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
		return 8*8*1024;
}

function dependency_modrewrite()
{
	return setting('modrewrite');
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_modrewrite($settings)
{
	return (isset($_REQUEST['modrewrite']) && $_REQUEST['modrewrite']);
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return the temp directory reported by the OS by default
 */
function setting_tmp_dir($settings)
{
	if(($settings['tmp_dir'] = generic_validate_dir($settings, 'tmp_dir')))
		return $settings['tmp_dir'];
	else
	{
		$tmpfile = tempnam("dummy","");
		unlink($tmpfile);
		return dirname($tmpfile) . DIRECTORY_SEPARATOR;
	}
}

/**
 * @defgroup setting Settings Functions
 * All functions that handling site settings, basically designed exactly the same way as validate()
 * @param settings the current list of settings for settings to depend on each other
 * @return default values of settings or a validated setting input
 * @{
 */

/**
 * Get a setting
 * @ingroup setting
 * @param name The setting name to get
 */
function setting($name)
{
	$args = func_get_args();
	array_shift($args);
	
	// if by chance settings isn't set up
	if(!isset($GLOBALS['settings']))
		preload_settings();
		
	array_unshift($args, $GLOBALS['settings']);
	
	// if the setting is not found, try to get the default
	if(function_exists('setting_' . $name))
		$GLOBALS['settings'][$name] = call_user_func_array('setting_' . $name, $args);
	elseif(isset($GLOBALS['setting_' . $name]) && is_callable($GLOBALS['setting_' . $name]))
		$GLOBALS['settings'][$name] = call_user_func_array($GLOBALS['setting_' . $name], $args);
	
	// if the setting is loaded already use that
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting isn't found in the configuration
	raise_error('Setting \'' . $name . '\' not found!', E_VERBOSE);
}

/**
 * Get all the default settings
 * @ingroup setting
 */
function get_default_settings($global_settings)
{
	// existing settings are passed in to this function incase a default depends on something already set up
	
	// new settings to return
	$default_settings = array();
	
	// loop through each module and get the default settings
	foreach(get_modules() as $i => $module)
	{
		$settings = get_settings($module);
		foreach($settings as $i => $setting)
		{
			if(function_exists('setting_' . $setting))
				$new_setting = call_user_func_array('setting_' . $setting, array($global_settings));
			elseif(isset($GLOBALS['setting_' . $setting]) && is_callable($GLOBALS['setting_' . $setting]))
				$new_setting = $GLOBALS['setting_' . $setting]($global_settings);
			else
				raise_error('Setting \'' . $setting . '\' is specified without a validate function in the ' . $module . ' module.', E_DEBUG);
		
			if(isset($new_setting))
				$default_settings[$setting] = $new_setting;
			else
				unset($default_settings[$setting]);
		}
	}
	
	return $default_settings;
}

/**
 * @}
 */
 
/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_setting_database_reset($request)
{
	if(isset($request['setting_database_reset']))
	{
		$result = _modules_save_db_settings(array());
		
		if($result)
			raise_error('The database settings have been reset!', E_WARN);
		else
			raise_error('There was a problem while saving the settings to the database!', E_USER);
	}
}
 
/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_setting_all_reset($request)
{
	if(isset($request['setting_all_reset']))
	{
		$result = _modules_save_db_settings(array());
		
		if($result)
			raise_error('The database settings have been reset!', E_WARN);
		else
			raise_error('There was a problem while saving the settings to the database!', E_USER);
			
		$result = _modules_save_fs_settings(array());
		
		if($result === -1)
			raise_error('Cannot save settings, the settings file is not writable!', E_USER);
		if($result === true)
			raise_error('The file settings have been reset!', E_WARN);
		else
			raise_error('There was a problem with saving the settings in the settings file.', E_USER);
	}
}

function get_system_files($settings)
{
	$paths = explode(PATH_SEPARATOR, get_include_path());
	$paths[] = setting('settings_file');
	$paths[] = dirname(__FILE__);
	
	return $paths;
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
	if(array_sum(array_map('is_writable', get_system_files($settings))) > 0)
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
	return include_path('PEAR.php') !== false;
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if getID3() is installed
 */
function dependency_getid3_installed($settings)
{
	return include_path('getid3' . DIRECTORY_SEPARATOR . 'getid3.lib.php') !== false;
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
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if simplexml is installed in the include directory
 */
function dependency_simplexml_installed($settings)
{
	return class_exists('SimpleXMLElement');
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
function status_settings($settings)
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

	$status['deflate_support'] = array(
		'name' => 'Deflating Support',
		'status' => function_exists('ob_gzhandler')?'':'warn',
		'description' => array(
			'list' => array(
				'Deflating the page helps reduce network traffic and speeds up output for users.',
				'This requires Zlib be installed.'
			),
		),
		'value' => array(
			'link' => array(
				'url' => 'http://www.php.net/manual/en/book.zlib.php',
				'text' => 'Zlib Extension',
			),
		),
	);
	
	$corrections = array();
	$files = get_system_files($settings);
	foreach($files as $i => $file)
	{
		if(is_writable($file))
			$corrections[] = '\'' . $file . '\' is writable, remove write permissions.';
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
			'value' => array(
				'You must correct the following permission problems:',
				'list' => $corrections,
			),
		);
	}
	
	$status['library_paths'] = array(
		'name' => 'Library Paths',
		'status' => '',
		'description' => array(
			'list' => array(
				'These paths are the locations where libraries can be installed to.',
			),
		),
		'value' => array(
			'Libraries can be installed in:',
			'list' => get_include_paths(),
		),
	);
	
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
 * Implementation of configure
 * @ingroup configure
 */
function configure_settings($settings, $request)
{
	$options = array();
	
	$reset = array();
	
	$reset['setting_database_reset'] = array(
		'name' => 'Database Settings',
		'status' => 'warn',
		'description' => array(
			'list' => array(
				'Reset all the settings in the database, this action cannot be undone.',
			),
		),
		'type' => 'submit',
		'value' => 'Reset',
	);
	
	$reset['setting_all_reset'] = array(
		'name' => 'All Settings',
		'status' => 'fail',
		'description' => array(
			'list' => array(
				'Reset all the settings including the administrative settings (requires write access).',
			),
		),
		'type' => 'submit',
		'value' => 'Reset',
	);
	
	$options['reset'] =  array(
		'name' => 'Reset Settings',
		'type' => 'fieldset',
		'collapsible' => true,
		'collapsed' => true,
		'options' => $reset
	);
	
	$general = array();
	
	// system type
	$settings['system_type'] = setting('system_type');
	$general['setting_system_type'] = array(
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
	
	
	// https
	$general['setting_enable_https'] = array(
		'name' => lang('enable https title', 'Use HTTPS'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('enable https description 1', 'Automatically switch to HTTPS when needed, such as for logins and filetransfer.'),
			),
		),
		'type' => 'checkbox',
		'options' => array(
			true => 'Switch to HTTPS'
		),
		'value' => setting('enable_https'),
	);
	
	// domain and root
	$general['setting_html_domain'] = array(
		'name' => lang('html domain title', 'HTML Domain'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('html domain description 1', 'This is the path that you would like to access the site.'),
				lang('html domain description 2', 'This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.'),
			),
		),
		'type' => 'text',
		'value' => setting('html_domain'),
	);

	$general['setting_html_root'] = array(
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
		'value' => setting('html_root'),
	);

	// site name
	$general['setting_html_name'] = array(
		'name' => lang('html name title', 'Site Name'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('html name description', 'Some templates can display a name for this media server.  Set this here.'),
			),
		),
		'type' => 'text',
		'value' => setting('html_name'),
	);
	
	$general['setting_deep_select'] = array(
		'name' => lang('recursive get title', 'Deep Select'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('recursive get description 1', 'This tells to system whether or not it should read directories on the fly and recursively.'),
				lang('recursive get description 2', 'If some files in a directory haven\'t been loaded, this will load them when the directory is accessed.'),
				lang('recursive get description 3', 'On large systems, this could cause page response to be VERY SLOW.  This option is not recommended for system where many files change often.'),
				lang('recursive get description 4', '"Very Deep Select" will load remote content in modules that cache information from the internet.  "Deep Select" only loads the information that can be processed locally.'),
			),
		),
		'type' => 'select',
		'value' => setting('deep_select'),
		'options' => array(
			2 => lang('recursive get option 1', 'Very Deep Select On'),
			true => lang('recursive get option 2', 'Turn Deep Select On'),
			false => lang('recursive get option 3', 'Do Not Use Deep Select'),
		)
	);
	
	$general['setting_buffer_size'] = array(
		'name' => lang('buffer size title', 'Buffer Size'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('buffer size description', 'Some modules and modules require open file streams of a specific size.  This allows you to set what size these streams should try to remain below.'),
			),
		),
		'type' => 'filesize',
		'value' => setting('buffer_size'),
	);
	
	$options['general'] = array(
		'name' => 'General Settings',
		'type' => 'fieldset',
		'collapsible' => true,
		'options' => $general
	);
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['users']['Settings']))
}

