<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fs_image.php';

// image handler
class fs_image_browser extends fs_image
{
	const NAME = 'Browser Images on Filesystem';
	
	// define if this module is internal so templates won't try to use it
	const INTERNAL = false;

	static function handles($file)
	{
				
		// get file extension
		$type = getExtType($file);
		
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
		header('Content-Disposition: ');
		return fs_file::out($file);
	}
	
	static function get($request, &$count, &$error)
	{
		return parent::get($request, $count, $error, get_class());
	}
}

?>