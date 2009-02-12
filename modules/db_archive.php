<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?
$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_archive extends db_file
{
	const DATABASE = 'archives';
	
	const NAME = 'Archives from Database';

	static function handles($file)
	{
		// parse through the file path and try to find a zip
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		$last_ext = '';
		foreach($paths as $i => $tmp_file)
		{
			// this will continue until either the end of the requested file (a .zip extension for example)
			// or if the entire path exists then it must be an actual folder on disk with a .zip in the name
			if(file_exists($last_path . $tmp_file))
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
			case 'zip':
			case 'rar':
			case 'gzip':
			case 'szip':
			case 'tar':
				return true;
			default:
				return false;
		}
		
		return false;

	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		$files = array();

		if(!USE_DATABASE)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( !isset($request['order_by']) || !in_array($request['order_by'], fs_archive::columns()) )
				$request['order_by'] = 'Title';
			if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
				$request['direction'] = 'ASC';
			if( isset($request['id']) )
				$request['item'] = $request['id'];
			getIDsFromRequest($request, $request['selected']);
		
			if(isset($request['selected']))
			{
				foreach($request['selected'] as $i => $id)
				{
					$file = pack('H*', $id);
					if(is_file($file))
					{
						if(fs_archive::handles($file))
						{
							$info = fs_archive::getInfo($file);
							// make some modifications
							$info['Filepath'] = stripslashes($info['Filepath']);
							if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
							$files[] = $info;
						}
					}
				}
			}
			
			if(isset($request['file']))
			{
				if(is_file($request['file']))
				{
					if(fs_archive::handles($request['file']))
					{
						return array(0 => fs_archive::getInfo($request['file']));
					}
					else{ $error = 'Invalid ' . fs_archive::NAME . ' file!'; }
				}
				else{ $error = 'File does not exist!'; }
			}
			else
			{
				if(!isset($request['dir']))
					$request['dir'] = realpath('/');
				if (is_dir($request['dir']))
				{
					$tmp_files = scandir($request['dir']);
					$count = count($tmp_files);
					for($j = 0; $j < $count; $j++)
						if(!fs_archive::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					$tmp_files = array_values($tmp_files);
					$count = count($tmp_files);
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						$info = fs_archive::getInfo($request['dir'] . $tmp_files[$i]);
						$info['Filepath'] = stripslashes($info['Filepath']);
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
						$files[] = $info;
					}
					return $files;
				}
				// maybe they are trying to access a zip file as if it were a folder
				// this is perfectly acceptable so lets check to see if this module handles it
				if(fs_archive::handles($request['dir']))
				{
					$ext = getExt($request['dir']);
					if(strpos($ext, DIRECTORY_SEPARATOR) !== false) $archive_dir = substr($ext, strpos($ext, DIRECTORY_SEPARATOR));
					else $archive_dir = '';
					$request['dir'] = substr($request['dir'], 0, strlen($request['dir']) - strlen($archive_dir));
					if(strlen($archive_dir) > 0 && $archive_dir[0] == DIRECTORY_SEPARATOR) $archive_dir = substr($archive_dir, strlen(DIRECTORY_SEPARATOR));
					$archive_dir = str_replace(DIRECTORY_SEPARATOR, '\/', $archive_dir);
					
					// make sure the file they are trying is access is actually a file
					if(is_file($request['dir']))
					{
						// analyze the file and output the files it contains
						$info = $GLOBALS['getID3']->analyze($request['dir']);
						
						// loop through central directory and list files with information
						foreach($info['zip']['central_directory'] as $i => $file)
						{
							if(preg_match('/^' . $archive_dir . '[^\/]+\/?$/i', $file['filename']) !== 0)
							{
								$fileinfo = array();
								$fileinfo['Filepath'] = $request['dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
								$fileinfo['id'] = bin2hex($fileinfo['Filepath']);
								$fileinfo['Filename'] = basename($file['filename']);
								if($file['filename'][strlen($file['filename'])-1] == '/')
									$fileinfo['Filetype'] = 'FOLDER';
								//$fileinfo['Filesize'] = filesize($file);
								//$fileinfo['Filemime'] = getMime($file);
								//$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
								$files[] = $fileinfo;
								
							}
						}
					}
					else
					{
						$error = 'File does not exist!';
					}
				}
				else{ $error = 'Directory does not exist!'; }
			}
		}
			
		return $files;
	}
	
	static function cleanup($mysql, $watched)
	{
	}

}

?>