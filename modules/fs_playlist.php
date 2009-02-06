<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'ID3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$getID3 = new getID3();

// music handler
class fs_playlist extends db_file
{
	const DATABASE = 'files';
	
	const NAME = 'Playlist';

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt($file);
		
		switch($ext)
		{
			case 'wpl':
				return true;
			default:
				return false;
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