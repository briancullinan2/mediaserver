<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_archive()
{
	return array(
		'name' => 'Archive',
		'description' => 'Descend in to archives to enable searching of extra files and compression information.',
		'database' => array(
			'Filepath' 		=> 'TEXT',
			'Filename'		=> 'TEXT',
			'Compressed'	=> 'BIGINT',
			'Filesize'		=> 'BIGINT',
			'Filemime'		=> 'TEXT',
			'Filedate'		=> 'DATETIME ',
			'Filetype'		=> 'TEXT',
		),
		'settings' => array('archiver'),
		'depends on' => array('archiver_installed'),
	);
}

/**
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_archive()
{
	include_once setting('local_root') . 'handlers' . DIRECTORY_SEPARATOR . 'db_file.php';
	
	// include the id handler
	include_once 'File' . DIRECTORY_SEPARATOR . 'Archive.php';
	
}

/**
 * Implementation of handles
 * @ingroup handles
 */
function handles_archive($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
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

/**
 * Helper function
 */
function get_archive_info($file)
{
	parseInner($file, $last_path, $inside_path);
	
	$files = array();
	
	// if using getID3 archive parser
/*		
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if($inside_path != '')
		{
			if(isset($info['zip']) && isset($info['zip']['central_directory']))
			{
				foreach($info['zip']['central_directory'] as $i => $file)
				{
					if($file['filename'] == $inside_path)
					{
						$fileinfo['Filepath'] = $last_path . '/' . $file['filename'];
						$fileinfo['id'] = bin2hex($fileinfo['Filepath']);
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
						break;
					}
				}
			}
		}
*/	
	
	// loop through files
	$source = File_Archive::read($last_path . '/');
	
	if(PEAR::isError($source))
	{
		PEAR::raiseError('Error reading archive: ' . $last_path, E_DEBUG);
		PEAR::raiseError($source, E_DEBUG);
	}
	else
	{
		while($source->next())
		{
			$filepath = $last_path . '/' . trim($source->getFilename());
			
			// if looking for a specific file, skip all other files
			if($inside_path != '' && $filepath != $inside_path)
				continue;
			
			$stat = $source->getStat();
			
			// construct file information
			$fileinfo = array();
			$fileinfo['Filepath'] = addslashes($filepath);
			$fileinfo['Filename'] = basename($filepath);
			$fileinfo['Compressed'] = 0;
			
			// this is how we determine if and internal file is a folder
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
			
			if($inside_path != '')
				return $fileinfo;
			else
				$files[] = $fileinfo;
		}
		
		// if the file was not found before now, return false
		if($inside_path != '')
			return false;
	}
	
	return $files;
}

/**
 * Common helper function for the handler
 */
function add_archive($file, $archive_id = NULL)
{
	// pull information from $info
	parseInner($file, $last_path, $inside_path);
	
	// do a little cleanup here
	// if the archive changes remove all it's inside files from the database
	if( $archive_id != NULL )
	{
		PEAR::raiseError('Removing archive: ' . $file, E_DEBUG);
		remove_archive($last_path . '/');
	}

	// Add archive first so if it fails then it won't try to read it again
	$fileinfo = get_filesystem_info($last_path);
	$fileinfo['Compressed'] = $fileinfo['Filesize'];
	$fileinfo['Filesize'] = 0;
	
	// print status
	if( $archive_id == NULL )
	{
		PEAR::raiseError('Adding archive: ' . $fileinfo['Filepath'], E_DEBUG);
		
		// add to database
		$archive_id = $GLOBALS['database']->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo), false);
	}
	else
	{
		PEAR::raiseError('Modifying archive: ' . $fileinfo['Filepath'], E_DEBUG);
		
		// update database
		$archive_id = $GLOBALS['database']->query(array('UPDATE' => 'archive', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $archive_id), false);
	}
	
	// set up empty ids array since we know archive_id will be the only entry
	$ids = array();
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(!is_wrapper($handler) && !is_internal($handler))
			$ids[$table . '_id'] = false;
	}
	
	// add archive internal files to archive database
	$files = get_archive_info($last_path);
	$total_size = 0;
	foreach($files as $i => $fileinfo)
	{
		PEAR::raiseError('Adding file in archive: ' . stripslashes($fileinfo['Filepath']), E_DEBUG);
		$id = $GLOBALS['database']->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo), false);
		$ids['archive_id'] = $id;
		add_ids(stripslashes($fileinfo['Filepath']), true, $ids);
		$total_size += $fileinfo['Filesize'];
	}
	
	// add root file which is the filepath but with a / for compatibility
	$fileinfo = get_filesystem_info($last_path);
	$fileinfo['Compressed'] = $fileinfo['Filesize'];
	$fileinfo['Filesize'] = $total_size;
	if(substr($fileinfo['Filepath'], -1) != '/') $fileinfo['Filepath'] .= '/';
	
	PEAR::raiseError('Adding file in archive: ' . stripslashes($fileinfo['Filepath']), E_DEBUG);
	$id = $GLOBALS['database']->query(array('INSERT' => 'archive', 'VALUES' => $fileinfo), false);
	
	// add ID for root file
	$ids['archive' . '_id'] = $id;
	add_ids(stripslashes($fileinfo['Filepath']), true, $ids);
	
	// update total size
	$return = $GLOBALS['database']->query(array('UPDATE' => 'archive', 'VALUES' => array('Filesize' => $total_size), 'WHERE' => 'id=' . $archive_id), false);
	
	return $archive_id;
}

/**
 * Implementation of handler_output
 * @ingroup handler_output
 */
function output_archive($file)
{
	$file = str_replace('\\', '/', $file);
	
	if(setting('use_alias') == true)
		$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
	$files = $GLOBALS['database']->query(array('SELECT' => 'archive', 'WHERE' => 'Filepath = "' . addslashes($file) . '"', 'LIMIT' => 1), true);
	if(count($files) > 0)
	{				
		return @fopen($GLOBALS['handlers']['db_archive']['streamer'] . '://' . $file, 'rb');
	}

	return false;
}

/**
 * Implementation of handler_get
 * @ingroup handler_get
 */
function get_archive($request, &$count)
{
	// change the cat to the table we want to use
	$request['cat'] = validate_cat(array('cat' => 'archive'));
	
	// if this module handles the directory, show the files inside the archive
	if(isset($request['dir']) && handles($request['dir'], 'archive'))
	{
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		if(setting('use_alias') == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

		parseInner($request['dir'], $last_path, $inside_path);
		if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
		$request['dir'] = $last_path . $inside_path;

		// make sure the archive exists
		if(!is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
		{
			unset($request['dir']);
			PEAR::raiseError('Directory does not exist!', E_USER);
		}
	}
	
	$files = get_files($request, $count, 'files');
	
	return $files;
}

// music handler
class archive
{	
	// this is for stream stuff when controlling output of the file
       
    protected $internal_fp = NULL;
    protected $internal_length = NULL;
    protected $internal_pos = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, strlen($GLOBALS['handlers']['db_archive']['streamer'] . '://')) == $GLOBALS['handlers']['db_archive']['streamer'] . '://')
			$path = substr($path, strlen($GLOBALS['handlers']['db_archive']['streamer'] . '://'));
			
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
}


