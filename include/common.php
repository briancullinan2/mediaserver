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
define('VERSION', 			     '0.60.5');
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

//if(realpath('/') == '/')
//	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.nix.php';
//else
/** require the settings */
if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.ini'))
{
	if($GLOBALS['settings'] = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.ini'))
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
	define('NOT_INSTALLED', true);
	if(!isset($_REQUEST['module'])) $_REQUEST['module'] = 'admin_install';
}

if(!isset($GLOBALS['settings']['local_root']))
{
	// spoof local root
	$GLOBALS['settings']['local_root'] = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
}
if(!isset($GLOBALS['settings']['use_database']))
{
	// decide for them
	$GLOBALS['settings']['use_database'] = false;
}

/** require compatibility */
include_once $GLOBALS['settings']['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'compatibility.php';

/** require core functionality */
include_once $GLOBALS['settings']['local_root'] . 'modules' . DIRECTORY_SEPARATOR . 'core.php';

/** require pear for error handling */
include_once 'PEAR.php';
/** Set the error handler to use our custom function for storing errors */
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
//set_error_handler('php_to_PEAR_Error');
/** stores a list of all errors */
$GLOBALS['errors'] = array();
/** stores a list of all user errors */
$GLOBALS['user_errors'] = array();
/** stores a list of all warnings */
$GLOBALS['warn_errors'] = array();
/** stores a list of all debug information */
$GLOBALS['debug_errors'] = array();

//session_cache_limiter('public');
/** always begin the session */
session_start();

/** Remove annoying POST error message with the page is refreshed 
 * @{
 */
if(isset($_POST) && count($_POST) > 0)
{
	$_SESSION['last_request'] = $_REQUEST;
	goto($_SERVER['REQUEST_URI']);
}
if(isset($_SESSION['last_request']))
{
	$_REQUEST = $_SESSION['last_request'];
	unset($_SESSION['last_request']);
}
/**
 * @}
 */

include_once 'MIME' . DIRECTORY_SEPARATOR . 'Type.php';

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

	// include the database wrapper class so it can be used by any page
	if( $GLOBALS['settings']['use_database'] ) include_once $GLOBALS['settings']['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'database.php';
		
	// set up database to be used everywhere
	if($GLOBALS['settings']['use_database'])
		$GLOBALS['database'] = new database($GLOBALS['settings']['db_connect']);
	else
		$GLOBALS['database'] = NULL;
	
	// set up mime types because PEAR MIME_Type is retarded
	setup_mime();
	
	// set up the list of handlers
	setup_handlers();
	
	// set up a list of modules available to the system
	setup_register();
	
	// set up the settings to use from here on out
	setup_settings();
	
	// set up list of tables
	setup_tables();
	
	// set up aliases for path replacement
	setup_aliases();
	
	// set up the template system for outputting
	setup_template();

	// set up variables passed to the system in the request or post
	setup_validate();
	
	// set up users for permission based access
	setup_user();

}

/**
 * Set up the list of aliases from the database
 */
function setup_aliases()
{
	// get the aliases to use to replace parts of the filepath
	$GLOBALS['paths_regexp'] = array();
	$GLOBALS['alias_regexp'] = array();
	$GLOBALS['paths'] = array();
	$GLOBALS['alias'] = array();
	if($GLOBALS['settings']['use_database'] && $GLOBALS['settings']['use_alias'] == true)
	{
		$aliases = $GLOBALS['database']->query(array('SELECT' => 'alias'), false);
		
		if($aliases !== false)
		{
			foreach($aliases as $key => $alias_props)
			{
				$GLOBALS['paths_regexp'][] = $alias_props['Paths_regexp'];
				$GLOBALS['alias_regexp'][] = $alias_props['Alias_regexp'];
				$GLOBALS['paths'][] = $alias_props['Filepath'];
				$GLOBALS['alias'][] = $alias_props['Alias'];
			}
		}
	}
	
}

/**
 * Scan handlers directory and load all of the handlers that handle files
 */
function setup_handlers()
{
	
	// include the handlers
	$tmp_handlers = array();
	if ($dh = @opendir($GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR))
	{
		while (($file = readdir($dh)) !== false)
		{
			// filter out only the handlers for our $GLOBALS['settings']['use_database'] setting
			if ($file[0] != '.' && !is_dir($GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR . $file))
			{
				$class_name = substr($file, 0, strrpos($file, '.'));
				if(!defined(strtoupper($class_name) . '_ENABLED') || constant(strtoupper($class_name) . '_ENABLED') != false)
				{
					// include all the handlers
					include_once $GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR . $file;
					
					// only use the handler if it is properly defined
					if(class_exists($class_name))
					{
						if(substr($file, 0, 3) == ($GLOBALS['settings']['use_database']?'db_':'fs_'))
							$tmp_handlers[] = $class_name;
					}
				}
			}
		}
		closedir($dh);
	}
	
	$error_count = 0;
	$new_handlers = array();
	
	// reorganize handlers to reflect heirarchy
	while(count($tmp_handlers) > 0 && $error_count < 1000)
	{
		foreach($tmp_handlers as $i => $handler)
		{
			$tmp_override = get_parent_class($handler);
			if(in_array($tmp_override, $new_handlers) || $tmp_override == '')
			{
				$new_handlers[] = $handler;
				unset($tmp_handlers[$i]);
			}
		}
		$error_count++;
	}
	$GLOBALS['handlers'] = $new_handlers;
}


/**
 * Create a GLOBAL list of tables used by all the handlers
 */
function setup_tables()
{
	// loop through each handler and compile a list of databases
	$GLOBALS['tables'] = array();
	if($GLOBALS['settings']['use_database'])
	{
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			if(defined($handler . '::DATABASE'))
				$GLOBALS['tables'][] = constant($handler . '::DATABASE');
		}
		$GLOBALS['tables'] = array_values(array_unique($GLOBALS['tables']));
		
		// get watched and ignored directories because they are used a lot
		$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count);
		$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count);
		// always add user local to watch list
		$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', LOCAL_USERS));
	}
}

/**
 * @}
 */

/**
 * Output a module
 * @ingroup output
 */
function output($request)
{
	$GLOBALS['module'] = $request['module'];

	// output module
	call_user_func_array('output_' . $request['module'], array($request));
	
	// only display a template for the current module if there is one
	if(!isset($GLOBALS['modules'][$GLOBALS['module']]['notemplate']) || 
			$GLOBALS['modules'][$GLOBALS['module']]['notemplate'] == false
		)
	{
		theme();
		
		theme('errors');
	}
}

/**
 * Check if the specified class is just a wrapper for some parent database
 * @param handler is the catagory or handler to check
 * @return true or false if the class is a wrapper
 */
function is_wrapper($handler)
{
	// fs_ handlers are never wrappers
	if($GLOBALS['settings']['use_database'] == false)
		return false;
	if($handler == 'db_file')
		return false;
	return (constant($handler . '::DATABASE') == constant(get_parent_class($handler) . '::DATABASE'));
}

/**
 * Check if a handler handles a certain type of files
 * this is a useful call for templates to use because it provides short syntax
 * @param file The file to test if it is handled
 * @param handler The handler to check if it handles the specified file
 * @return true if the specified handler handles the specified file, false if the handler does not handle that file
 */
function handles($file, $handler)
{
	if(class_exists(($GLOBALS['settings']['use_database']?'db_':'fs_') . $handler))
	{
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		return call_user_func(($GLOBALS['settings']['use_database']?'db_':'fs_') . $handler . '::handles', $file);
	}
	return false;
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
	if(!db_users::handles($file['Filepath']))
		return true;
		
	$tmp_file = str_replace('\\', '/', $file['Filepath']);
	if(USE_ALIAS == true) $tmp_file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $tmp_file);
	
	// user can always access own files
	if(substr($tmp_file, 0, strlen(LOCAL_USERS)) == LOCAL_USERS)
		$user = substr($tmp_file, strlen(LOCAL_USERS));
	
	if(strpos($user, '/') !== false)
		$user = substr($user, 0, strpos($user, '/'));
	
	if($user == $_SESSION['username'])
		return true;
	
	// the current user can access public files
	if(substr($tmp_file, 0, strlen(LOCAL_USERS . $user . '/public/')) == LOCAL_USERS . $user . '/public/')
		return true;
	
	// the current user can access private files if they provided a key
	if(substr($tmp_file, 0, strlen(LOCAL_USERS . $user . '/private/')) == LOCAL_USERS . $user . '/private/')
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
	$columns = array();
	foreach($GLOBALS['handlers'] as $i => $handler)
	{
		if($GLOBALS['settings']['use_database'] == false || constant($handler . '::INTERNAL') == false)
			$columns = array_merge($columns, array_flip(call_user_func($handler . '::columns')));
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
	$id_keys = array_flip(db_ids::columns());
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
	return MIME_Type::getMedia(getMime($filename));
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
            $buffer=fread($fp, $GLOBALS['settings']['buffer_size']);
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
	if($error_code & E_WARNING || $error_code & E_NOTICE)
		$error_code = E_WARN;
		
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
	
	if($error->code & E_DEBUG)
		$GLOBALS['debug_errors'][] = $error;
	if($error->code & E_USER)
		$GLOBALS['user_errors'][] = $error;
	if($error->code & E_WARN)
		$GLOBALS['warn_errors'][] = $error;
	
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
	if(file_exists($GLOBALS['settings']['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'mime.types'))
	{
		$handle = fopen($GLOBALS['settings']['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'mime.types', 'r');
		$mime_text = fread($handle, filesize($GLOBALS['settings']['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'mime.types'));
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
