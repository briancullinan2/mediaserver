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

define('SITE_HTMLPATH',            			    'http://209.250.30.30/');
define('SITE_HTMLROOT',                                  'mediaserver/');
define('SITE_DEFAULT',            SITE_LOCALROOT . 'templates/default/');
define('SITE_TEMPLATE',           SITE_LOCALROOT . 'templates/default/');
define('SITE_PLUGINS', 						 SITE_HTMLROOT . 'plugins/');

// extra constants
define('DELIMITER', 							' - ');
define('SITE_NAME',			 'Brian\'s Media Website');

// comment-out-able
ini_set('error_reporting', E_ALL);
ini_set('include_path', './:' . SITE_LOCALROOT . ':' . SITE_LOCALROOT . 'include/');


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