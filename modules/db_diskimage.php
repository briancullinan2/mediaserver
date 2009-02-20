<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_diskimage extends db_file
{
	const DATABASE = 'diskimage';
	
	const NAME = 'Disk Images from Database';

	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath');
	}

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
	
	static function handle($mysql, $file)
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
		
		$file = $last_path;

		if(db_diskimage::handles($file))
		{
			// check to see if it is in the database
			$db_diskimage = $mysql->query(array(
					'SELECT' => db_diskimage::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_diskimage) == 0 )
			{
				$fileid = db_diskimage::add($mysql, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $mysql->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = db_diskimage::add($mysql, $file, $db_diskimage[0]['id']);
				}
				
			}

		}
		
	}
	
	static function add($mysql, $file, $image_id = NULL)
	{
		// do a little cleanup here
		// if the image changes remove all it's inside files from the database
		if( $image_id != NULL )
		{
			print 'Removing disk image: ' . $file . "\n";
			$mysql->query(array('DELETE' => 'diskimage', 'WHERE' => 'Filepath REGEXP "^' . addslashes(addslashes($file)) . '\\\/"'));
		}

		// pull information from $info
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
		
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if(isset($info['iso']) && isset($info['iso']['directories']))
		{
			$directories = array();
			foreach($info['iso']['directories'] as $i => $directory)
			{
				foreach($directory as $j => $file)
				{
					if(!in_array($file['filename'], $directories))
					{
						$directories[] = $file['filename'];
						$fileinfo = array();
						$fileinfo['Filepath'] = $last_path . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
						$fileinfo['Filename'] = basename($fileinfo['Filepath']);
						if($file['filename'][strlen($file['filename'])-1] == '/')
							$fileinfo['Filetype'] = 'FOLDER';
						else
							$fileinfo['Filetype'] = getExt($file['filename']);
						if($fileinfo['Filetype'] === false)
							$fileinfo['Filetype'] = 'FILE';
						$fileinfo['Filesize'] = $file['filesize'];
						$fileinfo['Filemime'] = getMime($file['filename']);
						$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['recording_timestamp']);
						
						print 'Adding file in disk image: ' . $fileinfo['Filepath'] . "\n";
						$id = $mysql->query(array('INSERT' => 'diskimage', 'VALUES' => $fileinfo));
					}
				}
			}
		}
		
		// get entire image information
		$fileinfo = array();
		$fileinfo['Filepath'] = $last_path;
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Filetype'] = getExt($last_path);
		if($fileinfo['Filetype'] === false)
			$fileinfo['Filetype'] = 'FILE';
		$fileinfo['Filesize'] = filesize($last_path);
		$fileinfo['Filemime'] = getMime($last_path);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));

		// print status
		if( $image_id != NULL )
		{
			print 'Modifying Disk Image: ' . $fileinfo['Filepath'] . "\n";
			
			// update database
			$id = $mysql->query(array('UPDATE' => 'diskimage', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding Disk Image: ' . $fileinfo['Filepath'] . "\n";
			
			// add to database
			$id = $mysql->query(array('INSERT' => 'diskimage', 'VALUES' => $fileinfo));
			
			return $id;
		}
		
		flush();
		
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
		$inside_path = str_replace(DIRECTORY_SEPARATOR, '/', $inside_path);
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if(is_file($last_path))
		{
			$files = $mysql->query(array('SELECT' => db_archive::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				
				$info = $GLOBALS['getID3']->analyze($last_path);
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
	
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
									header('Content-Transfer-Encoding: binary');
									header('Content-Type: ' .  getMime($inside_path));
									header('Content-Length: ' . $file['filesize']);
									header('Content-Disposition: attachment; filename="' . basename($inside_path) . '"');
									
									if($op !== false)
									{
										if($fp = fopen($last_path, 'rb'))
										{
											fseek($fp, $file['offset_bytes']);
											
											$total = 0;
											while($total < $file['filesize'])
											{
												$buffer = fread($fp, min(BUFFER_SIZE, $file['filesize'] - $total));
												$total += strlen($buffer);
												fwrite($op, $buffer);
											}
											fclose($fp);
											fclose($op);
											return true;
										}
									}
								}
							}
						}
					} else{ $error = 'Cannot read this type of file!'; }
				}
				else
				{
					// download entire image
					header('Content-Transfer-Encoding: binary');
					header('Content-Type: ' .  getMime($last_path));
					header('Content-Length: ' . filesize($last_path));
					header('Content-Disposition: attachment; filename="' . basename($last_path) . '"');
					
					if($op !== false)
					{
						if($fp = fopen($last_path, 'rb'))
						{
							while (!feof($fp)) {
								fwrite($op, fread($fp, BUFFER_SIZE));
							}				
							fclose($fp);
							fclose($op);
							return true;
						}
					}
				}
			} else{ $error = 'File not found!'; }
		}

		return false;
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		if(isset($request['dir']))
		{
			if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

			$paths = split('\\' . DIRECTORY_SEPARATOR, $request['dir']);
			$last_path = '';
			foreach($paths as $i => $tmp_file)
			{
				if(file_exists($last_path . $tmp_file) || $last_path == '')
				{
					$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
					if(strlen($last_path) == 0 || $last_path[strlen($last_path)-1] != DIRECTORY_SEPARATOR)
						$last_path .= DIRECTORY_SEPARATOR;
				} else {
					if(file_exists($last_path))
						break;
				}
			}
			$inside_path = substr($request['dir'], strlen($last_path));
			if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = DIRECTORY_SEPARATOR . $inside_path;
			if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
			$request['dir'] = $last_path . $inside_path;
			
			if(!is_file($last_path))
			{
				unset($_REQUEST['dir']);
				$error = 'Directory does not exist!';
			}
		}
		
		$files = db_file::get($mysql, $request, $count, $error, 'db_diskimage');
		
		return $files;
	}
	
	static function cleanup_remove($row, $args)
	{
		$paths = split('\\' . DIRECTORY_SEPARATOR, $row['Filepath']);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
				if(strlen($last_path) == 0 || $last_path[strlen($last_path)-1] != DIRECTORY_SEPARATOR)
					$last_path .= DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if( !file_exists($last_path) )
		{
			$args['CONNECTION']->query(array('DELETE' => constant($args['MODULE'] . '::DATABASE'), 'WHERE' => 'Filepath REGEXP "' . addslashes(addslashes($row['Filepath'])) . '\\\/" OR Filepath = "' . addslashes($row['Filepath']) . '"'));
			
			print 'Removing ' . constant($args['MODULE'] . '::NAME') . ': ' . $row['Filepath'] . "\n";
		}
	}

	static function cleanup($mysql, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($mysql, $watched, $ignored, get_class());
	}
}

?>