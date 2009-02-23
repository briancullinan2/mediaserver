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
	static function out($database, $file, $stream)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			$files = $database->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				$file = $files[0];
				header('Content-Type: ' . $file['Filemime']);
				header('Content-Length: ' . $file['Filesize']);
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
				
				if($op !== false)
				{
					if($fp = fopen($files[0]['Filepath'], 'rb'))
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
		}
		return false;
	}
	
	static function handle($database, $file)
	{
	}
	

	static function cleanup($database, $watched)
	{
	}
}

?>