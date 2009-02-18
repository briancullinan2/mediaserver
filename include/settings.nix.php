<?php

// the most basic settings for getting the system running
// all other settings are stored in the appropriate classes that handle each section

// global admin username and pass
define('ADMIN_USER',			'bjcullinan');
define('ADMIN_PASS',			   'tmppass');


// database connection constants
define('USE_DATABASE', 	                  true); // set to false to make modules load information about every file on the fly
define('DB_SERVER',                'localhost');
define('DB_USER',                 'bjcullinan');
define('DB_PASS',                   'Da1ddy23');
define('DB_NAME',                'mediaserver');
define('DB_TYPE',				       'mysql');

// this prefix can be used to include completely different sets of files in the same database
// don't be decieved though, some files use the db_<file type> where <file type> refers to a module!
define('DB_PREFIX',				         'db_');

// site constants these are used throughout the entire system
define('LOCAL_ROOT',                '/var/www/mediaserver/cross_platform/');

// this is the path used by html pages to refer back to the website domain, HTML_ROOT is usually appended to this
define('HTML_DOMAIN',            			    'http://dev.bjcullinan.com/');

// this is the root directory of the site, this is needed if the site is not running on it's own domain
// this is so HTML pages can refer to the root of the site, without making the brower validate the entire domain, this saves time loading pages
// a slash / is always preppended to this when the HTML_DOMAIN is not preceeding this
define('HTML_ROOT',                                        'cross_platform/');

// this is the local filesystem path to the default template, this path should not be used in web pages, instead use HTML_TEMPLATE
define('LOCAL_DEFAULT',            				        'templates/default/');

// this is the optional template that will be used
// if this is defined here, the user will not be given an option to choose a template
#define('LOCAL_TEMPLATE',            					 'templates\extjs\\');

// plugins directory	
// this is the path for templates to access the plugins directory to provide links to extra functionality
// this should be the absolute path from the root of the site
define('HTML_PLUGINS', 						 'plugins/');

// extra constants
// site name is used through various templates, generally a subtitle is appended to this
define('HTML_NAME',			                                   'Brian\'s Media Website'); // name for the website

// commands, these are used for converting media throughout the site
// multiple definitions can be set with the extension in all caps for different file types
//  for example CONVERT_JPG, and CONVERT_ARGS_JPG can be used for converting any file with the JPG extension
//  if %IF is not found in the string the plugins and modules will assume STDIN is used, additional functionality will be enabled in this case
//  same for %0F, STDOUT will be used and this will enable much faster response times

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%FM - Format to output
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the convert.php plugin
define('CONVERT', 				   '/usr/bin/convert'); // image magick's convert program
define('CONVERT_ARGS', 			   '"%IF" %FM:-'); // image magick's convert program

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%VC - Video Codec to be used in the conversion
%AC - Audio Codec
%VB - Video Bitrate
%AB - Audio Bitrate
%SR - Sample Rate
%CH - Number of Channels
%MX - Muxer to use for encapsulating the streams
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the encode.php plugin
// remember ffmpeg uses generally the same codec names as the default vlc, however custom commands may be needed to convert to each type
define('ENCODE', 				       '/usr/bin/vlc'); // a program that can convert video and audio streams
define('ENCODE_ARGS',                  '-I dummy "%IF" :sout=\'#transcode{vcodec=%VC,acodec=%AC,vb=%VB,ab=%AB,samplerate=%SR,channels=%CH,deinterlace,audio-sync}:std{mux=%MX,access=file,dst=-}\' vlc://quit'); // a program that can convert video and audio streams

// the arguments to use with archive are as follows
/*
%IF - Input file
%OF - Output file if necessary
*/
define('ARCHIVE_RAR',                                'C:\Program Files\WinRAR\Rar.exe'); // a program that can convert video and audio streams
define('ARCHIVE_ARGS_RAR',                           ' p %IF'); // a program that can convert video and audio streams

// finally some general options, just used to avoid hardcoding stuff
define('BUFFER_SIZE', 	                         2048); // max amount to output when accessing a file
define('TMP_DIR', 	                             '/tmp/'); // a temporary directory to use for creating thumbnails
// USE_DATABASE must be enabled in order for this to be used!
define('USE_ALIAS', 							 true); // set to true in order to use aliased paths for output of Filepath

// comment-out-able
ini_set('error_reporting', E_ALL);

?>