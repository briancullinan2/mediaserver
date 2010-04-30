<?php
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fs_file.php';

// ISO handler
class fs_diskimage extends fs_file
{
	const NAME = 'Disk Images on Filesystem';
	
	// define if this handler is internal so templates won't try to use it
	const INTERNAL = false;

	static function init()
	{
		if(setting('exists_getid3'))
		{
			// include the id handler
			include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
			
			// set up id3 reader incase any files need it
			$GLOBALS['getID3'] = new getID3();
		}
		else
			PEAR::raiseError('getID3() missing from include directory! Archive handlers cannot function properly.', E_DEBUG);
	}

	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath');
	}

	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		
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
		if(!isset($GLOBALS['getID3']))
			self::init();
		
		$filename = str_replace('\\', '/', $filename);
		parseInner($filename, $last_path, $inside_path);
		
		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
		{
			$fileinfo = array();
			
			$info = $GLOBALS['getID3']->analyze($last_path);
			
			if($inside_path != '')
			{
				if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
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
								break;
							}
						}
					}
				}
				else{ PEAR::raiseError('Cannot read this type of file!', E_USER); }
			}
			// look at archive properties for the entire archive
			else
			{
				$last_path = str_replace('/', DIRECTORY_SEPARATOR, $last_path);
				// get entire image information
				$fileinfo = array();
				$fileinfo['Filepath'] = str_replace('\\', '/', $last_path);
				$fileinfo['Filename'] = basename($last_path);
				$fileinfo['Filetype'] = getExt($last_path);
				$fileinfo['Filesize'] = filesize($last_path);
				$fileinfo['Filemime'] = getMime($last_path);
				$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));
			}
		}
		
		return $fileinfo;
	}

	static function out($file)
	{
		$file = str_replace('\\', '/', $file);
		
		parseInner($file, $last_path, $last_ext);

		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
		{
			return @fopen(db_diskimage::PROTOCOL . '://' . $file, 'rb');
		}

		return false;
	}
	
	static function get($request, &$count)
	{
		$files = array();
		
		// do validation! for the fields we use
		if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
			$request['start'] = 0;
		if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
			$request['limit'] = 15;
		
		if(isset($request['selected']) && count($request['selected']) > 0 )
		{
			foreach($request['selected'] as $i => $id)
			{
				$file = @pack('H*', $id);
				if(fs_diskimage::handles($file))
				{
					$files[] = fs_diskimage::getInfo($file);
				}
			}
		}
		
		if(isset($request['file']))
		{
			$request['file'] = str_replace('\\', '/', $request['file']);
			if(fs_diskimage::handles($request['file']))
			{
				return array(0 => fs_diskimage::getInfo($request['file']));
			}
			else{ PEAR::raiseError('Invalid ' . fs_diskimage::NAME . ' file!', E_USER); }
		}
		else
		{
			if(!isset($request['dir']))
				$request['dir'] = realpath('/');
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if (is_dir(str_replace('/', DIRECTORY_SEPARATOR, $request['dir'])))
			{
				$tmp_files = scandir($request['dir']);
				$count = count($tmp_files);
				for($j = 0; $j < $count; $j++)
					if(!fs_diskimage::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
				$tmp_files = array_values($tmp_files);
				$count = count($tmp_files);
				for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
				{
					$files[] = fs_diskimage::getInfo($request['dir'] . $tmp_files[$i]);
				}
				return $files;
			}
			// maybe they are trying to access a zip file as if it were a folder
			// this is perfectly acceptable so lets check to see if this handler handles it
			if(fs_diskimage::handles($request['dir']))
			{
				parseInner($request['dir'], $last_path, $inside_path);
				if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '\/' . $inside_path;
				
				// make sure the file they are trying is access is actually a file
				if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
				{
					// analyze the file and output the files it contains
					$info = $GLOBALS['getID3']->analyze($last_path);

					$count = 0;
					if(isset($info['iso']) && isset($info['iso']['directories']))
					{
						// loop through central directory and list files with information
						$directories = array();
						foreach($info['iso']['directories'] as $i => $directory)
						{
							foreach($directory as $j => $file)
							{
								if(preg_match('/^' . $inside_path . '[^\/]+\/?$/i', $file['filename']) !== 0)
								{
									if(!in_array($file['filename'], $directories))
									{
										if($count >= $request['start'] && $count < $request['start']+$request['limit'])
										{
											// prevent repeat directory listings
											$directories[] = $file['filename'];
											$fileinfo = array();
											$fileinfo['Filepath'] = $last_path . $file['filename'];
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
										$count++;
									}
								}
							}
						}
					}
					else{ PEAR::raiseError('Cannot read this type of file!', E_USER); }
				}
				else{ PEAR::raiseError('File does not exist!', E_USER); }
			}
			else{ PEAR::raiseError('Directory does not exist!', E_USER); }
		}
			
		return $files;
	}

}

?>