<?php

// search IMDb for movie
// cache useful information
// search for the title of single video files, if it exists in a directory call movies
//  use parseFilename to search with

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_diskimage.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_movies extends db_file
{
	const DATABASE = 'movies';
	
	const NAME = 'Movies from Database';

	static function columns()
	{
		return array('id', 'Title', 'Director', 'ReleaseDate', 'Genre', 'Plot', 'Cast', 'Runtime', 'Language', 'AspectRatio', 'Filepath');
	}

	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		// parse through the file path and try to find a zip
		$paths = split('/', $file);
		$last_path = '';
		$last_ext = '';
		foreach($paths as $i => $tmp_file)
		{
			// this will continue until either the end of the requested file (a .zip extension for example)
			// or if the entire path exists then it must be an actual folder on disk with a .zip in the name
			if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path . $tmp_file)) || $last_path == '')
			{
				$last_ext = getExt($last_path . $tmp_file);
				$last_path = $last_path . $tmp_file . '/';
			}
			else
			{
				// if the last path exists and the last $ext is an archive then we know the path is inside an archive
				if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
				{
					// we can break
					break;
				}
			}
		}
		
		if($last_ext == 'iso')
		{
			$info = $GLOBALS['getID3']->analyze($last_path);
			
			if(isset($info['iso']) && isset($info['iso']['directories']))
			{
				
			}
		}
		
		return false;

	}
	
	static function handle($file, $force = false)
	{
		return false;
	}
	
	static function getInfo($filename)
	{
		return array();
	}

	static function out($file)
	{
	}
	
	static function get($request, &$count, &$error)
	{
		$files = db_file::get($request, $count, $error, get_class());
		
		return $files;
	}
	
	static function remove($file)
	{
		//parent::remove($file, get_class());
	}
	
	static function cleanup()
	{
		// call default cleanup function
		//db_file::cleanup(get_class());
	}

}

?>