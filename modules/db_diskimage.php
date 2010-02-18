<?php

// music handler
class db_diskimage extends db_file
{
	const DATABASE = 'diskimage';
	
	const NAME = 'Disk Images from Database';
	
	// this is for stream stuff when controlling output of the file
    const PROTOCOL = 'diskimage'; /* Underscore not allowed */
       
    protected $internal_fp = NULL;
    protected $internal_start = NULL;
    protected $internal_length = NULL;
    protected $internal_pos = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, strlen(self::PROTOCOL . '://')) == self::PROTOCOL . '://')
			$path = substr($path, strlen(self::PROTOCOL . '://'));
			
		parseInner(str_replace('/', DIRECTORY_SEPARATOR, $path), $last_path, $inside_path);

		if(is_file($last_path))
		{
			if($inside_path != '')
			{
				$info = $GLOBALS['getID3']->analyze($last_path);
			
				if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
				if(isset($info['iso']) && isset($info['iso']['directories']))
				{
					foreach($info['iso']['directories'] as $i => $directory)
					{
						foreach($directory as $j => $file)
						{
							if($file['filename'] == $inside_path)
							{
								if($fp = @fopen($last_path, 'rb'))
								{
									fseek($fp, $file['offset_bytes']);
									$this->internal_fp = $fp;
									$this->internal_start = $file['offset_bytes'];
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
				if($fp = @fopen($last_path, 'rb'))
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
			$position = $this->internal_length;
		}
		$this->internal_pos = $position;
		fseek($this->internal_fp, $this->internal_start + $this->internal_pos);
        return 0;
    }

	static function init()
	{
		// include the id handler
		require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
		
		// set up id3 reader incase any files need it
		$GLOBALS['getID3'] = new getID3();
	}

	static function columns()
	{
		return array_keys(self::struct());
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'Filename' => 'TEXT',
			'Filemime' => 'TEXT',
			'Filesize' => 'BIGINT',
			'Filedate' => 'DATETIME',
			'Filetype' => 'TEXT',
			'Filepath' => 'TEXT'
		);
	}

	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		// parse through the file path and try to find a zip
		parseInner($file, $last_path, $inside_path);

		switch(getExt($last_path))
		{
			case 'iso':
				return true;
			default:
				return false;
		}
		
		return false;

	}
	
	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		parseInner($file, $last_path, $inside_path);
		
		$file = $last_path;

		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_diskimage = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			// try to get music information
			if( count($db_diskimage) == 0 )
			{
				return self::add($file);
			}
			elseif($force)
			{
				return self::add($file, $db_diskimage[0]['id']);
			}

		}
		return false;
	}
	
	static function add($file, $image_id = NULL)
	{
		if(!isset($GLOBALS['getID3']))
			self::init();
		
		// pull information from $info
		parseInner($file, $last_path, $inside_path);
		
		// do a little cleanup here
		// if the image changes remove all it's inside files from the database
		if( $image_id != NULL )
		{
			log_error('Removing disk image: ' . $file);
			self::remove($last_path . '/', get_class());
		}

		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if(isset($info['iso']) && isset($info['iso']['directories']))
		{
			$ids = array();
			foreach($GLOBALS['tables'] as $i => $table)
			{
				$ids[$table . '_id'] = false;
			}
			$directories = array();
			foreach($info['iso']['directories'] as $i => $directory)
			{
				foreach($directory as $j => $file)
				{
					if(!in_array($file['filename'], $directories))
					{
						$file['filename'] = str_replace('\\', '/', $file['filename']);
						$directories[] = $file['filename'];
						$fileinfo = array();
						$fileinfo['Filepath'] = addslashes($last_path . $file['filename']);
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
						
						log_error('Adding file in disk image: ' . stripslashes($fileinfo['Filepath']));
						$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
						$ids[self::DATABASE . '_id'] = $id;
						db_ids::handle(stripslashes($fileinfo['Filepath']), true, $ids);
					}
				}
			}
		}
		
		$last_path = str_replace('/', DIRECTORY_SEPARATOR, $last_path);
		// get entire image information
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $last_path));
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Filetype'] = getExt($last_path);
		$fileinfo['Filesize'] = filesize($last_path);
		$fileinfo['Filemime'] = getMime($last_path);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));

		// print status
		if( $image_id == NULL )
		{
			log_error('Adding Disk Image: ' . $last_path);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
			
			return $id;
		}
		else
		{
			log_error('Modifying Disk Image: ' . $last_path);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $image_id), false);
		
			return $image_id;
		}
		
	}

	static function out($file)
	{
		$file = str_replace('\\', '/', $file);
		
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
			
		$files = $GLOBALS['database']->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"', 'LIMIT' => 1), true);
		if(count($files) > 0)
		{				
			return @fopen(self::PROTOCOL . '://' . $file, 'rb');
		}

		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		if(isset($request['dir']) && self::handles($request['dir']))
		{
			$request['dir'] = str_replace('\\', '/', $request['dir']);
			if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

			parseInner($request['dir'], $last_path, $inside_path);
			if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
			$request['dir'] = $last_path . $inside_path;
			
			if(!is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
			{
				unset($request['dir']);
				$error = 'Directory does not exist!';
			}
		}
		
		$files = db_file::get($request, $count, $error, get_class());
		
		return $files;
	}
	
	static function remove($file)
	{
		// db_file can handle inside paths
		parent::remove($file, get_class());
	}

	static function cleanup()
	{
		// call default cleanup function
		parent::cleanup(get_class());
	}
}

stream_wrapper_register(
    db_diskimage::PROTOCOL,
   'db_diskimage'
);

?>
