<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'File' . DIRECTORY_SEPARATOR . 'Archive.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_archive extends db_file
{
	const DATABASE = 'archive';
	
	const NAME = 'Archives from Database';
	
	// this is for stream stuff when controlling output of the file
    const PROTOCOL = 'archive'; /* Underscore not allowed */
       
    protected $internal_fp = NULL;
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
				$this->internal_fp = File_Archive::read($last_path . '/' . $inside_path);
				return true;
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
		if(is_resource($this->internal_fp))
		{
			if($this->internal_pos + $count > $this->internal_length)
				$count = $this->internal_length - $this->internal_pos;
			$this->internal_pos += $count;
			return fread($this->internal_fp, $count);
		}
		elseif(is_object($this->internal_fp))
		{
			return $this->internal_fp->getData($count);
		}
    }
    function stream_eof(){
		if(is_resource($this->internal_fp))
		{
			return $this->internal_pos >= $this->internal_length;
		}
		elseif(is_object($this->internal_fp))
		{
			$stat = $this->internal_fp->getStat();
			return ($this->internal_fp->tell() >= $stat[7]);
		}
    }
    function stream_tell(){
		if(is_resource($this->internal_fp))
		{
			return $this->internal_pos;
		}
		elseif(is_object($this->internal_fp))
		{
			return $this->internal_fp->tell();
		}
    }
    function stream_seek($position){
		if(is_resource($this->internal_fp))
		{
			if($position > $this->internal_length)
			{
				$position = $this->internal_length;
			}
			$this->internal_pos = $position;
			fseek($this->internal_fp, $this->internal_pos);
			return 0;
		}
		elseif(is_object($this->internal_fp))
		{
			$stat = $this->internal_fp->getStat();
			if($position > $stat[7])
			{
				$position = $stat[7];
			}
			$this->internal_fp->skip($position);
		}
    }

	static function columns()
	{
		return array_keys(self::struct());
	}
	
	static function struct()
	{
		return array(
			'Filepath' 		=> 'TEXT',
			'Filename'		=> 'TEXT',
			'Compressed'	=> 'BIGINT',
			'Filesize'		=> 'BIGINT',
			'Filemime'		=> 'TEXT',
			'Filedate'		=> 'DATETIME ',
			'Filetype'		=> 'TEXT',
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
			case 'zip':
			case 'rar':
			case 'tgz':
			case 'gz':
			case 'bz2':
			case 'tbz':
			case 'ar':
			case 'ark':
			case 'deb':
			case 'szip':
			case 'tar':
			case '7z':
				return true;
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
			$db_archive = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			// try to get music information
			if( count($db_archive) == 0 )
			{
				return self::add($file);
			}
			elseif($force)
			{
				return self::add($file, $db_archive[0]['id']);
			}

		}
		return false;
	}

	static function add($file, $archive_id = NULL)
	{
		// pull information from $info
		parseInner($file, $last_path, $inside_path);
		
		// do a little cleanup here
		// if the archive changes remove all it's inside files from the database
		if( $archive_id != NULL )
		{
			log_error('Removing archive: ' . $file);
			self::remove($last_path . '/', get_class());
		}
		
		// set up empty ids array since we know archive_id will be the only entry
		$ids = array();
		foreach($GLOBALS['tables'] as $i => $table)
		{
			$ids[$table . '_id'] = false;
		}
		
		// loop through files
		$source = File_Archive::read($last_path . '/');
		$total_size = 0;
		if(PEAR::isError($source))
		{
			log_error('Error reading archive: ' . $last_path);
			log_error($source);
		}
		else
		{
			while($source->next())
			{
				$stat = $source->getStat();
				$fileinfo = array();
				$fileinfo['Filepath'] = addslashes($last_path . '/' . trim($source->getFilename()));
				$fileinfo['Filename'] = basename($source->getFilename());
				$fileinfo['Compressed'] = 0;
				if($fileinfo['Filepath'][strlen($fileinfo['Filepath'])-1] == '/')
				{
					$fileinfo['Filetype'] = 'FOLDER';
					$fileinfo['Filesize'] = 0;
				}
				else
				{
					$fileinfo['Filetype'] = getExt($fileinfo['Filename']);
					$fileinfo['Filesize'] = @$stat['size'];
				}
				if($fileinfo['Filetype'] === false)
					$fileinfo['Filetype'] = 'FILE';
				else
					$fileinfo['Filetype'] = strtoupper($fileinfo['Filetype']);
					
				$fileinfo['Filemime'] = @$source->getMime();
				$fileinfo['Filedate'] = date("Y-m-d h:i:s", @$stat['mtime']);
				
				$total_size += $fileinfo['Filesize'];
				
				log_error('Adding file in archive: ' . stripslashes($fileinfo['Filepath']));
				$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
				$ids[self::DATABASE . '_id'] = $id;
				db_ids::handle(stripslashes($fileinfo['Filepath']), true, $ids);
			}
		}
		
		$last_path = str_replace('/', DIRECTORY_SEPARATOR, $last_path);
		// get entire archive information
		$fileinfo = array();
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Compressed'] = filesize($last_path);
		$fileinfo['Filetype'] = 'FOLDER';
		$fileinfo['Filesize'] = $total_size;
		$fileinfo['Filemime'] = getMime($last_path);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));
		
		// add root file which is the filepath but with a / for compatibility
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $last_path . '/'));
		log_error('Adding file in archive: ' . stripslashes($fileinfo['Filepath']));
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
		$ids[self::DATABASE . '_id'] = $id;
		db_ids::handle(stripslashes($fileinfo['Filepath']), true, $ids);

		// use same information for actual file, but change these back
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $last_path));
		$fileinfo['Filetype'] = getFileType($last_path);
		
		// print status
		if( $archive_id == NULL )
		{
			log_error('Adding archive: ' . $fileinfo['Filepath']);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
			
			return $id;
		}
		else
		{
			log_error('Modifying archive: ' . $fileinfo['Filepath']);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id), false);
		
			return $archive_id;
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
    db_archive::PROTOCOL,
   'db_archive'
);

?>
