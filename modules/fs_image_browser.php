<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_image.php';

// music handler
class fs_image_browser extends fs_image
{
	const NAME = 'Browser Images on Filesystem';

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
	static function out($database, $file, $stream)
	{
		$file = str_replace('\\', '/', $file);
		
		// check to make sure file is valid
		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
		{
			header('Content-Disposition: ');
			return fs_file::out($database, $file);
		}
		return false;
	}
	
	static function get($database, $request, &$count, &$error)
	{
		return parent::get(NULL, $request, $count, $error, get_class());
	}
}

?>