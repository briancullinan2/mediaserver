<?php
// provide an easy to access interface to all the unique albums

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_audio.php';

// music handler
class db_artists extends db_audio
{
	const DATABASE = 'audio';
	
	const NAME = 'Artists from Database';

	static function columns()
	{
		return array('id', 'SongCount', 'Artist', 'Filepath');
	}

	static function handle($database, $file)
	{
	}
	
	
	static function get($database, $request, &$count, &$error)
	{
		if(isset($request['dir']))
		{
			if($request['dir'][0] == DIRECTORY_SEPARATOR) $request['dir'] = substr($request['dir'], 1);
			if($request['dir'][strlen($request['dir'])-1] == DIRECTORY_SEPARATOR) $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
			if($request['dir'] == '$Unknown$')
				$request['dir'] = '';
			$request['search'] = '^' . preg_quote($request['dir']) . '$';
			$request['columns'] = 'Artist';
			unset($request['dir']);
			
			$files = parent::get($database, $request, $count, $error, 'db_audio');
		}
		else
		{
			// modify some request stuff
			$request['order_by'] = 'Artist';
			$request['order_trimmed'] = true;
			$request['group_by'] = 'Artist';
			
			$files = parent::get($database, $request, $count, $error, 'db_audio');
			
			// make some changes
			foreach($files as $i => $file)
			{
				if($files[$i]['Artist'] == '')
					$files[$i]['Artist'] = '$Unknown$';
				$files[$i]['Filetype'] = 'FOLDER';
				$files[$i]['Filesize'] = '0';
				$files[$i]['Filepath'] = DIRECTORY_SEPARATOR . $files[$i]['Artist'] . DIRECTORY_SEPARATOR;
				$files[$i]['Filename'] = $files[$i]['Artist'];
				$files[$i]['SongCount'] = $files[$i]['count(*)'];
			}
		}
		
		return $files;
	}


	static function cleanup($database, $watched, $ignored)
	{
	}

}

?>
