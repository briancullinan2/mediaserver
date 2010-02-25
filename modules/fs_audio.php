<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fs_file.php';

// music handler
class fs_audio extends fs_file
{
	const NAME = 'Audio on Filesystem';
	
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
		return array('id', 'Track', 'Title', 'Artist', 'Album', 'Genre', 'Year', 'Length', 'Bitrate', 'Comments', 'Filepath');
	}
	
	static function handles($file)
	{
				
		// get file extension
		$type = getExtType($file);
		
		if( $type == 'audio' )
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
		
		$fileinfo = db_audio::getInfo($file);
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
