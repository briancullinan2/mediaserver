<?php
// provide an easy to access interface to all the unique albums
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_audio.php';

// music handler
class db_years extends db_audio
{
	const DATABASE = 'audio';
	
	const NAME = 'Years from Database';

	static function columns()
	{
		return array('id', 'SongCount', 'Year', 'Filepath');
	}

	static function handles($file)
	{
		// we don't want this module to handle any files, it is just a wrapper
		return false;
	}

	static function handle($file, $force = false)
	{
		return false;
	}
	
	static function get($request, &$count)
	{
			
		// modify some request stuff
		if(isset($request['dir']))
		{
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
			if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
			if($request['dir'] == '$Unknown$')
				$request['dir'] = '';
			$request['search'] = '=' . $request['dir'] . '=';
			$request['columns'] = 'Year';
			unset($request['dir']);
			
			$files = parent::get($request, $count, 'db_audio');
		}
		else
		{
			$request['order_by'] = 'Year';
			$request['group_by'] = 'Year';
			
			$files = parent::get($request, $count, 'db_audio');
			
			// make some changes
			foreach($files as $i => $file)
			{
				if($files[$i]['Year'] == '')
					$files[$i]['Year'] = '$Unknown$';
				$files[$i]['Filetype'] = 'FOLDER';
				$files[$i]['Filesize'] = '0';
				$files[$i]['Filepath'] = '/' . $files[$i]['Year'] . '/';
				$files[$i]['Filename'] = $files[$i]['Year'];
				$files[$i]['SongCount'] = $files[$i]['count(*)'];
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
