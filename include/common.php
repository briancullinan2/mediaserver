<?php

// the most basic functions used a lot
// things to consider:
// get the extension for a file using getExt
// get the file mime type
// 

session_start();

// require the settings
require_once 'settings.php';

require_once 'sql.php';

// include the sql class so it can be used by any page
if( DB_TYPE == 'mysql' )
{
	include_once 'mysql.php';
}

// include the modules
$tmp_modules = array();
if ($dh = opendir(MODULES_DIR))
{
	while (($file = readdir($dh)) !== false)
	{
		if ($file != '.' && $file != '..')
		{
			// include all the modules
			require_once MODULES_DIR . $file;
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


function roundFileSize($dirsize)
{
	$dirsize = ( $dirsize < 1024 ) ? ($dirsize . " B") : (( $dirsize < 1048576 ) ? (round($dirsize / 1024, 2) . " KB") : (( $dirsize < 1073741824 ) ? (round($dirsize / 1048576, 2) . " MB") : (( $dirsize < 1099511627776 ) ? (round($dirsize / 1073741824, 2) . " GB") : (round($dirsize / 1099511627776, 2) . " TB") ) ) );
	return $dirsize;
}


// get the constant from a class
function get_class_const($class, $const)
{
	return constant(sprintf('%s::%s', $class, $const));
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
				return getMime($ext);
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
		return strtoupper($ext);
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



?>