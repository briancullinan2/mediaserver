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

	static function handle($file)
	{
		$file = str_replace('\\', '/', $file);
		
		db_file::parseInner($file, $last_path, $inside_path);
		
		$file = $last_path;

		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_archive = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_archive) == 0 )
			{
				$fileid = self::add($file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $GLOBALS['database']->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = self::add($file, $db_archive[0]['id']);
				}
				
			}

		}
		
	}

	static function add($file, $archive_id = NULL)
	{
		// do a little cleanup here
		// if the archive changes remove all it's inside files from the database
		if( $archive_id != NULL )
		{
			log_error('Removing archive: ' . $file);
			$GLOBALS['database']->query(array('DELETE' => self::DATABASE, 'WHERE' => 'LEFT(Filepath, ' . (strlen($file)+1) . ') = "' . addslashes($file) . '/" AND (LOCATE("/", Filepath, ' . (strlen($file)+2) . ') = 0 OR LOCATE("/", Filepath, ' . (strlen($file)+2) . ') = LENGTH(Filepath))'));
		}

		// pull information from $info
		db_file::parseInner($file, $last_path, $inside_path);
		
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if(isset($info['zip']) && isset($info['zip']['central_directory']))
		{
			$directories = array();
			foreach($info['zip']['central_directory'] as $i => $file)
			{
				if(!in_array($file['filename'], $directories))
				{
					$file['filename'] = str_replace('\\', '/', $file['filename']);
					$directories[] = $file['filename'];
					$fileinfo = array();
					$fileinfo['Filepath'] = addslashes($last_path . '/' . $file['filename']);
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
					
					log_error('Adding file in archive: ' . $fileinfo['Filepath']);
					$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
				}
				
				// get folders leading up to files
				$paths = split('/', '/' . $file['filename']);
				unset($paths[count($paths)-1]); // remove last item either a file name or empty
				$current = '';
				foreach($paths as $i => $path)
				{
					$current .= $path . '/';
					if(!in_array(substr($current, 1), $directories))
					{
						$directories[] = substr($current, 1);
						$fileinfo = array();
						$fileinfo['Filepath'] = addslashes($last_path . $current);
						$fileinfo['Filename'] = basename($fileinfo['Filepath']);
						$fileinfo['Filetype'] = 'FOLDER';
						$fileinfo['Filesize'] = 0;
						$fileinfo['Compressed'] = 0;
						$fileinfo['Filemime'] = getMime($file['filename']);
						$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
						
						log_error('Adding directory in archive: ' . $fileinfo['Filepath']);
						$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
					}
				}
			}
		}
		
		$last_path = str_replace('/', DIRECTORY_SEPARATOR, $last_path);
		// get entire archive information
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $last_path));
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Compressed'] = filesize($last_path);
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
			log_error('Modifying archive: ' . $fileinfo['Filepath']);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id));
		
			return $audio_id;
		}
		else
		{
			log_error('Adding archive: ' . $fileinfo['Filepath']);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
		
	}

	static function out($file)
	{
		$file = str_replace('\\', '/', $file);
		
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
			
		db_file::parseInner($file, $last_path, $inside_path);

		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
		{
			return db_file::out($last_path);
		}

		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		if(isset($request['dir']))
		{
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

			db_file::parseInner($request['dir'], $last_path, $inside_path);
			if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
			$request['dir'] = $last_path . $inside_path;
			
			if(!is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
			{
				unset($_REQUEST['dir']);
				$error = 'Directory does not exist!';
			}
		}
		
		$files = db_file::get($request, $count, $error, 'db_archive');
		
		return $files;
	}

	static function cleanup()
	{
		// call default cleanup function
		parent::cleanup(get_class());
	}
}

?>
