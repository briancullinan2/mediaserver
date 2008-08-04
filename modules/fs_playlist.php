<?php

require_once SITE_LOCALROOT . 'modules/db_file.php';

// include the id handler
require_once SITE_LOCALROOT . 'include/ID3/getid3.php';

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