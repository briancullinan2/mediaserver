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
define('LOCAL_ROOT',                '/var/www/mediaserver/');

// the directory that contains all the different modules
define('LOCAL_MODULES', 'modules/');

define('HTML_DOMAIN',            			    'http://dev.bjcullinan.com/');
define('HTML_ROOT',                                                       '');
define('LOCAL_DEFAULT',            				        'templates/default/');

// plugins directory	
define('HTML_PLUGINS', 						 HTML_ROOT . 'plugins/');

// extra constants
define('HTML_NAME',			 'Brian\'s Media Website'); // name for the website
define('CONVERT', 				   '/usr/bin/convert'); // image magick's convert program
define('ENCODE', 				       '/usr/bin/vlc'); // a program that can convert video and audio streams
define('BUFFER_SIZE', 						  24*1024); // max amount to output when accessing a file
define('TMP_DIR', 						      '/tmp/'); // a temporary directory to use for creating thumbnails
define('USE_ALIAS', 							 true); // set to true in order to use aliased paths for output of Filepath

// comment-out-able
ini_set('error_reporting', E_ALL);



?>