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
	static function out($mysql, $file, $stream)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			header('Content-Type: ' . getMime($file));
			header('Content-Length: ' . filesize($file));
			
			if(is_string($stream))
				$op = fopen($stream, 'wb');
			else
				$op = $stream;
			
			if($op !== false)
			{
				if($fp = fopen($file, 'rb'))
				{
					while (!feof($fp)) {
						fwrite($op, fread($fp, BUFFER_SIZE));
					}				
					fclose($fp);
					fclose($op);
					return true;
				}
			}
		}
		return false;
	}
	
}

?>