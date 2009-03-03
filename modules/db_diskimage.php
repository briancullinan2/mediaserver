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
	
	// this is for stream stuff when controlling output of the file
    const PROTOCOL = 'diskimage'; /* Underscore not allowed */
       
    protected $internal_fp  = NULL;
    protected $internal_length  = NULL;
    protected $internal_pos  = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, 12) == 'diskimage://')
			$path = substr($path, 12);
			
		$paths = split('\\' . DIRECTORY_SEPARATOR, $path);
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
		$inside_path = substr($path, strlen($last_path));
		$inside_path = str_replace(DIRECTORY_SEPARATOR, '/', $inside_path);
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if(is_file($last_path))
		{
	
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
								if($fp = fopen($last_path, 'rb'))
								{
									fseek($fp, $file['offset_bytes']);
									$this->internal_fp = $fp;
									$this->internal_length = $file['filesize'];
									$this->internal_pos = 0;
									
									return true;
								}
							}
						}
					}
				}
			}
			// download entire image
			else
			{
				if($fp = fopen($last_path, 'rb'))
				{
					$this->internal_fp = $fp;
					$this->internal_length = filesize($last_path);
					$this->internal_pos = 0;
					return true;
				}
			}
		}
		return false;
    }
    function stream_read($count){
		if($this->internal_pos + $count > $this->internal_length)
			$count = $this->internal_length - $this->internal_pos;
		$this->internal_pos += $count;
        return fread($this->internal_fp, $count);
    }
    function stream_eof(){
        return $this->internal_pos >= $this->internal_length;
    }
    function stream_tell(){
        return $this->internal_pos;
    }
    function stream_seek($position){
		if($position > $this->internal_length)
		{
			$this->internal_pos = $this->internal_length;
			return 0;
		}
		$this->internal_pos = $position;
        return 0;
    }

	static function columns()
	{
		return array('id', 'Offset', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath');
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
	
	static function handle($database, $file)
	{
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['HARD']['alias_regexp'], $GLOBALS['HARD']['paths'], $file);
		
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

		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_diskimage = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_diskimage) == 0 )
			{
				$fileid = self::add($database, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $database->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = self::add($database, $file, $db_diskimage[0]['id']);
				}
				
			}

		}
		
	}
	
	static function add($database, $file, $image_id = NULL)
	{
		// do a little cleanup here
		// if the image changes remove all it's inside files from the database
		if( $image_id != NULL )
		{
			log_error('Removing disk image: ' . $file);
			$database->query(array('DELETE' => self::DATABASE, 'WHERE' => 'LEFT(Filepath, ' . (strlen($file)+1) . ') = "' . addslashes($file) . addslashes(DIRECTORY_SEPARATOR) . '" AND (LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($file)+2) . ') = 0 OR LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($file)+2) . ') = LENGTH(Filepath))'));
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
						
						log_error('Adding file in disk image: ' . $fileinfo['Filepath']);
						$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
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
			log_error('Modifying Disk Image: ' . $fileinfo['Filepath']);
			
			// update database
			$id = $database->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id));
		
			return $audio_id;
		}
		else
		{
			log_error('Adding Disk Image: ' . $fileinfo['Filepath']);
			
			// add to database
			$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
		
	}

	static function out($database, $file)
	{
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $file);
			
		$files = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
		if(count($files) > 0)
		{				
			return fopen(self::PROTOCOL . '://' . $file, 'rb');
		}

		return false;
	}
	
	static function get($database, $request, &$count, &$error)
	{
		if(isset($request['dir']))
		{
			if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $request['dir']);

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
		
		$files = db_file::get($database, $request, $count, $error, get_class());
		
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
			$args['CONNECTION']->query(array('DELETE' => constant($args['MODULE'] . '::DATABASE'), 'WHERE' => 'LEFT(Filepath, ' . strlen($dir) . ') = "' . addslashes($dir) . '" AND (LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($dir)+1) . ') = 0 OR LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($dir)+1) . ') = LENGTH(Filepath))'));
			
			log_error('Removing ' . constant($args['MODULE'] . '::NAME') . ': ' . $row['Filepath']);
		}
	}

	static function cleanup($database, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($database, $watched, $ignored, get_class());
	}
}

stream_wrapper_register(
    db_diskimage::PROTOCOL,
   'db_diskimage'
);

?>