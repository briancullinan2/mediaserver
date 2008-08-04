<?php

require_once SITE_LOCALROOT . 'modules/db_file.php';

// include the id handler
require_once SITE_LOCALROOT . 'include/ID3/getid3.php';

// set up id3 reader incase any files need it
$getID3 = new getID3();

// music handler
class db_image_browser extends db_image
{
	const DATABASE = 'image';
	
	const NAME = 'Browser Image';

	

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

	static function handle($mysql, $file)
	{
	}
	
	static function get($mysql, $props)
	{
		return array();
	}


	static function cleanup($mysql, $watched)
	{
	}
}

?>