<?php

// the most basic settings for getting the system running
// all other settings are stored in the appropriate classes that handle each section

// database connection constants
define('USE_DATABASE', 	                  true); // set to false to make handlers load information about every file on the fly
define('DB_SERVER',                'localhost');
define('DB_USER',                    'tmpuser');
define('DB_PASS',                    'tmppass');
define('DB_NAME',                'mediaserver');
define('DB_TYPE',				       'mysql');

define('DB_CONNECT',			DB_TYPE . '://' . DB_USER . ':' . DB_PASS . '@' . DB_SERVER . '/' . DB_NAME);

// this secrect key is prepended to all passwords before encryption
//   this is so if someone access the database, they still must know the secret key before they can get the passwords
//   this key should be very random
define('DB_SECRET', 		'QyzoH2zqp%MGs1yD');

// site constants these are used throughout the entire system
define('LOCAL_ROOT',                dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// where to put the user directory for storing user uploaded files
//   this directory must be writtable by the web server
define('LOCAL_USERS', 							LOCAL_ROOT . 'users' . DIRECTORY_SEPARATOR);

// this template folder includes all the files in pages that are accessible
//  this includes the types of list outputs so other templates don't have to reimplement them to use them
define('LOCAL_BASE',            				        'templates' . DIRECTORY_SEPARATOR . 'plain' . DIRECTORY_SEPARATOR);

// this is the local filesystem path to the default template, this path should not be used in web pages, instead use HTML_TEMPLATE
//  this is the template that is used when a template is not specified
define('LOCAL_DEFAULT',            				        'templates' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR);

// this is the optional template that will be used
// if this is defined here, the user will not be given an option to choose a template
#define('LOCAL_TEMPLATE',            					 'templates' . DIRECTORY_SEPARATOR . 'extjs' . DIRECTORY_SEPARATOR);

// this is the path used by html pages to refer back to the website domain, HTML_ROOT is usually appended to this
define('HTML_DOMAIN',            			    'http://dev.bjcullinan.com');

// this is the root directory of the site, this is needed if the site is not running on it's own domain
// this is so HTML pages can refer to the root of the site, without making the brower validate the entire domain, this saves time loading pages
// a slash / is always preppended to this when the HTML_DOMAIN is not preceeding this
define('HTML_ROOT',                                        '/');

// extra constants
// site name is used through various templates, generally a subtitle is appended to this
define('HTML_NAME',			                                   'Brian\'s Media Website'); // name for the website

// commands, these are used for converting media throughout the site
// multiple definitions can be set with the extension in all caps for different file types
//  for example CONVERT_JPG, and CONVERT_ARGS_JPG can be used for converting any file with the JPG extension
//  if %IF is not found in the string the modules and handlers will assume STDIN is used, additional functionality will be enabled in this case
//  same for %0F, STDOUT will be used and this will enable much faster response times

// the arguments to use with encode are as follows
/*
*/
// More options can be added but you will have to do some scripting in the convert.php module
define('CONVERT', 				   ''); // image magick's convert program
define('CONVERT_ARGS', 			   ''); // image magick's convert program

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%VC - Video Codec to be used in the conversion
%AC - Audio Codec
%VB - Video Bitrate
%AB - Audio Bitrate
%SR - Sample Rate
%SR - Scale
%CH - Number of Channels
%MX - Muxer to use for encapsulating the streams
%TO - Time Offset for resumable listening and moving time position
%FS - Frames per Second
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the encode.php module
// remember ffmpeg uses generally the same codec names as the default vlc, however custom commands may be needed to convert to each type
define('ENCODE', 				       '/usr/bin/vlc'); // a program that can convert video and audio streams
define('ENCODE_ARGS',                  ''); // a program that can convert video and audio streams

// the arguments to use with archive are as follows
/*
%IF - Input file
%OF - Output file if necessary
*/
define('ARCHIVE_RAR',                                'C:\Program Files\WinRAR\Rar.exe'); // a program that can convert video and audio streams
define('ARCHIVE_ARGS_RAR',                           ' p %IF'); // a program that can convert video and audio streams

// finally some general options, just used to avoid hardcoding stuff

// debug mode is used by many templates to display debugging options on the page
define('DEBUG_MODE', 							true);

// when a user tries to access a directory listing, this will load missing directories on the fly
//   this is good when there are few files in a directory, but the site hasn't scanned them all
//   don't use this when there are many complex files and the site has loaded thousands already
define('RECURSIVE_GET', 				false);

// this will redirect a google bot to the sitemap.php module or remove request options like search
//   this is recommended because GoogleBots usually look for the wrong information and this will slow down the site A LOT
define('NO_BOTS', 							true);

// max amount to output when accessing a file
define('BUFFER_SIZE', 	                         2*1024*8);

// a temporary directory to use for creating thumbnails
define('TMP_DIR', 	                             '/tmp/');

// set to true in order to use aliased paths for output of Filepath
// USE_DATABASE must be enabled in order for this to be used!
define('USE_ALIAS', 							 true);

// how long to search for directories that have changed
define('DIRECTORY_SEEK_TIME',		120);

// how long to search for changed files or add new files
define('FILE_SEEK_TIME', 		   240);

// how long to clean up files
define('CLEAN_UP_BUFFER_TIME',				45);

// how many times should we be able to run the cron script before a cleanup is needed?
//  cleanup will also be fired when a directory change is detected, this may not always be accurate
define('CLEAN_UP_THREASHOLD', 				10);

ini_set('include_path', '.:/usr/share/php:/usr/share/pear:' . LOCAL_ROOT . 'include/');

// comment-out-able
ini_set('error_reporting', E_ALL);

?>