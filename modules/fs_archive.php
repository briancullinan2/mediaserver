<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?
$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_archive extends fs_file
{
	const NAME = 'Archives from Filesystem';

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
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
		
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if($inside_path != '')
		{
			
			foreach($info['zip']['central_directory'] as $i => $file)
			{
				if($file['filename'] == $inside_path)
				{
					$fileinfo['Filepath'] = $filename;
					$fileinfo['id'] = bin2hex($fileinfo['Filepath']);
					$fileinfo['Filename'] = basename($file['filename']);
					if($file['filename'][strlen($file['filename'])-1] == '/')
						$fileinfo['Filetype'] = 'FOLDER';
					else
						$fileinfo['Filetype'] = getExt($file['filename']);
					if($fileinfo['Filetype'] === false)
						$fileinfo['Filetype'] = 'FILE';
					$fileinfo['Filesize'] = $file['uncompressed_size'];
					$fileinfo['Compressed'] = $file['compressed_size'];
					$fileinfo['Filemime'] = getMime($file['filename']);
					$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
				}
			}
		}
		// look at archive properties for the entire archive
		else
		{
		}
		
		return $fileinfo;
	}

	static function out($mysql, $file, $stream)
	{
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
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
		$inside_path = substr($file, strlen($last_path));
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
		
		$fp = fopen($last_path, 'rb');
		
		$info = array('fileformat'=>'', 'error'=>array(), 'filesize'=>filesize($last_path));
		$getid3_zip = new getid3_zip($fp, $info);
		
		if(is_string($stream))
			$op = fopen($stream, 'wb');
		else
			$op = $stream;
			
		if($op !== false)
		{
			foreach($info['zip']['central_directory'] as $i => $file)
			{
				if($file['filename'] == $inside_path)
				{
					fseek($fp, $info['zip']['central_directory'][$i]['entry_offset']);
					$LocalFileHeader = $getid3_zip->ZIPparseLocalFileHeader($fp);
					
					header('Content-Transfer-Encoding: binary');
					header('Content-Type: ' . getMime($inside_path));
					header('Content-Length: ' . $LocalFileHeader['uncompressed_size']);
					//header('Content-Disposition: attachment; filename="' . basename($inside_path) . '"');
			
					fseek($fp, $LocalFileHeader['data_offset']);
					$total = 0;
					while($total < $LocalFileHeader['compressed_size'])
					{
						$buffer = gzread($fp, $LocalFileHeader['compressed_size']);
						//$buffer = gzinflate($buffer);
						$total += strlen($total);
						fwrite($op, $buffer);
					}
					fclose($fp);
					fclose($op);
					return true;
				}
			}
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
			
			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				foreach($request['selected'] as $i => $id)
				{
					$file = pack('H*', $id);
					if(fs_archive::handles($file))
					{
						$files[] = fs_archive::getInfo($file);
					}
				}
			}
			
			if(isset($request['file']))
			{
				if(fs_archive::handles($request['file']))
				{
					return array(0 => fs_archive::getInfo($request['file']));
				}
				else{ $error = 'Invalid ' . fs_archive::NAME . ' file!'; }
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
					$paths = split('\\' . DIRECTORY_SEPARATOR, $request['dir']);
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
					$inside_path = substr($request['dir'], strlen($last_path));
					$inside_path = str_replace(DIRECTORY_SEPARATOR, '\/', $inside_path);
					if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
					
					// make sure the file they are trying is access is actually a file
					if(is_file($last_path))
					{
						// analyze the file and output the files it contains
						$info = $GLOBALS['getID3']->analyze($last_path);
						
						$count = 0;
						// loop through central directory and list files with information
						foreach($info['zip']['central_directory'] as $i => $file)
						{
							if(preg_match('/^' . $inside_path . '[^\/]+\/?$/i', $file['filename']) !== 0)
							{
								if($count >= $request['start'] && $count < $request['start']+$request['limit'])
								{
									$fileinfo = array();
									$fileinfo['Filepath'] = $last_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
									$fileinfo['id'] = bin2hex($fileinfo['Filepath']);
									$fileinfo['Filename'] = basename($file['filename']);
									if($file['filename'][strlen($file['filename'])-1] == '/')
										$fileinfo['Filetype'] = 'FOLDER';
									else
										$fileinfo['Filetype'] = getExt($file['filename']);
									if($fileinfo['Filetype'] === false)
										$fileinfo['Filetype'] = 'FILE';
									$fileinfo['Filesize'] = $file['uncompressed_size'];
									$fileinfo['Compressed'] = $file['compressed_size'];
									$fileinfo['Filemime'] = getMime($file['filename']);
									$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
									$files[] = $fileinfo;
								}
								$count++;
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

}

?>