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
		'name' => 'Core Functions',
		'description' => 'Adds core functionality to site that other common modules depend on.',
		'path' => __FILE__,
		'privilage' => 1,
		'settings' => array(
			'system_type', 'local_root', 'settings_file', 'writable_settings_file',
			'modrewrite', 'memory_limit', 'exists_local_root', 'exists_adodb', 'exists_pear',
			'exists_getid3', 'exists_snoopy', 'exists_extjs',
		),
	);
}
/**
 * @}
 */

/**
 * Create a global variable for storing all the module information
 * @ingroup setup
 */
function setup_register()
{
	$GLOBALS['modules'] = array(
		'index' => array(
			'name' => 'Index',
			'description' => 'Load a module\'s output variables and display the template.',
			'privilage' => 1,
			'path' => dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'index.php',
			'alter query' => array('limit', 'start', 'direction', 'order_by', 'group_by'),
			'settings' => array('html_domain', 'html_root', 'html_name', 'tmp_dir', 'writable_tmp_dir', 'debug_mode', 'recursive_get', 'no_bots', 'buffer_size')
		),
		'database' => array(
			'name' => 'Database',
			'description' => 'Wrapper module for displaying database configuration',
			'privilage' => 10,
			'path' => dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'database.php',
			'settings' => array('db_connect', 'db_type', 'db_server', 'db_user', 'db_pass', 'db_name', 'use_alias')
		),
	);
	$GLOBALS['triggers'] = array('session' => array(), 'settings' => array());
	
	// read module list and create a list of available modules	
	setup_register_modules('modules' . DIRECTORY_SEPARATOR);
	
	// load the modules module again because the config depends on all the other modules
	$GLOBALS['admin']['modules']['modules'] = register_admin_modules();
	$GLOBALS['modules']['admin_modules'] = $GLOBALS['admin']['modules']['modules'];
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
 * @return The parent directory of the admin directory by default
 */
function setting_local_root($settings)
{
	if(isset($settings['local_root']) && is_dir($settings['local_root']))
		return $settings['local_root'];
	else
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
	
	if(isset($settings['html_root']) && @parse_url($settings['html_domain'] . $settings['html_root']) !== false)
		return $settings['html_root'];
	else
		return ((substr($settings['local_root'], 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'])?substr($settings['local_root'], strlen($_SERVER['DOCUMENT_ROOT'])):'');
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
 * @return the temp directory reported by the OS by default
 */
function setting_tmp_dir($settings)
{
	if(isset($settings['tmp_dir']) && is_dir($settings['tmp_dir']))
		return $settings['tmp_dir'];
	else
	{
		$tmpfile = tempnam("dummy","");
		unlink($tmpfile);
		return dirname($tmpfile) . DIRECTORY_SEPARATOR;
	}
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
function setting_settings_file($settings)
{
	return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'settings.ini';
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_writable_settings_file($settings)
{
	return is_writable(setting_settings_file($settings));
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_writable_tmp_dir($settings)
{
	$settings['tmp_dir'] = setting_tmp_dir($settings);
	return is_writable($settings['tmp_dir']);
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
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_memory_limit($settings)
{
	return (intval(ini_get('memory_limit')) >= 96);
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_local_root($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	return file_exists($settings['local_root'] . 'include');
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_adodb($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	return file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php');
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_pear($settings)
{
	return ((@include_once 'PEAR.php') == true);
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_getid3($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	return file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.lib.php');
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_snoopy($settings)
{
	$settings['local_root'] = setting_local_root($settings);
	return file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'Snoopy.class.php');
}

/**
 * Implementation of setting, basic wrapper for checks
 * @ingroup setting
 */
function setting_exists_extjs($settings)
{
	return file_exists($settings['local_root'] . 'templates' . DIRECTORY_SEPARATOR . 'plain' . DIRECTORY_SEPARATOR . 'extjs' . DIRECTORY_SEPARATOR . 'ext-all.js');
}

/**
 * @defgroup configure Configure Functions
 * All functions that all configuring of settings for a module
 * @param settings The request array that contains values for configuration options
 * @return an associative array that describes the configuration options
 * @{
 */

/**
 * Implementation of configure
 */
function configure_index($settings)
{
	$settings['html_root'] = setting_html_root($settings);
	$settings['html_domain'] = setting_html_domain($settings);
	$settings['html_name'] = setting_html_name($settings);
	$settings['tmp_dir'] = setting_tmp_dir($settings);
	$settings['debug_mode'] = setting_debug_mode($settings);
	$settings['recursive_get'] = setting_recursive_get($settings);
	$settings['no_bots'] = setting_no_bots($settings);
	$settings['buffer_size'] = setting_buffer_size($settings);
	
	$options = array();
	
	// domain and root
	$options['html_domain'] = array(
		'name' => 'HTML Domain',
		'status' => '',
		'description' => array(
			'list' => array(
				'This is the path that you would like to access the site.',
				'This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.',
			),
		),
		'type' => 'text',
		'value' => $settings['html_domain'],
	);

	$options['html_root'] = array(
		'name' => 'HTML Root',
		'status' => '',
		'description' => array(
			'list' => array(
				'This is the directory that the site is accessed through.',
				'This allows the site to run along site another website, in the specified directory.  This is needed so that templates can find the right path to images and styles.',
				'This path must also end with the HTTP separator /.',
				'The server reports the DOCUMENT ROOT is ' . $_SERVER['DOCUMENT_ROOT'],
			),
		),
		'type' => 'text',
		'value' => $settings['html_root'],
	);

	// site name
	$options['html_name'] = array(
		'name' => 'Site Name',
		'status' => '',
		'description' => array(
			'list' => array(
				'Some templates can display a name for this media server.  Set this here.',
			),
		),
		'type' => 'text',
		'value' => $settings['html_name'],
	);
	
	if(setting_writable_tmp_dir($settings))
	{
		$options['tmp_dir'] = array(
			'name' => 'Temporary Files',
			'status' => '',
			'description' => array(
				'list' => array(
					'This directory will be used for uploaded files and storing temporary files like converted files and images.',
				),
			),
			'type' => 'text',
			'value' => $settings['tmp_dir'],
		);
	}
	else
	{
		$options['tmp_dir'] = array(
			'name' => 'Temporary Files',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that this directory does not exist or is not writable.',
					'Please correct this error by entering a directory path that exists and is writable by the web server.',
				),
			),
			'type' => 'text',
			'value' => $settings['tmp_dir'],
		);
	}
	
	$options['debug_mode'] = array(
		'name' => 'Debug Mode',
		'status' => '',
		'description' => array(
			'list' => array(
				'Debug mode is used by many templates to display debugging options on the page.',
				'This is usefull for viewing information about file system and database problems and to test if the system is running properly.',
			),
		),
		'type' => 'boolean',
		'value' => $settings['debug_mode'],
		'options' => array(
			'Turn Debug Mode On',
			'Do Not Use Debug Mode',
		)
	);
	
	$options['recursive_get'] = array(
		'name' => 'Deep Select',
		'status' => '',
		'description' => array(
			'list' => array(
				'This tells to system whether or not it should read directories on the fly and recursively.',
				'If some files in a directory haven\'t been loaded, this will load them when the directory is accessed.',
				'On large systems, this could cause page response to be VERY SLOW.  This option is not recommended for system where files change a lot.',
			),
		),
		'type' => 'boolean',
		'value' => $settings['recursive_get'],
		'options' => array(
			'Turn Deep Select On',
			'Do Not Use Deep Select',
		)
	);
	
	$options['no_bots'] = array(
		'name' => 'Robots Handling',
		'status' => '',
		'description' => array(
			'list' => array(
				'Some services like Google like to scan websites.  This option will prevent robots from downloading and scanning files on your site.',
				'This will also enable robots to view a customizable sitemap.php module that provides them with the information they deserve.',
			),
		),
		'type' => 'boolean',
		'value' => $settings['no_bots'],
		'options' => array(
			'Disable Robots',
			'Allow Robots to Scan my Files',
		)
	);
	
	$options['buffer_size'] = array(
		'name' => 'Buffer Size',
		'status' => '',
		'description' => array(
			'list' => array(
				'Some modules and modules require open file streams of a specific size.  This allows you to set what size these streams should try to remain below.',
			),
		),
		'type' => 'filesize',
		'value' => $settings['buffer_size'],
	);
	
	return $options;
}

/**
 * Implementation of configure. Checks for convert path
 * @ingroup configure
 */
function configure_core($settings)
{
	$settings['system_type'] = setting_system_type($settings);
	$settings['local_root'] = setting_local_root($settings);
	
	$options = array();
	
	// system type
	$options['system_type'] = array(
		'name' => 'System Type',
		'status' => '',
		'description' => array(
			'list' => array(
				'The system has detected that you are running ' . (($settings['system_type']=='win')?'Windows':(($settings['system_type']=='nix')?'Linux or Unix':'Mac OS')) . '.',
				'If this is not correct, you must specify the system type so the media server can be optimized for your system.',
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
	
	// settings permission
	if(setting_writable_settings_file($settings))
	{
		$options['writable_settings_file'] = array(
			'name' => 'Access to Settings',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.',
				),
			),
			'type' => 'text',
			'disabled' => true,
			'value' => setting_settings_file($settings),
		);
	}
	else
	{
		$options['writable_settings_file'] = array(
			'name' => 'Access to Settings',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system would like access to the following file.  This is so it can write all the settings when we are done with the install.',
					'Please create this file, and grant it Read/Write permissions.',
				),
			),
			'type' => 'text',
			'disabled' => true,
			'value' => setting_settings_file($settings),
		);
	}
	
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

	if(setting_memory_limit($settings))
	{
		$options['memory_limit'] = array(
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
		$options['memory_limit'] = array(
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
	
	if(setting_exists_local_root($settings))
	{
		$options['local_root'] = array(
			'name' => 'Local Root',
			'status' => '',
			'description' => array(
				'list' => array(
					'This is the directory that the site lives in.',
					'This directory MUST end with a directory seperate such as / or \.',
				),
			),
			'type' => 'text',
			'value' => $settings['local_root'],
		);
	}
	else
	{
		$options['local_root'] = array(
			'name' => 'Local Root',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that there is no "include" directory in the site root folder.  You must specify the root directory that the site lives in.',
					'This directory MUST end with a directory seperate such as / or \.',
				),
			),
			'type' => 'text',
			'value' => $settings['local_root'],
		);
	}
	
	if(setting_exists_adodb($settings))
	{
		$options['exists_adodb'] = array(
			'name' => 'ADOdb Library',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that ADOdb is installed in the includes directory.',
					'ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.',
				),
			),
			'type' => 'label',
			'value' => 'ADOdb Detected',
		);
	}
	else
	{
		$options['exists_adodb'] = array(
			'name' => 'ADOdb Library Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that ADOdb is NOT INSTALLED.',
					'The root of the ADOdb Library must be placed in &lt;site root&gt;/include/adodb5',
					'ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://adodb.sourceforge.net/',
					'text' => 'Get ADOdb',
				),
			),
		);
	}
	
	$pear_libs = array('File/Archive.php' => 'File_Archive', 'MIME/Type.php' => 'MIME_Type', 'Text/Highlighter.php' => 'Text_Highlighter');
	$not_installed = array();
	$installed = array();
	foreach($pear_libs as $lib => $link)
	{
		if((@include_once $lib) === false)
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
	
	if(setting_exists_pear($settings))
	{
		$options['exists_pear'] = array(
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
			$options['exists_pear']['value']['text'][] = 'However, the following packages must be installed:';
			$options['exists_pear']['value']['text'][] = array('list' => $not_installed);
		}
		else
		{
			$options['exists_pear']['value']['text'][] = 'The following required packages are also installed:';
			$options['exists_pear']['value']['text'][] = array('list' => $installed);
		}
	}
	else
	{
		$options['exists_pear'] = array(
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
	
	
	if(setting_exists_getid3($settings))
	{
		$options['exists_getid3'] = array(
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
		$options['exists_getid3'] = array(
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
	
	if(setting_exists_snoopy($settings))
	{
		$options['exists_snoopy'] = array(
			'name' => 'Snoopy cUrl API',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that the Scoopy cUrl API is installed in the includes directory.',
					'Snoopy is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.',
				),
			),
			'type' => 'label',
			'value' => 'Snoopy detected',
		);
	}
	else
	{
		$options['exists_snoopy'] = array(
			'name' => 'Snoopy cUrl API Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that Snoopy cUrl API is NOT INSTALLED.',
					'The Snoopy class (Snoopy.class.php) must be placed in &lt;site root&gt;/include/',
					'Snoopy is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://sourceforge.net/projects/snoopy/',
					'text' => 'Get Snoopy',
				),
			),
		);
	}

	if(setting_exists_extjs($settings))
	{
		$options['exists_extjs'] = array(
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
		$options['exists_extjs'] = array(
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
	
	return $options;
}
/**
 * @}
 */

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
	$files = fs_file::get(array('dir' => $GLOBALS['settings']['local_root'] . $path, 'limit' => 32000), $count, true);
	
	$modules = array();

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
				$prefix = substr($file['Filepath'], strlen($GLOBALS['settings']['local_root']), -strlen($module));
				
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
				if(isset($modules[$module]['session']))
				{
					foreach($modules[$module]['session'] as $i => $var)
					{
						$GLOBALS['triggers']['session'][$var][] = $prefix . $module;
					}
				}
				
				// reorganize alter query triggers
				if(isset($modules[$module]['alter query']))
				{
					foreach($modules[$module]['alter query'] as $i => $var)
					{
						$GLOBALS['triggers']['alter query'][$var][] = $prefix . $module;
					}
				}
			}
		}
	}
	
	return $modules;
}

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
		// if there is no validator
		else
		{
			// unset it to prevent anything from using the input
			unset($_REQUEST[$key]);
			if(isset($_GET[$key])) unset($_GET[$key]);
		}
			
		// set the get variable also, so that when url($_GET) is used it is an accurate representation of the current page
		if(isset($_GET[$key])) $_GET[$key] = $_REQUEST[$key];
	}
	
	// call the session save functions
	session($_REQUEST);
	
	// do not let GoogleBot perform searches or file downloads
	if($GLOBALS['settings']['no_bots'])
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
 * @defgroup session Session Save Functions
 * All functions that save information to the session for later reference
 * @param request The full request array to use for saving request information to the session
 * @return An associative array to be saved to $_SESSION[&lt;module&gt;] = session_select($request);
 * @{
 */
 
/**
 * Save session information based on triggers specified by a module configuration
 */
function session($request = array())
{
	// check modules for vars and trigger a session save
	foreach($request as $key => $value)
	{
		if(isset($GLOBALS['triggers']['session'][$key]))
		{
			foreach($GLOBALS['triggers']['session'][$key] as $i => $module)
			{
				$_SESSION[$module] = call_user_func_array('session_' . $module, array($request));
			}
		}
	}
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
	$link = (($include_domain)?$GLOBALS['settings']['html_domain']:'') . 
		$GLOBALS['settings']['html_root'] . 
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
 * Function to call before the template is called, this can also be called from the first time #theme() is called
 * This sets all the register variables as HTML or original content, it also removes all unnecissary variables that might be used to penetrate the site
 */
function set_output_vars()
{
	// set a couple more that are used a lot
	
	// if the search is set, then alway output because any module that uses a get will also use search
	if(isset($_REQUEST['search']))
	{
		output_search($_REQUEST);
	}
	
	// the entire site depends on this
	register_output_vars('module', $_REQUEST['module']);
	
	// most template pieces use the category variable, so set that
	register_output_vars('cat', $_REQUEST['cat']);
	
	// some templates refer to the dir to determine their own location
	if(isset($_REQUEST['dir'])) register_output_vars('dir', $_REQUEST['dir']);
	
	// this is just a helper variable for templates to use that only need to save 1 setting
	if(isset($_REQUEST['extra'])) register_output_vars('extra', $_REQUEST['extra']);
	
	// some templates would like to submit to their own page, generate a string based on the current get variable
	register_output_vars('get', url($_GET, true));
	
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
	
	// do not remove these variables
	$dont_remove = array(
		'GLOBALS',
		//'_REQUEST', // allow this because it has been fully validated
		'_SESSION', // purely for error handling
		'templates',
		'errors',
		'debug_errors',
		'user_errors',
		'warn_errors',
		'note_errors',
		'output',
		'alias',
		'alias_regexp',
		'paths',
		'paths_regexp',
		'mte',
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
 * @return db_file/fs_file handler by default
 */
function validate_cat($request)
{
	if(isset($request['cat']) && (substr($request['cat'], 0, 3) == 'db_' || substr($request['cat'], 0, 3)))
		$request['cat'] = (($GLOBALS['settings']['use_database'])?'db_':'fs_') . substr($request['cat'], 3);
	if(!isset($request['cat']) || !in_array($request['cat'], $GLOBALS['handlers']) || constant($request['cat'] . '::INTERNAL') == true)
		return $GLOBALS['settings']['use_database']?'db_file':'fs_file';
	return $request['cat'];
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
	
	$columns = call_user_func($handler . '::columns');
	
	if( !isset($request['order_by']) || !in_array($request['order_by'], $columns) )
	{
		if(isset($request['search']))
			return 'Relevance';
			
		// make sure if it is a list that it is all valid columns
		$columns = split(',', (isset($request['order_by'])?$request['order_by']:''));
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
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
	
	$columns = call_user_func($handler . '::columns');
	
	if( isset($request['group_by']) && !in_array($request['group_by'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		$columns = split(',', $request['group_by']);
		foreach($columns as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
				unset($columns[$i]);
		}
		if(count($columns) == 0)
			return;
		else
			return join(',', $columns);
	}
	return $request['group_by'];
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
	
	$columns = call_user_func($handler . '::columns');
	
	// which columns to search
	if( isset($request['columns']) && !in_array($request['columns'], $columns) )
	{
		// make sure if it is a list that it is all valid columns
		if(!is_array($request['columns'])) $request['columns'] = split(',', $request['columns']);
		foreach($request['columns'] as $i => $column)
		{
			if(!in_array($column, call_user_func($handler . '::columns')))
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
	if(defined('NOT_INSTALLED') && NOT_INSTALLED == true)
		return '';
	
	// make sure the module doesn't actually exists on the web server
	if(file_exists($GLOBALS['settings']['local_root'] . $path))
	{
		// a path without all the underscores replaced would be better then no path at all
		$path = $request['module'] . '/';

		if(file_exists($GLOBALS['settings']['local_root'] . $path))
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
	$request['module'] = validate_module($request);
	
	if(isset($request['path_info']))
		$request = array_merge($request, parse_path_info($request['path_info']));
		
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
	$props['LIMIT'] = $request['start'] . ',' . $request['limit'];
	
	if(isset($request['group_by'])) $props['GROUP'] = validate_group_by($request);

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
		
		theme('errors');
		
		// remove old errors from session
		$_SESSION['errors']['user'] = array();
		$_SESSION['errors']['warn'] = array();
		$_SESSION['errors']['note'] = array();
		
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
