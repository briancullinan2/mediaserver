<?php

// the most basic settings for getting the system running
// all other settings are stored in the appropriate classes that handle each section

// global admin username and pass
define('ADMIN_USER',			'bjcullinan');
define('ADMIN_PASS',			  'Da1ddy23');


// database connection constants
define('DB_SERVER',                'localhost');
define('DB_USER',                 'bjcullinan');
define('DB_PASS',                   'Da1ddy23');
define('DB_NAME',                'mediaserver');
define('DB_TYPE',				       'mysql');
define('DB_PREFIX',				         'db_');

// site constants these are used throughout the entire system
define('SITE_LOCALROOT',                '/var/www/mediaserver/');

// the directory that contains all the different modules
define('MODULES_DIR', SITE_LOCALROOT . 'modules/');

define('SITE_HTMLPATH',            			    'http://dev.bjcullinan.com/');
define('SITE_HTMLROOT',                                  '');
define('SITE_DEFAULT',            				   'templates/default/');

// set the template
if(isset($_REQUEST['template']))
{
	if(substr($_REQUEST['template'], strlen($_REQUEST['template']) - 1, 1) != '/')
		$_REQUEST['template'] .= '/';
	define('SITE_TEMPLATE',            					 'templates/' . $_REQUEST['template']);
	$_SESSION['template'] = $_REQUEST['template'];
}
elseif(isset($_SESSION['template']))
{
	define('SITE_TEMPLATE',            					 'templates/' . $_SESSION['template']);
}
else
{
	define('SITE_TEMPLATE',            					 'templates/default/');
	if(preg_match('/.*mobile.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		$_SESSION['template'] = 'default/';
	}
	// don't set a template, allow them to choose
}

// plugins directory	
define('SITE_PLUGINS', 						 SITE_HTMLROOT . 'plugins/');

// extra constants
define('SITE_NAME',			 'Brian\'s Media Website'); // name for the website
define('CONVERT', 				   '/usr/bin/convert'); // image magick's convert program
define('BUFFER_SIZE', 						  24*1024); // max amount to output when accessing a file
define('TMP_DIR', 						      '/tmp/'); // a temporary directory to use for creating thumbnails
define('USE_ALIAS', 							 true); // set to true in order to use aliased paths for output of Filepath
if(!defined('DIR_SEP')) {
    define('DIR_SEP', DIRECTORY_SEPARATOR);
}

// comment-out-able
ini_set('error_reporting', E_ALL);


loadMime();


function loadMime()
{
	if(file_exists('/etc/mime.types'))
	{
		$handle = fopen('/etc/mime.types', 'r');
		$mime_text = fread($handle, filesize('/etc/mime.types'));
		fclose($handle);
		
		$mimes = split("\n", $mime_text);
		
		$mime_to_ext = array();
		$ext_to_mime = array();
		$ext_to_type = array();
		$type_to_ext = array();
		foreach($mimes as $index => $mime)
		{
			$mime = preg_replace('/#.*?$/', '', $mime);
			if($mime != '')
			{
				// mime to ext
				$file_types = preg_split('/[\s,]+/', $mime);
				$mime_type = $file_types[0];
				// general type
				$tmp_type = split('/', $mime_type);	
				$type = $tmp_type[0];
				// unset mime part to get all its filetypes
				unset($file_types[0]);
				
				// ext to mime
				foreach($file_types as $index => $ext)
				{
					$ext_to_mime[$ext] = $mime_type;
					$ext_to_type[$ext] = $type;
					$type_to_ext[$type][] = $ext;
					$mime_to_ext[$mime_type][] = $ext;
				}
			}
		}
		
		
		// set global variables
		$GLOBALS['ext_to_mime'] = $ext_to_mime;
		$GLOBALS['mime_to_ext'] = $mime_to_ext;
		$GLOBALS['ext_to_type'] = $ext_to_type;
		$GLOBALS['type_to_ext'] = $type_to_ext;
		

	}
	
}



?>