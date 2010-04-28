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
 * Implementation of register
 * @ingroup register
 */
function register_settings()
{
	return array(
		'name' => 'Settings',
		'description' => 'This allows users to save theme settings.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

/**
 * set up all the default site settings
 * @ingroup setup
 */
function setup_settings()
{
	if(!isset($GLOBALS['settings']))
		$GLOBALS['settings'] = array();
		
	// merge everything with the default settings
	$GLOBALS['settings'] = array_merge(settings_get_defaults(), $GLOBALS['settings']);
}

/**
 * Get a setting
 * @param name The setting name to get
 */
function setting($name)
{
	if(isset($GLOBALS['settings'][$name]))
		return $GLOBALS['settings'][$name];
		
	// if the setting isn't found in the configuration
	PEAR::raiseError('Setting \'' . $name . '\' not found!', E_DEBUG);
}

/**
 * Get all the default settings
 */
function settings_get_defaults()
{
	if(realpath('/') == '/')
	{
		if(file_exists('/Users/'))
			$settings['system_type'] = 'mac';
		else
			$settings['system_type'] = 'nix';
	}
	else
		$settings['system_type'] = 'win';
	
	// convert path
	if(setting('system_type') == 'win')
		$settings['convert_path'] = 'C:\Program Files\ImageMagick-6.4.9-Q16\convert.exe';
	else
		$settings['convert_path'] = '/usr/bin/convert';

	// local root
	$settings['local_root'] = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;

	// html domain
	$settings['html_domain'] = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['HTTP_HOST'] . (($_SERVER['SERVER_PORT'] != 80)?':' . $_SERVER['SERVER_PORT']:'');

	// html root
	$settings['html_root'] = ((substr(setting('local_root'), 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'])?substr(setting('local_root'), strlen($_SERVER['DOCUMENT_ROOT'])):'');

	// database
	$settings['use_database'] = false;
	$settings['db_type'] = 'mysql';
	$settings['db_server'] = 'localhost';
	$settings['db_user'] = '';
	$settings['db_pass'] = '';
	$settings['db_name'] = '';
	
	// html name
	$settings['html_name'] = 'Brian\'s Media Website';
	
	// template
	$settings['local_base'] = 'plain';
	$settings['local_default'] = 'live';
	$settings['local_template'] = '';
	
	// other
	$settings['dir_seek_time'] = 60;
	$settings['file_seek_time'] = 60;
	$settings['debug_mode'] = false;
	$settings['recursive_get'] = false;
	$settings['no_bots'] = true;
	
	// tmp dir
	$tmpfile = tempnam("dummy","");
	unlink($tmpfile);
	$settings['tmp_dir'] = dirname($tmpfile) . DIRECTORY_SEPARATOR;

	// users
	$settings['local_users'] = setting('local_root') . 'users' . DIRECTORY_SEPARATOR;
	
	// buffer size
	$settings['buffer_size'] = 2*1024*8;
	
	// use alias
	$settings['use_alias'] = true;
	
	return $settings;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_settings($request)
{
	//if(isset($_SESSION['user']['Settings']))
}

