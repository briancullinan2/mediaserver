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

	static function handles($file)
	{
		// we don't want this module to handle any files, it is just a wrapper
		return false;
	}

	static function handle($database, $file)
	{
	}
	
	
	static function get($database, $request, &$count, &$error)
	{
		if(isset($request['dir']))
		{
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
			if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
			if(strpos($request['dir'], '/') !== false)
			{
				$dirs = split('/', $request['dir']);
				if($dirs[0] == '$Unknown$')
					$dirs[0] = '';
				if($dirs[1] == '$Unknown$')
					$dirs[1] = '';
				$request['search_Artist'] = '=' . $dirs[0] . '=';
				$request['search_Album'] = '=' . $dirs[1] . '=';
				unset($request['dir']);
				
				// set to db_file which will handle the request
				$files = parent::get($database, $request, $count, $error, 'db_audio');
			}
			else
			{
				// modify some request stuff
				$request['order_by'] = 'Album';
				$request['order_trimmed'] = true;
				$request['group_by'] = 'Album';

				if($request['dir'] == '$Unknown$')
					$request['dir'] = '';
				$request['search_Artist'] = '=' . $request['dir'] . '=';
				unset($request['dir']);
				
				$files = parent::get($database, $request, $count, $error, 'db_audio');
				
				// make some changes
				foreach($files as $i => $file)
				{
					if($files[$i]['Artist'] == '')
						$files[$i]['Artist'] = '$Unknown$';
					if($files[$i]['Album'] == '')
						$files[$i]['Album'] = '$Unknown$';
					$files[$i]['Filetype'] = 'FOLDER';
					$files[$i]['Filesize'] = '0';
					$files[$i]['Filepath'] = '/' . $files[$i]['Artist'] . '/' . $files[$i]['Album'] . '/';
					$files[$i]['Filename'] = $files[$i]['Album'];
					$files[$i]['SongCount'] = $files[$i]['count(*)'];
				}
			}
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
				$files[$i]['Filepath'] = '/' . $files[$i]['Artist'] . '/';
				$files[$i]['Filename'] = $files[$i]['Artist'];
				$files[$i]['SongCount'] = $files[$i]['count(*)'];
			}
		}
		
		return $files;
	}


	static function cleanup($database)
	{
	}

}

?>
