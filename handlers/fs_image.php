<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fs_file.php';

// image handler
class fs_image extends fs_file
{
	const NAME = 'Images on Filesystem';
	
	// define if this handler is internal so templates won't try to use it
	const INTERNAL = false;

	static function init()
	{
		// include the id handler
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
		
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
		$type = getExtType($file);
		
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

	static function get($request, &$count)
	{
		return parent::get($request, $count, get_class());
	}

}

?>