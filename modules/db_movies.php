<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_diskimage.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_movies_tmp extends db_diskimage
{
	const DATABASE = 'movies';
	
	const NAME = 'Movies from Database';

	static function columns()
	{
		return array('id', 'Title', 'Director', 'ReleaseDate', 'Genre', 'Plot', 'Cast', 'Runtime', 'Language', 'AspectRatio', 'Filepath');
	}

	static function handles($file)
	{
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['ALL']['alias_regexp'], $GLOBALS['ALL']['paths'], $file);
		
		// parse through the file path and try to find a zip
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		$last_ext = '';
		foreach($paths as $i => $tmp_file)
		{
			// this will continue until either the end of the requested file (a .zip extension for example)
			// or if the entire path exists then it must be an actual folder on disk with a .zip in the name
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_ext = getExt($last_path . $tmp_file);
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			}
			else
			{
				// if the last path exists and the last $ext is an archive then we know the path is inside an archive
				if(file_exists($last_path))
				{
					// we can break
					break;
				}
			}
		}
		
		switch($last_ext)
		{
			case 'iso':
				return true;
			default:
				return false;
		}
		
		return false;

	}
	
	static function getInfo($filename)
	{
		$fileinfo = array();
		
		$paths = split('\\' . DIRECTORY_SEPARATOR, $filename);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		$inside_path = substr($filename, strlen($last_path));
		$inside_path = str_replace(DIRECTORY_SEPARATOR, '/', $inside_path);
		if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
		
		if(is_file($last_path))
		{
			$info = $GLOBALS['getID3']->analyze($last_path);
			
			if($inside_path != '')
			{
				if(isset($info['iso']) && isset($info['iso']['directories']))
				{
					foreach($info['iso']['directories'] as $i => $directory)
					{
						foreach($directory as $j => $file)
						{
							if($file['filename'] == $inside_path)
							{
								$fileinfo = array();
								$fileinfo['Filepath'] = $last_path . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
								$fileinfo['id'] = bin2hex($fileinfo['Filepath']);
								$fileinfo['Filename'] = basename($file['filename']);
								if($file['filename'][strlen($file['filename'])-1] == '/')
									$fileinfo['Filetype'] = 'FOLDER';
								else
									$fileinfo['Filetype'] = getExt($file['filename']);
								if($fileinfo['Filetype'] === false)
									$fileinfo['Filetype'] = 'FILE';
								$fileinfo['Filesize'] = $file['filesize'];
								$fileinfo['Filemime'] = getMime($file['filename']);
								$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['recording_timestamp']);
								$files[] = $fileinfo;
							}
						}
					}
				}
				else{ $error = 'Cannot read this type of file!'; }
			}
			// look at archive properties for the entire archive
			else
			{
			}
		}
		
		return $fileinfo;
	}

	static function out($database, $file, $stream)
	{
	}
	
	static function get($database, $request, &$count, &$error)
	{
		$files = db_file::get($database, $request, $count, $error, get_class());
		
		return $files;
	}
	
	static function cleanup($database, $watched, $ignored)
	{
		// call default cleanup function
		//db_file::cleanup($database, $watched, $ignored, get_class());
	}

}

?>