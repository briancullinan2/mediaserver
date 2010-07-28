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
 * @defgroup setup Setup Functions
 * All functions that are used to set up necissary parts of the site
 * These functions usually specify global variables
 * @{
 */

/**
 * @}
 */
 
function disable_module($module)
{
	if(dependency($module) == false)
	{
		$GLOBALS['settings'][$module . '_enable'] = false;
		// this prevents us from disabling required modules on accident
		$GLOBALS['settings'][$module . '_enable'] = setting($module . '_enable');
	}
}

function trigger_key($trigger, $callback, $input, $key)
{
	$args = func_get_args();
	
	if(isset($GLOBALS['triggers'][$trigger][$key]) && count($GLOBALS['triggers'][$trigger][$key]) > 0)
	{
		foreach($GLOBALS['triggers'][$trigger][$key] as $module => $function)
		{
			unset($result);
			
			if(is_callable($function))
				$result = call_user_func_array($function, array($input, $key));
			else
				raise_error('Trigger \'' . $trigger . '\' functionality specified in \'' . $module . '\' but ' . $function . ' in not callable!', E_DEBUG);
			
			if(is_callable($callback))
				call_user_func_array($callback, array($module, $result, $args));
			elseif(isset($result))
			{
				$return = $result;
				// also set it here so it can be passed to next validator
				$input[$key] = $result;
			}
		}
		
		if(isset($return))
			return $return;
	}
	
	return;
}

function trigger($trigger, $callback = NULL, $input = array())
{
	$args = func_get_args();

	if(isset($GLOBALS['triggers'][$trigger][NULL]))
	{
		// call triggers set to always go off
		foreach($GLOBALS['triggers'][$trigger][NULL] as $module => $function)
		{
			unset($result);
			
			// numeric indices on this level indicates always call
			if(is_callable($function))
			{
				$result = call_user_func_array($function, array($input));
				
				if(is_callable($callback))
					call_user_func_array($callback, array($module, $result, $args));
				elseif(isset($result))
					$input[$key] = $result;
			}
			else
				raise_error('Trigger \'' . $trigger . '\' function specified by \'' . $module . '\' but it is not callable.', E_DEBUG);
		}
	}
	
	// call triggers based on input
	foreach($input as $key => $value)
	{
		$input[$key] = trigger_key($trigger, $callback, $input, $key);
	}
	
	return $input;
}

function register_trigger($trigger, $config, $module)
{
	// reorganize alter query triggers
	if(isset($config[$trigger]))
	{
		if(is_array($config[$trigger]))
		{
			foreach($config[$trigger] as $i => $var)
			{
				if(is_numeric($i))
					$GLOBALS['triggers'][$trigger][$var][$module] = $trigger . '_' . $module;
				elseif(is_callable($var))
					$GLOBALS['triggers'][$trigger][$i][$module] = $var;
			}
		}
		elseif(is_bool($config[$trigger]))
		{
			$GLOBALS['triggers'][$trigger][NULL][$module] = $trigger . '_' . $module;
		}
		elseif(is_callable($config[$trigger]))
		{
			$GLOBALS['triggers'][$trigger][NULL][$module] = $config[$trigger];
		}
	}
	else
		raise_error('Trigger not set in config for \'' . $module . '\'.', E_VERBOSE);
}

function invoke_module($method, $module)
{
	$args = func_get_args();
	unset($args[1]);
	unset($args[0]);
	
	if(function_exists($method . '_' . $module))
	{
		return call_user_func_array($method . '_' . $module, $args);
	}
	else
		raise_error('Invoke \'' . $method . '\' called on \'' . $module . '\' but dependencies not met or function does not exist.', E_VERBOSE);
}

/**
 * Function for invoking an API call on all modules and returning the result
 * @param method Method to call on all modules
 * @param list_return_values list all the return values from each module separately
 * @return true if not listing return values and it succeeded, returns false if any module fails, returns associative array if listing each value
 */
function invoke_all($method)
{
	$args = func_get_args();
	
	// remove method name
	unset($args[0]);
	
	raise_error('Modules invoked with \'' . $method . '\'.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if((dependency($module) || in_array($module, get_required_modules())) && function_exists($method . '_' . $module))
		{
			$result = call_user_func_array($method . '_' . $module, $args);
		}
	}
}

function invoke_all_callback($method, $callback)
{
	$args = func_get_args();
	
	// remove method name
	unset($args[1]);
	unset($args[0]);
	
	raise_error('Modules invoked with \'' . $method . '\' and a callback function supplied.', E_VERBOSE);
	
	// loop through modules
	foreach($GLOBALS['modules'] as $module => $config)
	{
		// do not call set up if dependencies are not met, this will force strict use of modules functionality
		// set up the modules in the right order
		if((dependency($module) || in_array($module, get_required_modules())) && function_exists($method . '_' . $module))
		{
			$result = call_user_func_array($method . '_' . $module, $args);
			
			if(is_callable($callback))
				call_user_func_array($callback, array($module, $result, $args));
		}
	}
}

/**
 * Function for returning all the modules that match certain criteria
 */
function get_modules($package = NULL)
{
	$modules = array();
	
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if($package === NULL || $config['package'] == $package)
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for getting all the modules that implement a specified API function
 */
function get_modules_implements($method)
{
	$modules = array();
	
	// check if function exists
	foreach($GLOBALS['modules'] as $module => $config)
	{
		if(function_exists($method . '_' . $module))
			$modules[$module] = $config;
	}
	
	return $modules;
}

/**
 * Function for matching properties on a module using regular expression and the serialized function
 */
function get_modules_match($expression)
{
	$modules = array();
	
	// if it is not an object or a string, then serialize it in order to match it against modules
	if(!is_string($expression))
		$expression = '/' . addslashes(serialize($expression)) . '/i';
		
	// make sure it is valid regular expression
	$expression = generic_validate_regexp(array('package' => $expression), 'package');
	
	// if it is valid
	if(isset($expression))
	{
		// loop through all the modules and match expression
		foreach($GLOBALS['modules'] as $module => $config)
		{
			if(preg_match($expression, serialize($config)) != 0)
				$modules[$module] = $config;
		}
	}
	
	return $modules;
}

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
	
	$session_user = session('users');
	if($user == $session_user['Username'])
		return true;
	
	// the current user can access public files
	if(substr($tmp_file, 0, strlen(setting('local_users') . $user . '/public/')) == setting('local_users') . $user . '/public/')
		return true;
	
	// the current user can access private files if they provided a key
	if(substr($tmp_file, 0, strlen(setting('local_users') . $user . '/private/')) == setting('local_users') . $user . '/private/')
	{
		if(isset($session_user['Settings']['keys']) && 
		   isset($file['PrivateKey']) &&
		   in_array($file['PrivateKey'], $session_user['Settings']['keys']) == true)
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
	foreach($GLOBALS['modules'] as $handler => $config)
	{
		if(setting('database_enable') == false || !is_internal($handler))
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
	$error_code = E_DEBUG;

	raise_error('PHP ERROR: ' . $error_str, $error_code);

	return (setting('verbose') != 2);
}

function raise_error($str, $code)
{
	$error = new StdClass;
	$error->code = $code;
	$error->message = $str;
	$error->backtrace = debug_backtrace();
	
	error_callback($error);
}

/**
 * The callback function for the PEAR error handler to use
 * @param error the pear error object to add to the error stack
 */
function error_callback($error)
{
	if(count($GLOBALS['errors']) > 200)
		return;

	if($error->code & E_USER)
		$GLOBALS['user_errors'][] = $error->message;
	if($error->code & E_WARN)
		$GLOBALS['warn_errors'][] = $error->message;
	if($error->code & E_NOTE)
		$GLOBALS['note_errors'][] = $error->message;
	if($error->code & E_DEBUG || ($error->code & E_VERBOSE && setting('verbose') === 2))
	{
		// add special error handling based on the origin of the error
		foreach($error->backtrace as $i => $stack)
		{
			if($stack['function'] == 'raise_error')
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
		
		// only show verbose errors if it is really verbose!
		if($error->code & E_DEBUG || setting('verbose'))
			$GLOBALS['debug_errors'][] = $error;
	}
	
	$GLOBALS['errors'][] = $error->message;
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
	if(file_exists(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types'))
	{
		$handle = fopen(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types', 'r');
		$mime_text = fread($handle, filesize(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types'));
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

/**
 * Fetch remote pages using curl
 * @param url the url of the page to fetch
 * @param post if set perform a post request
 * @param cookies send cookies along with the request, also stores the cookies returned
 * @return an associative array consisting of content, and headers
 */
function fetch($url, $post = array(), $headers = array(), $cookies = array())
{
	if(function_exists('curl_init'))
	{
		$ch = curl_init($url);
		
		// setup basics
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// setup timeout
		if(isset($headers['timeout']))
		{
			curl_setopt($ch, CURLOPT_TIMEOUT, $headers['timeout']);
			unset($headers['timeout']);
		}
		else
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		// setup user agent
		if(isset($headers['agent']))
		{
			curl_setopt($ch, CURLOPT_USERAGENT, $headers['agent']);
			unset($headers['agent']);
		}
		else
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1');
			
		// setup referer
		if(isset($headers['referer']))
		{
			curl_setopt($ch, CURLOPT_REFERER, $headers['referer']);
			unset($headers['referer']);
		}
		
		// curl ssl
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		// setup headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		// setup post
		if(count($post) > 0)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);    
		}
		
		$cookie = '';
		foreach ($cookies as $key => $value)
		{
			$cookie .= $key . '=' . $value . '; ';
		}
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		// use a cookie file just because of follow_location
		$cookie_file = tempnam('dummy', 'cookie_');
		if($cookie_file !== false)
		{
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		}
	
		// execute
		$content = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 	
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);	
		$headers_raw = split("\n", substr($content, 0, $header_size));
		$content = substr($content, $header_size);
		curl_close($ch);
		
		// process cookies
		$headers = array();
		foreach($headers_raw as $i => $header)
		{
			// parse header
			if(strpos($header, ':') !== false)
			{
				$headers[substr($header, 0, strpos($header, ':'))] = trim(substr($header, strpos($header, ':') + 1));
			}
			
			// parse cookie
			if(!strncmp($header, "Set-Cookie:", 11))
			{
				$cookiestr = trim(substr($header, 11, -1));
				$cookie = explode(';', $cookiestr);
				$cookie = explode('=', $cookie[0]);
				$cookiename = trim(array_shift($cookie)); 
				$cookies[$cookiename] = trim(implode('=', $cookie));
			}
		}
		
		// delete cookie jar because they are saved and return in an array instead
		if($cookie_file !== false) unlink($cookie_file);
		
		return array('headers' => $headers, 'content' => $content, 'cookies' => $cookies, 'status' => $status);
	}
	else
	{
		raise_error('cUrl not installed!', E_DEBUG);
		
		return array('headers' => array(), 'content' => '', 'cookies' => array(), 'status' => 0);
	}
}

/**
 * Helper function takes a url and a fragment and gets the full valid url
 */
function get_full_url($url, $fragment)
{
	if($address = generic_validate_hostname(array('address' => $fragment), 'address'))
		// already is valid
		return $fragment;
	else
	{
		// check if url is valid
		$address = generic_validate_hostname(array('address' => $url), 'address');
		
		// make sure there is a slash on the end
		if(substr($address, -1) != '/') $address .= '/';
		
		// remove extra slashes from beginning of fragment
		if(substr($fragment, 0, 1) == '/') $fragment = substr($fragment, 1);
		
		// get path to prepend to fragment
		if($path = generic_validate_urlpath(array('path' => $url), 'path'))
		{
			$path = dirname($path);
			if(substr($path, 0, 1) == '/') $path = substr($path, 1);
			
			return $address . $path . (($path != '')?'/':'') . $fragment;
		}
		else
		{
			return $address . $fragment;
		}
		
	}
}

function get_login_form($content, $userfield = 'username')
{
	// get forms
	if(preg_match_all('/<form[^>]*?action="([^"]*?)"[^>]*?>([\s\S]*?)<\/form>/i', $content, $forms) > 0)
	{
		// match input fields
		foreach($forms[0] as $i => $form)
		{
			// extract form elements
			if(preg_match_all('/<input[^>]*?name=(["\'])(?P<name>[^\1>]*?)\1[^>]*?>/i', $forms[2][$i], $post_vars) > 0)
			{
				$post = array_fill_keys($post_vars['name'], '');
				$count = preg_match_all('/<input[^>]*?value=(["\'])(?P<value>[^\1>]*?)\1[^>]*?name=(["\'])(?P<name>[^\3>]*?)\3[^>]*?>/i', $forms[2][$i], $post_vars);
				if($count > 0)
					$post = array_merge($post, array_combine($post_vars['name'], $post_vars['value']));
				$count = preg_match_all('/<input[^>]*?name=(["\'])(?P<name>[^\1>]*?)\1[^>]*?value=(["\'])(?P<value>[^\3>]*?)\3[^>]*?>/i', $forms[2][$i], $post_vars);
				if($count > 0)
					$post = array_merge($post, array_combine($post_vars['name'], $post_vars['value']));
					
				// use form with userfield in the field list
				if(in_array($userfield, array_keys($post)))
				{
		
					return array(escape_urlquery(htmlspecialchars_decode($forms[1][$i])), $post);
				}
			}
		}
	}
}


function escape_urlquery($request)
{
	if(strpos($request, '?') !== false)
	{
		$host = substr($request, 0, strpos($request, '?') + 1);
		
		$new_query = '';
		
		// split up the query string by amersands
		$arr = explode('&', substr($request, strpos($request, '?') + 1));
		if(count($arr) == 1 && $arr[0] == '')
			$arr = array();
		
		// loop through all the query string and generate our new request array
		foreach($arr as $i => $value)
		{
			// split each part of the query string into name value pairs
			$x = explode('=', $value);
			
			// set each part of the query string in our new request array
			$new_query .= (($new_query != '')?'&':'') . $x[0] . '=' . urlencode(isset($x[1])?$x[1]:'');
		}
		
		return $host . $new_query;
	}
	else
		return $request;
}