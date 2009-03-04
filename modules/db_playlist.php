<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?
$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// music handler
class db_playlist extends db_file
{
	const DATABASE = 'files';
	
	const NAME = 'Playlists from Database';
	
	static function columns()
	{
		return array('id', 'SongCount', 'Filename', 'Filepath');
	}

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

	static function handle($database, $file)
	{
	}
	
	static function get($database, $request, &$count, &$error, $module = NULL)
	{
		if(isset($request['dir']))
		{
			$request['file'] = str_replace('\\', '/', $request['dir']);
			if(USE_ALIAS == true)
				$request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
			
			$files = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['file']) . '"'));
			
			if(count($files) > 0)
			{
				// open playlist and parse out paths
				if($fp = fopen($request['file'], 'rb'))
				{
					$tmp_files = array();
					switch($files[0]['Filetype'])
					{
						case 'WPL':
							while(!feof($fp))
							{
								$buffer = fgets($fp);
								$count = preg_match('/\<media src="([^"]*)"( ?(t|c)id=| ?\/\>)/i', $buffer, $matches);
								if($count > 0)
								{
									$tmp_files[] = $matches[0];
								}
							}
					}
					fclose($fp);
					
					// now process the matches
					$count = count($tmp_files);
					
					// go through each file and do multiple steps from most presice to most general and try to find the file
				}
			}
		}
		else
		{
			$request['search_Filename'] = '.wpl$';
			$files = parent::get($database, $request, $count, $error, get_class());
		}
		
		return $files;
	}


	static function cleanup($database, $watched)
	{
	}
}

?>