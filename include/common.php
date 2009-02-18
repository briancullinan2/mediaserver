<?php

// the most basic functions used a lot
// things to consider:
// get the extension for a file using getExt
// get the file mime type
// 

//session_cache_limiter('public');
if(!isset($no_setup) || !$no_setup == true)
	session_start();

// require the settings
if(realpath('/') == '/')
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.nix.php';
else
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.win.php';

// classes that this function uses to set up stuff should use the $no_setup = true option
if(!isset($no_setup) || !$no_setup == true)
	setup();

function setup()
{
	
	// get the list of templates
	$GLOBALS['templates'] = array();
	if ($dh = opendir(LOCAL_ROOT . 'templates'))
	{
		while (($file = readdir($dh)) !== false)
		{
			if ($file != '.' && $file != '..' && is_dir(LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $file))
			{
				$GLOBALS['templates'][] = $file;
			}
		}
	}
	
	// set the template if a permenent one isn't already set in the settings file
	if(!defined('LOCAL_TEMPLATE'))
	{
		if(isset($_REQUEST['template']) && in_array($_REQUEST['template'], $GLOBALS['templates']))
		{
			if(substr($_REQUEST['template'], strlen($_REQUEST['template']) - 1, 1) != DIRECTORY_SEPARATOR)
				$_REQUEST['template'] .= DIRECTORY_SEPARATOR;
			define('LOCAL_TEMPLATE',            					 'templates' . DIRECTORY_SEPARATOR . $_REQUEST['template']);
			$_SESSION['template'] = $_REQUEST['template'];
		}
		elseif(isset($_SESSION['template']))
		{
			define('LOCAL_TEMPLATE',            					 'templates' . DIRECTORY_SEPARATOR . $_SESSION['template']);
		}
		else
		{
			define('LOCAL_TEMPLATE',            					 'templates' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR);
			if(preg_match('/.*mobile.*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
			{
				$_SESSION['template'] = 'default' . DIRECTORY_SEPARATOR;
			}
			// don't set a template, allow them to choose
		}
	}
	else
	{
		$_SESSION['template'] = basename(LOCAL_TEMPLATE) . DIRECTORY_SEPARATOR;
	}
	
	// set the HTML_TEMPLATE for templates to refer to their own directory to provide resources
	define('HTML_TEMPLATE', str_replace(DIRECTORY_SEPARATOR, '/', LOCAL_TEMPLATE));
	
	// include template constants
	require_once LOCAL_ROOT . LOCAL_DEFAULT . 'config.php';
	
	if(file_exists(LOCAL_ROOT . LOCAL_TEMPLATE . 'config.php'))
		require_once LOCAL_ROOT . LOCAL_TEMPLATE . 'config.php';
	
	if( USE_DATABASE ) require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'sql.php';
	
	// include the sql class so it can be used by any page
	if( USE_DATABASE && DB_TYPE == 'mysql' )
	{
		include_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'mysql.php';
	}
	
	// load templating system
	require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'Smarty.class.php';
	
	// some modules depend on the mime-types in order to determine if it can handle that type of file
	loadMime();
	
	// include the modules
	$tmp_modules = array();
	if ($dh = opendir(LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR))
	{
		while (($file = readdir($dh)) !== false)
		{
			// filter out only the modules for our USE_DATABASE setting
			if ($file[0] != '.' && !is_dir(LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file) && (substr($file, 0, 3) == (USE_DATABASE?'db_':'fs_') || $file == 'fs_file.php'))
			{
				// include all the modules
				require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file;
				$class_name = substr($file, 0, strrpos($file, '.'));
				
				// only use the module if it is properly defined
				if(class_exists($class_name))
				{
					$tmp_modules[] = $class_name;
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
	
	// merge some session variables with the request so modules only have to look in one place
	if(isset($_SESSION['search']))
		$_REQUEST = array_merge($_SESSION['search'], $_REQUEST);
	if(isset($_SESSION['display']))
		$_REQUEST = array_merge($_SESSION['display'], $_REQUEST);
	
	//set the detail for the template
	if( !isset($_REQUEST['detail']) || !is_numeric($_REQUEST['detail']) )
		$_REQUEST['detail'] = 0;
		
	// get the aliases to use to replace parts of the filepath
	$GLOBALS['paths_regexp'] = array();
	$GLOBALS['alias_regexp'] = array();
	$GLOBALS['paths'] = array();
	$GLOBALS['alias'] = array();
	if(USE_ALIAS == true && USE_DATABASE == true)
	{
		$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
		$aliases = $mysql->get('alias', array('SELECT' => '*'));
		
		if($aliases !== false)
		{
			foreach($aliases as $key => $alias_props)
			{
				$GLOBALS['paths_regexp'][] = $alias_props['Paths_regexp'];
				$GLOBALS['alias_regexp'][] = $alias_props['Alias_regexp'];
				$GLOBALS['paths'][] = $alias_props['Paths'];
				$GLOBALS['alias'][] = $alias_props['Alias'];
			}
		}
	}
}

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
	$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}
function strleft($s1, $s2)
{
	return substr($s1, 0, strpos($s1, $s2));
}

// get all columns from every modules
function getAllColumns()
{
	$columns = array();
	foreach($GLOBALS['modules'] as $i => $module)
	{
		$columns = array_merge($columns, array_flip(call_user_func($module . '::columns')));
	}
	
	$columns = array_keys($columns);

	return $columns;
}


function getRequestString($request)
{
	$request_str = '';
	foreach($request as $key => $value) $request_str .= '&amp;' . $key . '=' . $value;
	return substr($request_str, 5, strlen($request_str) - 5);
}


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

function roundFileSize($dirsize)
{
	$dirsize = ( $dirsize < 1024 ) ? ($dirsize . " B") : (( $dirsize < 1048576 ) ? (round($dirsize / 1024, 2) . " KB") : (( $dirsize < 1073741824 ) ? (round($dirsize / 1048576, 2) . " MB") : (( $dirsize < 1099511627776 ) ? (round($dirsize / 1073741824, 2) . " GB") : (round($dirsize / 1099511627776, 2) . " TB") ) ) );
	return $dirsize;
}

function getIDsFromRequest($request, &$selected)
{
	if(!isset($selected))
		$selected = array();
	
	if(isset($request['item']))
	{
		if(is_string($request['item']))
		{
			$selected = split(',', $request['item']);
		}
		elseif(is_array($request['item']))
		{
			foreach($request['item'] as $id => $value)
			{
				if(($value == 'on' || (isset($request['select']) && $request['select'] == 'All')) && !in_array($id, $selected))
				{
					$selected[] = $id;
				}
				elseif(($value == 'off' || (isset($request['select']) && $request['select'] == 'None')) && ($key = array_search($id, $selected)) !== false)
				{
					unset($selected[$key]);
				}
			}
		}
	}

	if(isset($request['on']))
	{
		$request['on'] = split(',', $request['on']);
		foreach($request['on'] as $i => $id)
		{
			if(!in_array($id, $selected) && $id != '')
			{
				$selected[] = $id;
			}
		}
	}
	
	if(isset($request['off']))
	{
		$request['off'] = split(',', $request['off']);
		foreach($request['off'] as $i => $id)
		{
			if(($key = array_search($id, $selected)) !== false)
			{
				unset($selected[$key]);
			}
		}
	}
	
	// reset indices in this indexed array
	$selected = array_values($selected);
	
	if(count($selected) == 0) unset($selected);
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



// simple check for login to admin
function loggedIn()
{

	if( isset($_SESSION['username']) && isset($_SESSION['password']) )
	{
		if( $_SESSION['username'] == ADMIN_USER && $_SESSION['password'] == ADMIN_PASS )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}

}


// get our file types, stuff the website can handle
function getFileType($file)
{
	if( file_exists( $file ) )
	{
		if( is_dir($file) )
		{
			return 'FOLDER';
		}
		elseif( is_file($file) )
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


function getAllExts($type)
{

	if(isset($GLOBALS['type_to_ext'][$type]))
	{
		return $GLOBALS['type_to_ext'][$type];
	}
	else
	{
		return false;
	}
	
}

function getExtType($ext)
{
	if(strpos($ext, '.') !== false)
	{
		$ext = getExt($ext);
	}
	
	if(isset($GLOBALS['ext_to_type'][$ext]))
	{
		return $GLOBALS['ext_to_type'][$ext];
	}
	else
	{
		return strtoupper($ext);
	}
}

function crc32_file($filename)
{
    $fp=fopen($filename, "rb");
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

function loadMime()
{
	// this will load the mime-types from a linux dist mime.types file stored in includes
	// this will organize the types for easy lookup
	if(file_exists(LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'mime.types'))
	{
		$handle = fopen(LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'mime.types', 'r');
		$mime_text = fread($handle, filesize(LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'mime.types'));
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