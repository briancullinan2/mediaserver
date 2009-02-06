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
define('LOCAL_ROOT',                'C:\wamp\www\mediaserver\\');

// the directory that contains all the different modules
define('LOCAL_MODULES', 'modules\\');

// this is the path used by html pages to refer back to the website domain, HTML_ROOT is usually appended to this
define('HTML_DOMAIN',            			             'http://127.0.0.1/');
// this is the root directory of the site, this is needed if the site is not running on it's own domain
// this is so HTML pages can refer to the root of the site, without making the brower validate the entire domain, this saves time loading pages
// a slash / is always appended to this when the HTML_DOMAIN is not preceeding this
define('HTML_ROOT',                                           'mediaserver/');
// this is the local filesystem path to the default template, this path should not be used in web pages, instead use HTML_TEMPLATE
define('LOCAL_DEFAULT',            				        'templates\default\\');
// this is the optional template that will be used
// if this is defined here, the user will not be given an option to choose a template
#define('LOCAL_TEMPLATE',            					 'templates\extjs\\');

// plugins directory	
// this is the path for templates to access the plugins directory to provide links to extra functionality
// this should be the absolute path from the root of the site
define('HTML_PLUGINS', 						 HTML_ROOT . 'plugins/');

// extra constants
define('HTML_NAME',			 'Brian\'s Media Website'); // name for the website
define('CONVERT', 				   'C:\Program Files\ImageMagick-6.4.9-Q16\convert.exe'); // image magick's convert program
define('ENCODE', 				       '/usr/bin/vlc'); // a program that can convert video and audio streams
define('BUFFER_SIZE', 						  24*1024); // max amount to output when accessing a file
define('TMP_DIR', 						      '/tmp/'); // a temporary directory to use for creating thumbnails
define('USE_ALIAS', 							 false); // set to true in order to use aliased paths for output of Filepath

// comment-out-able
ini_set('error_reporting', E_ALL);

?>