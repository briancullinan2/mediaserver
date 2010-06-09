<?php
/**
 * the most basic functions used a lot<br />
 * things to consider:
 * - get the extension for a file using getExt
 * - get the file mime type
 */
 
/**
 * @name Version Information
 * DO NOT CHANGE!
 * @{ 
 * @enum VERSION set version for stuff to reference
 * @enum VERSION_NAME set the name for a text representation of the version
 */
define('VERSION', 			     '0.70.0');
define('VERSION_NAME', 			'Goliath');
/** @} */

/**
 * @name Error Levels
 * Error codes so we know which errors to print to the user and which to print to debug
 */
//@{
/** @enum E_DEBUG the DEBUG level error used for displaying errors in the debug template block */
define('E_DEBUG',					1);
/** @enum E_USER USER level errors are printed to the user by the templates */
define('E_USER',					2);
/** @enum E_WARN the WARN level error prints a different color in the error block, this is
 * used by parts of the site that cause problems that may not be intentional */
define('E_WARN',					4);
/** @enum E_FATAL the FATAL errors are ones that cause the script to end at an unexpected point */
define('E_FATAL',					8);
/** @enum E_NOTE the NOTE error level is used for displaying positive information to users such as
 * "account has been created" */
define('E_NOTE',					16);
//@}

//ini_set('include_path', '.');

//if(realpath('/') == '/')
//	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.nix.php';
//else
/** require the settings */
if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.ini'))
{
	if($GLOBALS['settings'] = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.ini', true))
	{
		// awesome settings are loaded properly
	}
	else
	{
		unset($GLOBALS['settings']);
	}
}

if(!isset($GLOBALS['settings']))
{
	// try and forward them to the install page
	if(!isset($_REQUEST['module'])) $_REQUEST['module'] = 'admin_install';
}


/** require pear for error handling */
if(include_once 'PEAR.php')
{
	include_once 'MIME' . DIRECTORY_SEPARATOR . 'Type.php';
}
else
{
	class PEAR_Error
	{
		var $code = 0;
		var $message = '';
		var $backtrace = array();
	}
	
	// bootstrap pear error handling but don't load any other pear dependencies
	class PEAR
	{
		static function raiseError($message, $code)
		{
			$error = new PEAR_Error();
			$error->code = $code;
			$error->message = $message;
			$error->backtrace = debug_backtrace();
			call_user_func_array(PEAR_ERROR_CALLBACK, array($error));
		}
		
		static function setErrorHandling($type, $error_func)
		{
			if(is_callable($error_func))
			{
				define('PEAR_ERROR_CALLBACK', $error_func);
			}
		}
	}
}
//session_cache_limiter('public');
session_start();

/** Set the error handler to use our custom function for storing errors */
error_reporting(E_ALL);
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
/** stores a list of all errors */
$GLOBALS['errors'] = array();
/** stores a list of all user errors */
$GLOBALS['user_errors'] = isset($_SESSION['errors']['user'])?$_SESSION['errors']['user']:array();
/** stores a list of all warnings */
$GLOBALS['warn_errors'] = isset($_SESSION['errors']['warn'])?$_SESSION['errors']['warn']:array();
/** stores a list of all debug information */
$GLOBALS['debug_errors'] = isset($_SESSION['errors']['debug'])?$_SESSION['errors']['debug']:array();
/** stores a list of all notices and friendly messages */
$GLOBALS['note_errors'] = isset($_SESSION['errors']['note'])?$_SESSION['errors']['note']:array();
//set_error_handler('php_to_PEAR_Error', E_ALL);
/** always begin the session */

/** set up all the GLOBAL variables needed throughout the site */
setup();

/**
 * @defgroup setup Setup Functions
 * All functions that are used to set up necissary parts of the site
 * These functions usually specify global variables
 * @{
 */
 
/**
 * Setup all the GLOBAL variables used throughout the site
 */
function setup()
{
	// this is where most of the initialization occurs, some global variables are set up for other pages and handlers to use
	//  first the variables are parsed out of the path, just incase mod_rewrite isn't enabled
	//  in order of importance, the database is set up, the handlers are loaded, the aliases and watch list are loaded, the template system is loaded
	
	/** require core functionality */
	include_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'core.php';
	
	// always include fs_file handler
	include_once setting_local_root() . 'handlers' . DIRECTORY_SEPARATOR . 'filesystem.php';
	include_once setting_local_root() . 'handlers' . DIRECTORY_SEPARATOR . 'files.php';
	
	// register all modules
	setup_core();
	
	/** require compatibility */
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'compatibility.php';

	// loop through modules and call setup function
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if(dependency($module) && function_exists('setup_' . $module))
		{
			call_user_func_array('setup_' . $module, array());
		}
		// disable the module if the dependencies are not met
		elseif(dependency($module) == false)
		{
			// this prevents us from disabling required modules on accident
			$GLOBALS['settings'][$module . '_enable'] = setting_module_enable(array($module . '_enable' => false), $module);
		}
	}
	
	// setup all the handlers
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		// do not set up handlers if dependency is not met
		if(dependency($handler) && function_exists('setup_' . $handler))
			call_user_func_array('setup_' . $handler, array());
		// disable the handler if the dependencies are not met
		elseif(dependency($handler) == false)
		{
			// this prevents us from disabling required modules on accident
			$GLOBALS['settings'][$handler . '_enable'] = setting_handler_enable(array($handler . '_enable' => false), $handler);
		}
	}

	//Remove annoying POST error message with the page is refreshed 
	//  better place for this?
	if(isset($_POST) && count($_POST) > 0)
	{
		$_SESSION['last_request'] = $_REQUEST;
		goto($_SERVER['REQUEST_URI']);
	}
	if(isset($_SESSION['last_request']))
	{
		$_REQUEST = $_SESSION['last_request'];
		// set the method just for reference
		$_SERVER['REQUEST_METHOD'] = 'POST';
		unset($_SESSION['last_request']);
	}

	// set up variables passed to the system in the request or post
	setup_validate();
}

/**
 * @}
 */

/**
 * Function for checking in libraries are installed, specifically PEAR which likes to use /local/share/php5/
 * @param filename the library filename from the scope of the expected include_path
 * @return the full, real path of the library, or false if it is not found in any include path
 */
function include_path($filename)
{
	// Check for absolute path
	if (realpath($filename) == $filename) {
		return $filename;
	}
	
	// Otherwise, treat as relative path
	$paths = explode(PATH_SEPARATOR, get_include_path());
	foreach ($paths as $path)
	{
		if (substr($path, -1) == DIRECTORY_SEPARATOR)
		{
			$fullpath = $path . $filename;
		}
		else
		{
			$fullpath = $path . DIRECTORY_SEPARATOR . $filename;
		}
		if (file_exists($fullpath))
		{
			return $fullpath;
		}
	}
	
	return false;
}


/**
 * Output a module
 * @ingroup output
 */
function output($request)
{
	$GLOBALS['module'] = $request['module'];

	// output module
	// if the module is disabled, but has no template, call output function for handling disabledness
	// otherwise just show template for disabled modules
	call_user_func_array('output_' . $request['module'], array($request));
	
	// just return because the output function was already called
	if(isset($GLOBALS['modules'][$GLOBALS['module']]['template']) && 
		$GLOBALS['modules'][$GLOBALS['module']]['template'] == false
	)
		return;
	
	// if it is set to a callable function to determine the template, then call that
	elseif(isset($GLOBALS['modules'][$GLOBALS['module']]['template']) &&
		is_callable($GLOBALS['modules'][$GLOBALS['module']]['template'])
	)
		call_user_func_array($GLOBALS['modules'][$GLOBALS['module']]['template'], array($request));
		
	// if it is set to a string then that must be the theme handler for it
	elseif(isset($GLOBALS['modules'][$GLOBALS['module']]['template']) &&
		is_string($GLOBALS['modules'][$GLOBALS['module']]['template'])
	)
		theme($GLOBALS['modules'][$GLOBALS['module']]['template']);
		
	// call the default template based on the module name
	elseif(isset($GLOBALS['modules'][$GLOBALS['module']]['template']) &&
		$GLOBALS['modules'][$GLOBALS['module']]['template'] == true
	)
		theme($GLOBALS['module']);
		
	// if there isn't anything else, call the theme function and maybe it will just display the default blank page
	else
		theme();
	
	// translate the language buffer
	//$lang = validate_language($_REQUEST);
	//if($lang != 'en')
	//{
		// find a place to store this
	//	$_SESSION['translated'] = array_merge($_SESSION['translated'], array_combine(array_keys($GLOBALS['language_buffer']), translate($GLOBALS['language_buffer'], $lang)));
	//}
}

/**
 * Get the url of the server
 */
function selfURL()
{
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")).$s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}

/**
 * check a list of files for access by comparing session keys and user information to each file
 * @param file associative array from a handler::get() call
 * @return the modified list of files with items removed
 * leave keys alone so module can profile more feedback
 */
function checkAccess($file)
{
	// user can access files not handled by user handler
	if(!handles($file['Filepath'], 'db_users'))
		return true;
		
	$tmp_file = str_replace('\\', '/', $file['Filepath']);
	if(setting('admin_alias_enable') == true) $tmp_file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $tmp_file);
	
	// user can always access own files
	if(substr($tmp_file, 0, strlen(setting('local_users'))) == setting('local_users'))
		$user = substr($tmp_file, strlen(setting('local_users')));
	
	if(strpos($user, '/') !== false)
		$user = substr($user, 0, strpos($user, '/'));
	
	if($user == $_SESSION['username'])
		return true;
	
	// the current user can access public files
	if(substr($tmp_file, 0, strlen(setting('local_users') . $user . '/public/')) == setting('local_users') . $user . '/public/')
		return true;
	
	// the current user can access private files if they provided a key
	if(substr($tmp_file, 0, strlen(setting('local_users') . $user . '/private/')) == setting('local_users') . $user . '/private/')
	{
		if(isset($_SESSION['settings']['keys']) && 
		   isset($file['PrivateKey']) &&
		   in_array($file['PrivateKey'], $_SESSION['settings']['keys']) == true)
		{
			return true;
		}
	}
	
	return false;
}

/**
 * tokenize a string, assumed to be a filepath, in various different ways
 * remove useless words like 'and' and 'a' and 'of'
 * @param string the string to tokenize
 * @return An assosiative array with each variation of removed terms
 */
function tokenize($string)
{
	$return = array();
	
	$string = strtolower($string);
	$valid_pieces = array();
	$pieces = split('[^a-zA-Z0-9]', $string);
	$return['All'] = $pieces;
	$return['Unique'] = array_unique($pieces);
	for($i = 0; $i < count($pieces); $i++)
	{
		// remove single characters and common words
		if(strlen($pieces[$i]) > 1 && !in_array(strtolower($pieces[$i]), array('and', 'the', 'of', 'an', 'lp')))
		{
			$valid_pieces[] = $pieces[$i];
		}
	}
	
	$return['Most'] = $valid_pieces;
	
	// remove common edition words
	foreach($valid_pieces as $i => $piece)
	{
		if(in_array(strtolower($valid_pieces[$i]), array('version', 'unknown', 'compilation', 'compilations', 'remastered', 'itunes', 'music')))
		{
			unset($valid_pieces[$i]);
		}
	}
	$valid_pieces = array_values($valid_pieces);
	
	$return['Some'] = $valid_pieces;
	
	// remove common other common words
	foreach($valid_pieces as $i => $piece)
	{
		if(in_array(strtolower($valid_pieces[$i]), array('album', 'artist', 'single', 'clean', 'box', 'boxed', 'set', 'live', 'band', 'hits', 'other', 'disk', 'disc', 'volume', 'retail', 'edition')))
		{
			unset($valid_pieces[$i]);
		}
	}
	$valid_pieces = array_values($valid_pieces);
	
	$return['Few'] = $valid_pieces;
	
	return $return;
}

/**
 * sorting function for terms in a keyword search
 * @param a the first item to compare
 * @param b the second item to compare
 * @return 1 for comes after, -1 for comes before, 0 for equal
 */
function termSort($a, $b)
{
	if(($a[0] == '+' && $a[0] == $b[0]) || ($a[0] == '-' && $a[0] == $b[0]) || ($a[0] != '+' && $a[0] != '-' && $b[0] != '+' && $b[0] != '-'))
	{
		if(strlen($a) > strlen($b))
			return -1;
		elseif(strlen($a) < strlen($b))
			return 1;
		else
			return 0;
	} elseif($a[0] == '+') {
		return -1;
	} elseif($b[0] == '+') {
		return 1;
	} elseif($a[0] == '-') {
		return -1;
	} else {
		return 1;
	}
}

/**
 * parses the path inside of a file, useful to handlers like archive and diskimage
 * @param file The filepath to search for the inside part of
 * @param last_path The part of the path that exists on disk
 * @param inside_path The inner part of the path that exists within the directory
 * return none
 */
function parseInner($file, &$last_path, &$inside_path)
{
	$paths = split(DIRECTORY_SEPARATOR, $file);
	$last_path = '';
	foreach($paths as $i => $tmp_file)
	{
		if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path . $tmp_file)) || $last_path == '')
		{
			$last_path = $last_path . $tmp_file;
			if($last_path == '' || $last_path[strlen($last_path)-1] != '/')
				$last_path .= '/';
		} else {
			if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
				break;
		}
	}
	
	$inside_path = substr($file, strlen($last_path));
	if($last_path[strlen($last_path)-1] == '/') $last_path = substr($last_path, 0, strlen($last_path)-1);
}

/**
 * get all columns from every handlers
 * @return a list of all the columns combined from every handler installed
 */
function getAllColumns()
{
	if(!isset($GLOBALS['handlers']))
		return array();
	$columns = array();
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(setting('database_enable') == false || is_internal($handler) == false)
			$columns = array_merge($columns, array_flip(columns($handler)));
	}
	
	$columns = array_keys($columns);

	return $columns;
}

/**
 * rounds the filesize and adds the extension
 * @param dirsize the number to round
 * @return a rounded number with a GB/MB/KB suffix
 */
function roundFileSize($dirsize)
{
	$dirsize = ( $dirsize < 1024 ) ? ($dirsize . " B") : (( $dirsize < 1048576 ) ? (round($dirsize / 1024, 2) . " KB") : (( $dirsize < 1073741824 ) ? (round($dirsize / 1048576, 2) . " MB") : (( $dirsize < 1099511627776 ) ? (round($dirsize / 1073741824, 2) . " GB") : (round($dirsize / 1099511627776, 2) . " TB") ) ) );
	return $dirsize;
}

/**
 * just a function for getting the names keys used in the db_ids handler
 * this is used is most modules for simplifying database access by looking up the key first
 * @return all the id_ columns from db_ids handler
 */
function getIDKeys()
{
	if(setting('database_enable') == false)
		return array();
	$id_keys = array_flip(columns('ids'));
	unset($id_keys['id']);
	unset($id_keys['Filepath']);
	unset($id_keys['Hex']);
	
	return $id_keys;
}

/**
 * notify of ascii problems when reading data
 * @param str the input string to test for ascii only character codes
 * @return true if there is a match, false if no matches are found
 */
function utf8_is_ascii($str) {
	
	if ( strlen($str) > 0 )
	{
		
		// Search for any bytes which are outside the ASCII range...
		
		return (preg_match('/[^\x00-\x7F]/',$str) !== 1);
	
	}
	
	return false;
	
}

/**
 * get our file types, stuff the website can handle
 * @param file The file to get the type of
 * @return 'FOLDER' if the input file is a directory, 
 * the extension of the file in uppercase format, 
 * 'FILE' if there is no extension
 */
function getFileType($file)
{
	if( file_exists( str_replace('/', DIRECTORY_SEPARATOR, $file) ) )
	{
		if( is_dir($file) )
		{
			return 'FOLDER';
		}
		elseif( is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)) )
		{
			$ext = getExt($file);
			if( $ext == false )
			{
				return 'FILE';
			}
			else
			{
				return strtoupper($ext);
			}
		}
	}
	else
	{
		return false;
	}
}

/**
 * get the file extension
 * @param file The file to get the extention of
 * @return FALSE if there is no extension or anything after the last period in the file name
 */
function getExt($file)
{
	if( is_dir($file) )
	{
		return false;
	}
	else
	{
		$file = basename($file);
		if(strrpos($file, '.') !== false)
		{
			$ext = strrchr($file, '.');
			return strtolower(substr($ext, 1));
		}
		else
		{
			return false;
		}
	}
}

/**
 * get mime type based on file extension
 * @param ext the extension or filename to get the mime type of
 * @return a mime type based on the UNIX mime.types file
 */
function getMime($ext)
{
	if(strpos($ext, '.') !== false)
	{
		$ext = getExt($ext);
	}
	
	if(isset($GLOBALS['ext_to_mime'][$ext]))
	{
		return $GLOBALS['ext_to_mime'][$ext];
	}
	else
	{
		return '';
	}
}

/**
 * get the type which is the first part of a mime based on extension
 * @param filename the file to get the mime type of
 * @return the first part of a mime type such as 'audio' or 'text'
 */
function getExtType($filename)
{
	if(class_exists('MIME_Type'))
		return MIME_Type::getMedia(getMime($filename));
	else
	{
		$mime = getMime($filename);
		return substr($mime, 0, strpos($mime, '/'));
	}
}

/**
 * create the crc32 from a file, this uses a buffer size so it doesn't error out
 * @param filename the file to get the crc hash code of
 * @return the crc code
 */
function crc32_file($filename)
{
    $fp = @fopen($filename, "rb");
    $old_crc=false;

    if ($fp != false) {
        $buffer = '';
       
        while (!feof($fp)) {
            $buffer=fread($fp, setting('buffer_size'));
            $len=strlen($buffer);      
            $t=crc32($buffer);   
       
            if ($old_crc) {
                $crc32=crc32_combine($old_crc, $t, $len);
                $old_crc=$crc32;
            } else {
                $crc32=$old_crc=$t;
            }
        }
        fclose($fp);
    } else {
        print "Cannot open file\n";
    }

    return $crc32;
}

/**
 * a helper function for crc32_file
 * @param crc1 the first part of the crc code being generated
 * @param crc2 the second part of the crc code being generated
 * @param len2 the length of the current crc code
 * @return the new crc1 code
 */
function crc32_combine($crc1, $crc2, $len2)
{
    $odd[0]=0xedb88320;
    $row=1;

    for($n=1;$n<32;$n++) {
        $odd[$n]=$row;
        $row<<=1;
    }

    gf2_matrix_square($even,$odd);
    gf2_matrix_square($odd,$even);

    do {
        /* apply zeros operator for this bit of len2 */
        gf2_matrix_square($even, $odd);

        if ($len2 & 1)
            $crc1=gf2_matrix_times($even, $crc1);

        $len2>>=1;
   
        /* if no more bits set, then done */
        if ($len2==0)
            break;
   
        /* another iteration of the loop with odd and even swapped */
        gf2_matrix_square($odd, $even);
        if ($len2 & 1)
            $crc1=gf2_matrix_times($odd, $crc1);
        $len2>>= 1;
   
    } while ($len2 != 0);

    $crc1 ^= $crc2;
    return $crc1;
}

/**
 * helper function for crc32_combine
 */
function gf2_matrix_square(&$square, &$mat)
{
    for ($n=0;$n<32;$n++) {
        $square[$n]=gf2_matrix_times($mat, $mat[$n]);
    }
}

/**
 * helper function for crc32_combine
 */
function gf2_matrix_times($mat, $vec)
{
    $sum=0;
    $i=0;
    while ($vec) {
        if ($vec & 1) {
            $sum ^= $mat[$i];
        }
        $vec>>= 1;
        $vec &= 0x7fffffff;
        $i++;
    }
    return $sum;
}

/**
 * Converts unix formatted timestamps to DOS timestamps
 * @param unixtime the unix timestamp to convert
 * @return the converted DOS time stamp
 */
function unix2DosTime($unixtime = 0)
{
	$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

	if ($timearray['year'] < 1980) {
		$timearray['year']    = 1980;
		$timearray['mon']     = 1;
		$timearray['mday']    = 1;
		$timearray['hours']   = 0;
		$timearray['minutes'] = 0;
		$timearray['seconds'] = 0;
	} // end if

	return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
			($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
} // end of the 'unix2DosTime()' method

/**
 * kill a process on linux, for some reason closing the streams isn't working
 * @param command the command to be killed
 * @param startpid the estimate PID of the process to kill
 * @param limit the limit for how many process back to kill 
 */
function kill9($command, $startpid, $limit = 2)
{
	$ps = `ps -u www-data --sort=pid -o comm= -o pid=`;
	$ps_lines = explode("\n", $ps);
	
	$pattern = "/(\S{1,})(\s{1,})(\d{1,})/";
	
	foreach($ps_lines as $line)
	{
		if(preg_match($pattern, $line, $matches))
		{
			//this limits us to finding the command within $limit pid's of the parent;
			//eg, if ppid = 245, limit = 3, we won't search past 248
			if($matches[3] > $startpid + $limit)
				break;
	
			//try to match a ps line where the command matches our search
			//at a higher pid than our parent
			if($matches[1] == $command && $matches[3] > $startpid)
			{
				system('/bin/kill -9 ' . $matches[3]);
			}
		}
	}
}

/**
 * Converts PHP errors into PEAR errors
 * @param error_code the PHP code for the error
 * @param error_str the error text
 * @param error_file the file the error occured in
 * @param error_line the line the error was triggered from
 * @return true so the backend error handle knows the error has been processed
 */
function php_to_PEAR_Error($error_code, $error_str, $error_file, $error_line)
{
	if($error_code & E_WARNING || $error_code & E_STRICT || $error_code & E_NOTICE)
	{
		// if verbose is false drop the error
		if(setting_installed() == false)
		{
			$error_code = E_DEBUG|E_WARN|E_USER;
		}
		elseif(setting('verbose') == true)
		{
			$error_code = E_WARN|E_DEBUG;
		}
		else
		{
			$error_code = E_DEBUG;
		}
	}
	else
		$error_code = E_DEBUG;
		
	PEAR::raiseError($error_str, $error_code);
	
	return true;
}

/**
 * The callback function for the PEAR error handler to use
 * @param error the pear error object to add to the error stack
 */
function error_callback($error)
{
	// add special error handling based on the origin of the error
	foreach($error->backtrace as $i => $stack)
	{
		if($stack['function'] == 'raiseError')
			break;
	}
	$i++;
	if(isset($error->backtrace[$i]['file']))
	{
		if(dirname($error->backtrace[$i]['file']) == 'modules' && basename($error->backtrace[$i]['file']) == 'template.php')
		{
			for($i = $i; $i < count($error->backtrace); $i++)
			{
				if(dirname($error->backtrace[$i]['file']) != 'modules' || basename($error->backtrace[$i]['file']) != 'template.php')
					break;
			}
		}
	
		$error->message .= ' in ' . $error->backtrace[$i]['file'] . ' on line ' . $error->backtrace[$i]['line'];
	}
	
	if($error->code & E_USER)
		$GLOBALS['user_errors'][] = $error;
	if($error->code & E_WARN)
		$GLOBALS['warn_errors'][] = $error;
	if($error->code & E_NOTE)
		$GLOBALS['note_errors'][] = $error;
	if($error->code & E_DEBUG || setting('verbose') == true)
		$GLOBALS['debug_errors'][] = $error;
	
	$GLOBALS['errors'][] = $error;
}


/**
 * stolen from PEAR, 
 * DSN parser for use internally
 * @return an associative array of parsed DSN information
 */
function parseDSN($dsn)
{
	$parsed = array();
	if (is_array($dsn)) {
		$dsn = array_merge($parsed, $dsn);
		if (!$dsn['dbsyntax']) {
			$dsn['dbsyntax'] = $dsn['phptype'];
		}
		return $dsn;
	}

	// Find phptype and dbsyntax
	if (($pos = strpos($dsn, '://')) !== false) {
		$str = substr($dsn, 0, $pos);
		$dsn = substr($dsn, $pos + 3);
	} else {
		$str = $dsn;
		$dsn = null;
	}

	// Get phptype and dbsyntax
	// $str => phptype(dbsyntax)
	if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
		$parsed['phptype']  = $arr[1];
		$parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
	} else {
		$parsed['phptype']  = $str;
		$parsed['dbsyntax'] = $str;
	}

	if (!count($dsn)) {
		return $parsed;
	}

	// Get (if found): username and password
	// $dsn => username:password@protocol+hostspec/database
	if (($at = strrpos($dsn,'@')) !== false) {
		$str = substr($dsn, 0, $at);
		$dsn = substr($dsn, $at + 1);
		if (($pos = strpos($str, ':')) !== false) {
			$parsed['username'] = rawurldecode(substr($str, 0, $pos));
			$parsed['password'] = rawurldecode(substr($str, $pos + 1));
		} else {
			$parsed['username'] = rawurldecode($str);
		}
	}

	// Find protocol and hostspec

	// $dsn => proto(proto_opts)/database
	if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
		$proto       = $match[1];
		$proto_opts  = $match[2] ? $match[2] : false;
		$dsn         = $match[3];

	// $dsn => protocol+hostspec/database (old format)
	} else {
		if (strpos($dsn, '+') !== false) {
			list($proto, $dsn) = explode('+', $dsn, 2);
		}
		if (   strpos($dsn, '//') === 0
			&& strpos($dsn, '/', 2) !== false
			&& $parsed['phptype'] == 'oci8'
		) {
			//oracle's "Easy Connect" syntax:
			//"username/password@[//]host[:port][/service_name]"
			//e.g. "scott/tiger@//mymachine:1521/oracle"
			$proto_opts = $dsn;
			$dsn = substr($proto_opts, strrpos($proto_opts, '/') + 1);
		} elseif (strpos($dsn, '/') !== false) {
			list($proto_opts, $dsn) = explode('/', $dsn, 2);
		} else {
			$proto_opts = $dsn;
			$dsn = null;
		}
	}

	// process the different protocol options
	$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
	$proto_opts = rawurldecode($proto_opts);
	if (strpos($proto_opts, ':') !== false) {
		list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
	}
	if ($parsed['protocol'] == 'tcp') {
		$parsed['hostspec'] = $proto_opts;
	} elseif ($parsed['protocol'] == 'unix') {
		$parsed['socket'] = $proto_opts;
	}

	// Get dabase if any
	// $dsn => database
	if ($dsn) {
		// /database
		if (($pos = strpos($dsn, '?')) === false) {
			$parsed['database'] = $dsn;
		// /database?param1=value1&param2=value2
		} else {
			$parsed['database'] = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 1);
			if (strpos($dsn, '&') !== false) {
				$opts = explode('&', $dsn);
			} else { // database?param1=value1
				$opts = array($dsn);
			}
			foreach ($opts as $opt) {
				list($key, $value) = explode('=', $opt);
				if (!isset($parsed[$key])) {
					// don't allow params overwrite
					$parsed[$key] = rawurldecode($value);
				}
			}
		}
	}

	return $parsed;
}

/** 
 * parse mime types from a mime.types file, 
 * This functionality sucks less then the PEAR mime type library
 * @ingroup setup
 */
function setup_mime()
{
	// this will load the mime-types from a linux dist mime.types file stored in includes
	// this will organize the types for easy lookup
	if(file_exists(setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'mime.types'))
	{
		$handle = fopen(setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'mime.types', 'r');
		$mime_text = fread($handle, filesize(setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'mime.types'));
		fclose($handle);
		
		$mimes = split("\n", $mime_text);
		
		$ext_to_mime = array();
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
				}
			}
		}
		
		
		// set global variables
		$GLOBALS['ext_to_mime'] = $ext_to_mime;
	}
}
