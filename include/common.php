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