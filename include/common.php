<?php
define('DEBUG_PRIV', 				0);

// define some error codes so we know which errors to print to the user and which to print to debug
define('E_DEBUG',					1);
define('E_USER',					2);
define('E_WARN',					4);
define('E_FATAL',					8);

// the most basic functions used a lot
// things to consider:
// get the extension for a file using getExt
// get the file mime type

//session_cache_limiter('public');
session_start();
	
if(isset($_POST) && count($_POST) > 0)
{
	$_SESSION['last_request'] = $_REQUEST;
	header('Location: ' . $_SERVER['REQUEST_URI']);
	exit;
}
if(isset($_SESSION['last_request']))
{
	$_REQUEST = $_SESSION['last_request'];
	unset($_SESSION['last_request']);
}

// set version for stuff to reference
define('VERSION', 			     '0.50.0');
define('VERSION_NAME', 			'Goliath');

// require compatibility
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'compatibility.php';


// require the settings
//if(realpath('/') == '/')
//	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.nix.php';
//else
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.php';
	
// require pear for error handling
require_once 'PEAR.php';
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
$GLOBALS['errors'] = array();
$GLOBALS['user_errors'] = array();
$GLOBALS['debug_errors'] = array();

require_once 'MIME' . DIRECTORY_SEPARATOR . 'Type.php';
require_once 'MIME' . DIRECTORY_SEPARATOR . 'Type' . DIRECTORY_SEPARATOR . 'Extension.php';
$GLOBALS['mte'] = new MIME_Type_Extension();


// classes that this function uses to set up stuff should use the $no_setup = true option
setup();

function setup()
{
	// this is where most of the initialization occurs, some global variables are set up for other pages and modules to use
	//  first the variables are parsed out of the path, just incase mod_rewrite isn't enabled
	//  in order of importance, the database is set up, the modules are loaded, the aliases and watch list are loaded, the template system is loaded

	// include the database wrapper class so it can be used by any page
	if( USE_DATABASE ) require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'database.php';
		
	// set up database to be used everywhere
	if(USE_DATABASE)
		$GLOBALS['database'] = new database(DB_CONNECT);
	else
		$GLOBALS['database'] = NULL;
		
	// set up the list of modules
	setupModules();
	
	// set up a list of plugins available to the system
	setupPlugins();
	
	// set up list of tables
	setupTables();
	
	// set up aliases for path replacement
	setupAliases();
	
	// set up the template system for outputting
	setupTemplate();

	// set up variables passed to the system in the request or post
	setupInputVars();
	
	// set up users for permission based access
	setupUsers();

}

function setupPlugins()
{
	$GLOBALS['plugins'] = array('index' => array(
		'name' => 'index',
		'description' => 'Show index files of templates.',
		'privilage' => 1,
		'path' => LOCAL_ROOT . 'index.php'
		)
	);
	$GLOBALS['triggers'] = array('session' => array(), 'settings' => array());
	
	// read plugin list and create a list of available plugins
	$files = fs_file::get(array('dir' => LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR, 'limit' => 32000), $count, true);
	if(is_array($files))
	{
		foreach($files as $i => $file)
		{
			if(is_file($file['Filepath']))
			{
				include_once $file['Filepath'];
				
				$plugin = substr($file['Filename'], 0, strpos($file['Filename'], '.php'));
				
				if(function_exists('register_' . $plugin))
					$GLOBALS['plugins'][$plugin] = call_user_func_array('register_' . $plugin, array());
				
				// reorganize the session triggers for easy access
				if(isset($GLOBALS['plugins'][$plugin]['session']))
				{
					foreach($GLOBALS['plugins'][$plugin]['session'] as $i => $var)
					{
						$GLOBALS['triggers']['session'][$var][] = $plugin;
					}
				}
			}
		}
	}
	
	include_once LOCAL_ROOT . 'admin' . DIRECTORY_SEPARATOR . 'watch.php';
	$GLOBALS['plugins']['watch'] = call_user_func_array('register_watch', array());
	
}

function setupTemplate()
{
	// load templating system but only if we are using templates
	if(defined('LOCAL_BASE'))
	{
		require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'Smarty.class.php';
	
		// get the list of templates
		$GLOBALS['templates'] = array();
		$files = fs_file::get(array('dir' => LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR, 'limit' => 32000), $count, true);
		if(is_array($files))
		{
			foreach($files as $i => $temp_file)
			{
				if(is_dir($temp_file['Filepath']) && is_file($temp_file['Filepath'] . 'config.php'))
					$GLOBALS['templates'][] = $temp_file['Filename'];
			}
		}
		
		$_REQUEST['template'] = validate_template($_REQUEST, isset($_SESSION['template'])?$_SESSION['template']:'');
		
		// don't use a template if they comment out this define, this enables the tiny remote version
		if(!defined('LOCAL_TEMPLATE'))
		{
			define('LOCAL_TEMPLATE',            					 'templates' . DIRECTORY_SEPARATOR . $_REQUEST['template'] . DIRECTORY_SEPARATOR);
		}
		
		// set the HTML_TEMPLATE for templates to refer to their own directory to provide resources
		define('HTML_TEMPLATE', str_replace(DIRECTORY_SEPARATOR, '/', LOCAL_TEMPLATE));
		
		// include template constants
		require_once LOCAL_ROOT . LOCAL_BASE . 'config.php';
		
		if(file_exists(LOCAL_ROOT . LOCAL_TEMPLATE . 'config.php'))
			require_once LOCAL_ROOT . LOCAL_TEMPLATE . 'config.php';
			
		// start smarty global for plugins to use
		$GLOBALS['smarty'] = new Smarty();
		$GLOBALS['smarty']->compile_dir = LOCAL_ROOT . 'templates_c' . DIRECTORY_SEPARATOR;
		$GLOBALS['smarty']->compile_check = true;
		$GLOBALS['smarty']->debugging = false;
		$GLOBALS['smarty']->caching = false;
		$GLOBALS['smarty']->force_compile = true;
		
		// assign some shared variables
		register_output_vars('tables', $GLOBALS['tables']);
		register_output_vars('plugins', $GLOBALS['plugins']);
		register_output_vars('modules', $GLOBALS['modules']);
		register_output_vars('templates', $GLOBALS['templates']);
		register_output_vars('columns', getAllColumns());
		
	}
}

function setupUsers()
{
	// set up user settings
	if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] == false)
	{
		// check if user is logged in
		if( isset($_SESSION['login']['username']) && isset($_SESSION['login']['password']) )
		{
			// lookup username in table
			$db_user = $GLOBALS['database']->query(array(
					'SELECT' => 'users',
					'WHERE' => 'Username = "' . addslashes($_SESSION['login']['username']) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($db_user) > 0 )
			{
				if($_SESSION['login']['password'] == $db_user[0]['Password'])
				{
					$_SESSION['username'] = $_SESSION['login']['username'];
					
					// set up user information in session
					$_SESSION['loggedin'] = true;
					
					// the security level is the most important property
					$_SESSION['privilage'] = $db_user[0]['Privilage'];
					
					// the settings are also very important
					$_SESSION['settings'] = unserialize($db_user[0]['Settings']);
					
					// just incase a template wants to access the rest of the information; include the user
					unset($db_user[0]['Password']);
					unset($db_user[0]['Settings']);
					unset($db_user[0]['Privilage']);
					
					$_SESSION['user'] = $db_user[0];
				}
				else
				{
					PEAR::raiseError('Invalid password.', E_USER);
				}
			}
			else
			{
				PEAR::raiseError('Invalid username.', E_USER);
			}
		}
		// use guest information
		elseif(USE_DATABASE == true)
		{
			$_SESSION['loggedin'] = false;
			
			$db_user = $GLOBALS['database']->query(array(
					'SELECT' => 'users',
					'WHERE' => 'id = -2',
					'LIMIT' => 1
				)
			, false);
			
			if(is_array($db_user) && count($db_user) > 0)
			{
				$_SESSION['username'] = $db_user[0]['Username'];
		
				// the security level is the most important property
				$_SESSION['privilage'] = $db_user[0]['Privilage'];
				
				// the settings are also very important
				$_SESSION['settings'] = unserialize($db_user[0]['Settings']);
				//$_SESSION['settings']['keys'] = array('5a277c44344eaf04e1d92085eabfda02');
				
				// just incase a template wants to access the rest of the information; include the user
				unset($db_user[0]['Password']);
				unset($db_user[0]['Settings']);
				unset($db_user[0]['Privilage']);
				
				$_SESSION['user'] = $db_user[0];
			}
		}
		else
		{
			$_SESSION['username'] = 'guest';
			$_SESSION['privilage'] = 1;
			
		}
	}
	
	// this will hold a cached list of the users that were looked up
	$GLOBALS['user_cache'] = array();
	
	// get users associated with the keys
	if(isset($_SESSION['settings']['keys']))
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => db_users::DATABASE,
				'WHERE' => 'PrivateKey = "' . join('" OR PrivateKey = "', $_SESSION['settings']['keys']) . '"',
				'LIMIT' => count($_SESSION['settings']['keys'])
			)
		, false);
		
		$_SESSION['settings']['keys_usernames'] = array();
		foreach($return as $index => $user)
		{
			$_SESSION['settings']['keys_usernames'][] = $user['Username'];
			
			unset($return[$index]['Password']);
		}
		
		$_SESSION['settings']['keys_users'] = $return;
	}
	
	register_output_vars('loggedin', $_SESSION['loggedin']);
}

// this is used to set up the input variables
function setupInputVars()
{
	
	// first fix the REQUEST_URI and pull out what is meant to be pretty dirs
	$_REQUEST['plugin'] = validate_plugin($_REQUEST);
	
	// call rewrite_vars in order to set some request variables
	$_REQUEST = rewrite_vars($_REQUEST);

	// go through the rest of the request and validate all the variables with the plugins they are for
	foreach($_REQUEST as $key => $value)
	{
		if(function_exists('validate_' . $key))
		{
			$_REQUEST[$key] = call_user_func_array('validate_' . $key, array($_REQUEST));
		}
		else
			unset($_REQUEST[$key]);
	}
	
	// check plugins for vars and trigger a session save
	foreach($_REQUEST as $key => $value)
	{
		if(isset($GLOBALS['triggers']['session'][$key]))
		{
			foreach($GLOBALS['triggers']['session'][$key] as $i => $plugin)
			{
				$_SESSION[$plugin] = call_user_func_array('session_' . $plugin, array($_REQUEST));
			}
		}
	}
	
	// do not let GoogleBot perform searches or file downloads
	if(NO_BOTS)
	{
		if(preg_match('/.*Googlebot.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
		{
			if(basename($_REQUEST['plugin']) != 'select' && 
				basename($_REQUEST['plugin']) != 'index' &&
				basename($_REQUEST['plugin']) != 'sitemap')
			{
				header('Location: ' . generate_href(array('plugin' => 'sitemap')));
				exit;
			}
			else
			{
				// don't let google bots perform searches, this takes up a lot of resources
				foreach($_REQUEST as $key => $value)
				{
					if(substr($key, 0, 6) == 'search')
					{
						unset($_REQUEST[$key]);
					}
				}
			}
		}
	}

	if($_SERVER['REMOTE_ADDR'] != '209.250.30.30' && substr($_SERVER['REMOTE_ADDR'], 0, 8) != '134.114.' && substr($_SERVER['REMOTE_ADDR'], 0, 8) != '192.168.' && substr($_SERVER['REMOTE_ADDR'], 0, 7) != '75.242.')
	{
		exit;
	}
}

function setupAliases()
{
	// get the aliases to use to replace parts of the filepath
	$GLOBALS['paths_regexp'] = array();
	$GLOBALS['alias_regexp'] = array();
	$GLOBALS['paths'] = array();
	$GLOBALS['alias'] = array();
	if(USE_ALIAS == true && USE_DATABASE == true)
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

// this scans the modules directory
function setupModules()
{
	
	// include the modules
	$tmp_modules = array();
	if ($dh = @opendir(LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR))
	{
		while (($file = readdir($dh)) !== false)
		{
			// filter out only the modules for our USE_DATABASE setting
			if ($file[0] != '.' && !is_dir(LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file))
			{
				$class_name = substr($file, 0, strrpos($file, '.'));
				if(!defined(strtoupper($class_name) . '_ENABLED') || constant(strtoupper($class_name) . '_ENABLED') != false)
				{
					// include all the modules
					require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file;
					
					// only use the module if it is properly defined
					if(class_exists($class_name))
					{
						if(substr($file, 0, 3) == (USE_DATABASE?'db_':'fs_'))
							$tmp_modules[] = $class_name;
					}
				}
			}
		}
		closedir($dh);
	}
	
	$error_count = 0;
	$new_modules = array();
	
	// reorganize modules to reflect heirarchy
	while(count($tmp_modules) > 0 && $error_count < 1000)
	{
		foreach($tmp_modules as $i => $module)
		{
			$tmp_override = get_parent_class($module);
			if(in_array($tmp_override, $new_modules) || $tmp_override == '')
			{
				$new_modules[] = $module;
				unset($tmp_modules[$i]);
			}
		}
		$error_count++;
	}
	$GLOBALS['modules'] = $new_modules;
}


// this is used to create the list of tables
function setupTables()
{
	// loop through each module and compile a list of databases
	$GLOBALS['tables'] = array();
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if(defined($module . '::DATABASE'))
			$GLOBALS['tables'][] = constant($module . '::DATABASE');
	}
	$GLOBALS['tables'] = array_values(array_unique($GLOBALS['tables']));
	
	// get watched and ignored directories because they are used a lot
	$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count);
	$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count);
	// always add user local to watch list
	$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', LOCAL_USERS));
}

// check if a module handles a certain type of files
//  this is a useful call for templates to use because it provides short syntax
function handles($file, $module)
{
	if(class_exists((USE_DATABASE?'db_':'fs_') . $module))
	{
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		return call_user_func((USE_DATABASE?'db_':'fs_') . $module . '::handles', $file);
	}
	return false;
}

function selfURL()
{
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")).$s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}

// check a list of files for access by comparing session keys and user information to each file
//  return the modified list of files with items removed
//  leave keys alone so plugin can profile more feedback
function checkAccess($file)
{
	// user can access files not handled by user module
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

// tokenize a string, assumed to be a filepath, in various different ways
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

// sorting function for terms in a keyword search
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

// parses the path inside of a file, useful to modules like archive and diskimage
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

// get all columns from every modules
function getAllColumns()
{
	$columns = array();
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if(USE_DATABASE == false || constant($module . '::INTERNAL') == false)
			$columns = array_merge($columns, array_flip(call_user_func($module . '::columns')));
	}
	
	$columns = array_keys($columns);

	return $columns;
}

// string together everything in the request
function getRequestString($request)
{
	$request_str = '';
	foreach($request as $key => $value) $request_str .= '&amp;' . $key . '=' . $value;
	return substr($request_str, 5, strlen($request_str) - 5);
}

// simple parser for command line like arguments
function parseCommandArgs($string)
{
	$args = array();
	$current = '';
	$quote_switch = false;
	for($i = 0; $i < strlen($string); $i++)
	{
		if(substr($string, $i, 1) == ' ' && $quote_switch == false)
		{
			$args[] = $current;
			$current = '';
		}
		elseif(substr($string, $i, 1) == '"')
		{
			$quote_switch = !$quote_switch;
		}
		else
		{
			$current .= substr($string, $i, 1);
		}
	}
	if($current != '')
		$args[] = $current;
		
	return $args;
}

// rounds the filesize and adds the extension
function roundFileSize($dirsize)
{
	$dirsize = ( $dirsize < 1024 ) ? ($dirsize . " B") : (( $dirsize < 1048576 ) ? (round($dirsize / 1024, 2) . " KB") : (( $dirsize < 1073741824 ) ? (round($dirsize / 1048576, 2) . " MB") : (( $dirsize < 1099511627776 ) ? (round($dirsize / 1073741824, 2) . " GB") : (round($dirsize / 1099511627776, 2) . " TB") ) ) );
	return $dirsize;
}

// just a function for getting the names keys used in the db_ids module
//  this is used is most plugins for simplifying database access by looking up the key first
function getIDKeys()
{
	$id_keys = array_flip(db_ids::columns());
	unset($id_keys['id']);
	unset($id_keys['Filepath']);
	unset($id_keys['Hex']);
	
	return $id_keys;
}

// notify of ascii problems when reading data
function utf8_is_ascii($str) {
	
	if ( strlen($str) > 0 )
	{
		
		// Search for any bytes which are outside the ASCII range...
		
		return (preg_match('/[^\x00-\x7F]/',$str) !== 1);
	
	}
	
	return false;
	
}

// get our file types, stuff the website can handle
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

// get the file extension
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

// get mime type based on file extension
function getMime($filename)
{
	return $GLOBALS['mte']->getMIMEType($filename);
}

// get the type which is the first part of a mime based on extension
function getExtType($filename)
{
	return MIME_Type::getMedia($GLOBALS['mte']->getMIMEType($filename));
}

// create the crc32 from a file, this uses a buffer size so it doesn't error out
function crc32_file($filename)
{
    $fp = @fopen($filename, "rb");
    $old_crc=false;

    if ($fp != false) {
        $buffer = '';
       
        while (!feof($fp)) {
            $buffer=fread($fp, BUFFER_SIZE);
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

function gf2_matrix_square(&$square, &$mat)
{
    for ($n=0;$n<32;$n++) {
        $square[$n]=gf2_matrix_times($mat, $mat[$n]);
    }
}

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

// kill a process on linux, for some reason closing the streams isn't working
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

function error_callback($error)
{
	if($error->code == E_DEBUG)
		$GLOBALS['debug_errors'][] = $error;
	elseif($error->code == E_USER)
		$GLOBALS['user_errors'][] = $error;
	else
		$GLOBALS['errors'][] = $error;
}


// stolen from PEAR
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