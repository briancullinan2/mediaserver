<?php
// provide an easy to access interface to all the unique albums

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_audio.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_albums extends db_audio
{
	const DATABASE = 'audio';
	
	const NAME = 'Albums from Database';

	static function columns()
	{
		return array('id', 'SongCount', 'Album', 'Filepath');
	}
	
	static function handles($file)
	{
		return false;
	}

	static function handle($mysql, $file)
	{
	}
	
	
	static function get($mysql, $request, &$count, &$error)
	{
		// modify some request stuff
		$request['order_by'] = 'Album';
		$request['group_by'] = 'Album';
		$files = db_file::get($mysql, $request, $count, $error, 'db_audio');
		
		// make some changes
		foreach($files as $i => $file)
		{
			$files[$i]['Filetype'] = 'FOLDER';
			$files[$i]['Filesize'] = '0';
			$files[$i]['Filepath'] = dirname($files[$i]['Filepath']) . DIRECTORY_SEPARATOR;
			$files[$i]['Filename'] = $files[$i]['Album'];
			$files[$i]['SongCount'] = $files[$i]['count(*)'];
		}
		
		return $files;
	}


	static function cleanup($mysql, $watched, $ignored)
	{
	}

}

?>
