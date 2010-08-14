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

/**
 * Helper function
 */
function preload_settings()
{
	// get system settings from a few different locations
	if(file_exists(setting_settings_file()))
	{
		if($GLOBALS['settings'] = parse_ini_file(setting_settings_file(), true))
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
	
	// the include must have failed
	if(!isset($GLOBALS['settings']) || empty($GLOBALS['settings']))
	{
		$GLOBALS['settings'] = array();
		
		// try and forward them to the install page
		if(!isset($_REQUEST['module'])) $_REQUEST['module'] = 'admin_install';
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

	// loop through the modules and call settings functions on them if they are set to callbacks
	foreach(get_modules() as $module => $config)
	{
		if(isset($config['settings']) && is_string($config['settings']) && 
			$config['settings'] == $module && function_exists('setting_' . $module)
		)
			$GLOBALS['modules'][$module]['settings'] = invoke_module('setting', $module, array($GLOBALS['settings']));
	}
	
	// merge everything with the default settings
	$GLOBALS['settings'] = array_merge(settings_get_defaults($GLOBALS['settings']), $GLOBALS['settings']);
}

/**
 * Implementation of settings
 * DO NOT TOUCH!  This is how the dependency_settings() function determines when this module has been setup in setup_settings()
 * @ingroup settings
 */
function setting_settings()
{
	return array('database_reset', 'all_reset');
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
		'$port.$domain',
		'$domain',
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
	return generic_validate_boolean_false($settings, 'debug_mode') || !setting_installed();
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
 * Implementation of dependency
 * Get all the dependencies based on what settings must be set up first
 * @ingroup dependency
 */
function dependency_settings($settings)
{
	// this is a hack to return empty array after the setup has been done
	if(isset($GLOBALS['output']['modules']))
		return array();

	$depends = array();
	
	// loop through all the modules and look for config settings set to the module name
	// those modules must be set up before settings tries to load their default values
	foreach(get_modules() as $module => $config)
	{
		if(isset($config['settings']) && is_string($config['settings']) && 
			$config['settings'] == $module && function_exists('setting_' . $module)
		)
			$depends[] = $module;
	}
	
	// always load database first if it is being used
	if(dependency('database') !== false)
		$depends[] = 'database';

	return $depends;
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
function setting($name, $default = NULL)
{
	// if by chance settings isn't set up
	if(!isset($GLOBALS['settings']))
		preload_settings();
	
	// if the setting is not found, try to get the default
	if(function_exists('setting_' . $name))
		$GLOBALS['settings'][$name] = call_user_func_array('setting_' . $name, array($GLOBALS['settings']));
	elseif(isset($GLOBALS['setting_' . $name]) && is_callable($GLOBALS['setting_' . $name]))
		$GLOBALS['settings'][$name] = call_user_func_array($GLOBALS['setting_' . $name], array($GLOBALS['settings']));
	
	// if the setting is loaded already use that
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting isn't found in the configuration
	raise_error('Setting \'' . $name . '\' not found!', E_VERBOSE);
		
	// return default setting
	if($default !== NULL)
		return $default;
}

/**
 * Get all the default settings
 * @ingroup setting
 */
function settings_get_defaults($settings)
{
	// existing settings are passed in to this function incase a default depends on something already set up
	
	// new settings to return
	$default_settings = array();
	
	// loop through each module and get the default settings
	foreach(get_modules() as $module => $config)
	{
		if(isset($config['settings']) && is_array($config['settings']))
		{
			foreach($config['settings'] as $i => $setting)
			{
				if(function_exists('setting_' . $setting))
					$new_setting = call_user_func_array('setting_' . $setting, array($settings));
				elseif(isset($GLOBALS['setting_' . $setting]) && is_callable($GLOBALS['setting_' . $setting]))
					$new_setting = $GLOBALS['setting_' . $setting]($settings);
				else
					raise_error('Setting \'' . $setting . '\' is specified without a validate function in the ' . $module . ' module.', E_DEBUG);
			
				if(isset($new_setting))
					$default_settings[$setting] = $new_setting;
				else
					unset($default_settings[$setting]);
			}
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

function status_settings($settings)
{
	$status = array();
	
	if(setting_modrewrite($settings))
	{
		$status['modrewrite'] = array(
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
		$status['modrewrite'] = array(
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
	
	return $status;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_settings($settings, $request)
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
	
	$options['database_reset'] = array(
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
	
	$options['all_reset'] = array(
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
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['users']['Settings']))
}

