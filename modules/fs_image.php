<?php

// music handler
class fs_image extends fs_file
{
	const NAME = 'Images on Filesystem';
	
	// define if this module is internal so templates won't try to use it
	const INTERNAL = false;

	static function init()
	{
		// include the id handler
		require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
		
		// set up id3 reader incase any files need it
		$GLOBALS['getID3'] = new getID3();
	}

	static function columns()
	{
		return array('id', 'Height', 'Width', 'Make', 'Model', 'Title', 'Keywords', 'Author', 'Comments', 'ExposureTime', 'Filepath');
	}
	
	// COMPUTED usually contains the most accurate height and width values
	// IFD0 contains the make and model we are looking for
	// WINXP contains comments we should copy
	// EXIF contains a cool exposure time
	// THUMBNAIL just incase the thumbnail has some missing information
	
	static function handles($file)
	{
				
		// get file extension
		$ext = getExt(basename($file));
		$type = getExtType($ext);
		
		if( $type == 'image' )
		{
			return true;
		}
		
		return false;

	}
		
	static function getInfo($file)
	{
		if(!isset($GLOBALS['getID3']))
			self::init();
		
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$fileinfo = db_image::getInfo($file);
		$fileinfo['id'] = bin2hex($file);
		$fileinfo['Filepath'] = stripslashes($fileinfo['Filepath']);
		
		return $fileinfo;
	}

	static function get($request, &$count, &$error)
	{
		return parent::get($request, $count, $error, get_class());
	}

}

?>