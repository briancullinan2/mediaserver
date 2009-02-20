<?php

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
	const DATABASE = 'archive';
	
	const NAME = 'Archives from Database';

	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Compressed', 'Filedate', 'Filetype', 'Filepath');
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
			case 'gz':
			case 'szip':
			case 'tar':
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

		if(db_archive::handles($file))
		{
			// check to see if it is in the database
			$db_archive = $mysql->query(array(
					'SELECT' => db_archive::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_archive) == 0 )
			{
				$fileid = db_archive::add($mysql, $file);
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
					$id = db_archive::add($mysql, $file, $db_archive[0]['id']);
				}
				
			}

		}
		
	}

	static function add($mysql, $file, $archive_id = NULL)
	{
		// do a little cleanup here
		// if the archive changes remove all it's inside files from the database
		if( $archive_id != NULL )
		{
			print 'Removing archive: ' . $file . "\n";
			$mysql->query(array('DELETE' => 'archive', 'WHERE' => 'Filepath REGEXP "^' . addslashes(addslashes($file)) . '\\\/"'));
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
		
		if(isset($info['zip']) && isset($info['zip']['central_directory']))
		{
			$directories = array();
			foreach($info['zip']['central_directory'] as $i => $file)
			{
				if(!in_array($file['filename'], $directories))
				{
					$directories[] = $file['filename'];
					$fileinfo = array();
					$fileinfo['Filepath'] = $last_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
					$fileinfo['Filename'] = basename($file['filename']);
					if($file['filename'][strlen($file['filename'])-1] == '/')
					{
						$fileinfo['Filetype'] = 'FOLDER';
						$fileinfo['Filesize'] = 0;
						$fileinfo['Compressed'] = 0;
					}
					else
					{
						$fileinfo['Filetype'] = getExt($file['filename']);
						$fileinfo['Filesize'] = $file['uncompressed_size'];
						$fileinfo['Compressed'] = $file['compressed_size'];
					}
					if($fileinfo['Filetype'] === false)
						$fileinfo['Filetype'] = 'FILE';
					$fileinfo['Filemime'] = getMime($file['filename']);
					$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
					
					print 'Adding file in archive: ' . $fileinfo['Filepath'] . "\n";
					$id = $mysql->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo));
				}
				
				// get folders leading up to files
				$paths = split('/', '/' . $file['filename']);
				unset($paths[count($paths)-1]); // remove last item either a file name or empty
				$current = '';
				foreach($paths as $i => $path)
				{
					$current .= $path . DIRECTORY_SEPARATOR;
					if(!in_array(substr($current, 1), $directories))
					{
						$directories[] = substr($current, 1);
						$fileinfo = array();
						$fileinfo['Filepath'] = $last_path . $current;
						$fileinfo['Filename'] = basename($fileinfo['Filepath']);
						$fileinfo['Filetype'] = 'FOLDER';
						$fileinfo['Filesize'] = 0;
						$fileinfo['Compressed'] = 0;
						$fileinfo['Filemime'] = getMime($file['filename']);
						$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
						
						print 'Adding directory in archive: ' . $fileinfo['Filepath'] . "\n";
						$id = $mysql->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo));
					}
				}
			}
		}
		
		// get entire archive information
		$fileinfo = array();
		$fileinfo['Filepath'] = $last_path;
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Compressed'] = $info['filesize'];
		$fileinfo['Filetype'] = getFileType($last_path);
		if(isset($info['zip']) && isset($info['zip']['uncompressed_size']))
			$fileinfo['Filesize'] = $info['zip']['uncompressed_size'];
		else
			$fileinfo['Filesize'] = 0;
		$fileinfo['Filemime'] = getMime($last_path);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));

		// print status
		if( $archive_id != NULL )
		{
			print 'Modifying archive: ' . $fileinfo['Filepath'] . "\n";
			
			// update database
			$id = $mysql->query(array('UPDATE' => 'archive', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding archive: ' . $fileinfo['Filepath'] . "\n";
			
			// add to database
			$id = $mysql->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo));
			
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
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if(is_file($last_path))
		{
			$files = $mysql->query(array('SELECT' => db_archive::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				header('Content-Transfer-Encoding: binary');
				header('Content-Type: ' .  getMime($last_path));
				header('Content-Length: ' . filesize($last_path));
				header('Content-Disposition: attachment; filename="' . basename($last_path) . '"');
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
				
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
			} else { $error = 'File not found!'; }
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
		
		$files = db_file::get($mysql, $request, $count, $error, 'db_archive');
		
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
