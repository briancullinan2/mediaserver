<?php
// provide an easy to access interface to all the unique albums

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_audio.php';

// music handler
class db_years extends db_audio
{
	const DATABASE = 'audio';
	
	const NAME = 'Years from Database';

	static function columns()
	{
		return array('id', 'SongCount', 'Year', 'Filepath');
	}

	static function handle($database, $file)
	{
	}
	
	static function get($database, $request, &$count, &$error)
	{
		$database->validate($request, $props, get_class());
			
		// modify some request stuff
		if(isset($request['dir']))
		{
			if($request['dir'][0] == DIRECTORY_SEPARATOR) $request['dir'] = substr($request['dir'], 1);
			if($request['dir'][strlen($request['dir'])-1] == DIRECTORY_SEPARATOR) $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
			if($request['dir'] == '$Unknown$')
				$request['dir'] = '';
			$request['search'] = '^' . preg_quote($request['dir']) . '$';
			$request['columns'] = 'Year';
			unset($request['dir']);
			
			$files = parent::get($database, $request, $count, $error, 'db_audio');
		}
		else
		{
			$request['order_by'] = 'Year';
			$request['group_by'] = 'Year';
			
			$files = parent::get($database, $request, $count, $error, 'db_audio');
			
			// make some changes
			foreach($files as $i => $file)
			{
				if($files[$i]['Year'] == '')
					$files[$i]['Year'] = '$Unknown$';
				$files[$i]['Filetype'] = 'FOLDER';
				$files[$i]['Filesize'] = '0';
				$files[$i]['Filepath'] = DIRECTORY_SEPARATOR . $files[$i]['Year'] . DIRECTORY_SEPARATOR;
				$files[$i]['Filename'] = $files[$i]['Year'];
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
