<?php
// provide an easy to access interface to all the unique albums
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_audio.php';

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
		// we don't want this handler to handle any files, it is just a wrapper
		return false;
	}

	static function handle($file, $force = false)
	{
		return false;
	}
	
	static function get($request, &$count)
	{
		
		if(isset($request['dir']) && ($request['dir'] == '' || $request['dir'] == '/'))
		{
			unset($request['dir']);
		}
		
		// modify some request stuff
		if(isset($request['dir']))
		{
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
			if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
			if($request['dir'] == '$Unknown$')
				$request['dir'] = '';
			$request['search_Album'] = '=' . $request['dir'] . '=';
			unset($request['dir']);
			
			$files = parent::get($request, $count, get_class());
		}
		else
		{
			$request['order_by'] = 'Album';
			$request['group_by'] = 'Album';
			
			$files = parent::get($request, $count, get_class());
			
			// make some changes
			foreach($files as $i => $file)
			{
				if($files[$i]['Album'] == '')
					$files[$i]['Album'] = '$Unknown$';
				$files[$i]['Filetype'] = 'FOLDER';
				$files[$i]['Filesize'] = '0';
				$files[$i]['Filepath'] = '/' . $files[$i]['Album'] . '/';
				$files[$i]['Filename'] = $files[$i]['Album'];
				$files[$i]['SongCount'] = $files[$i]['count(*)'];
				unset($files[$i]['Title']);
				unset($files[$i]['Track']);
				unset($files[$i]['Bitrate']);
				unset($files[$i]['Length']);
				unset($files[$i]['Artist']);
			}
		}
		
		return $files;
	}
	
	static function remove($file)
	{
	}

	static function cleanup()
	{
	}

}

?>
