<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_image.php';

// music handler
class db_image_browser extends db_image
{
	const DATABASE = 'image';
	
	const NAME = 'Browser Images from Database';

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt($file);
		$type = getExtType($ext);
		
		if( $type == 'image' )
		{
			switch($ext)
			{
				case 'bmp':
				case 'gif':
				case 'png':
				case 'jpeg':
				case 'jpg':
					return true;
				default:
					return false;
			}
		}
		
		return false;

	}
	
	// output provided file to given stream
	static function out($file)
	{
		// check to make sure file is valid
		header('Content-Disposition: ');
		return db_file::out($file);
	}
	
	static function handle($file, $force = false)
	{
		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		$files = parent::get($request, $count, $error);
		if(count($files) == 0)
		{
			$files = db_file::get($request, $count, $error);
		}
		return $files;
	}
	
	static function remove($file)
	{
	}

	static function cleanup()
	{
	}
}

?>