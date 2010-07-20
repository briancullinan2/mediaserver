<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_diskimage()
{
	return array(
		'name' => 'Disk Image',
		'description' => 'Read ISO disk images.',
		'database' => array(
			'Filename' => 'TEXT',
			'Filemime' => 'TEXT',
			'Filesize' => 'BIGINT',
			'Filedate' => 'DATETIME',
			'Filetype' => 'TEXT',
			'Filepath' => 'TEXT'
		),
		'depends on' => array('getid3_installed'),
	);
}

/** 
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_diskimage()
{
	if(isset($GLOBALS['getID3']))
		return;
		
	// include the id handler
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
	
	// set up id3 reader incase any files need it
	$GLOBALS['getID3'] = new getID3();
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_diskimage($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
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

/** 
 * Implementation of handle
 * @ingroup handle
 */
function add_diskimage($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	parseInner($file, $last_path, $inside_path);
	
	$file = $last_path;

	if(handles($file, 'diskimage'))
	{
		// check to see if it is in the database
		$db_diskimage = $GLOBALS['database']->query(array(
				'SELECT' => 'diskimage',
				'COLUMNS' => 'id',
				'WHERE' => 'Filepath = "' . addslashes($file) . '"',
				'LIMIT' => 1
			)
		, false);
		
		// try to get music information
		if( count($db_diskimage) == 0 )
		{
			return diskimage_add($file);
		}
		elseif($force)
		{
			return diskimage_add($file, $db_diskimage[0]['id']);
		}

	}
	return false;
}

function get_diskimage_info($file)
{
	parseInner($file, $last_path, $inside_path);
	
	$files = array();
	
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
					$file['filename'] = str_replace('\\', '/', $file['filename']);
					$directories[] = $file['filename'];
					$filepath = $last_path . $file['filename'];
					
					// if looking for a specific file, skip all other files
					if($inside_path != '' && $filepath != $inside_path)
						continue;
					
					// construct file information
					$fileinfo = array();
					$fileinfo['Filepath'] = addslashes($filepath);
					$fileinfo['Filename'] = basename($filepath);
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
			}
		}
		
		// if the file was not found before now, return false
		if($inside_path != '')
			return false;
	}
	
	return $files;
}

/** 
 * Helper function
 */
function diskimage_add($file, $image_id = NULL)
{
	// pull information from $info
	parseInner($file, $last_path, $inside_path);
	
	// do a little cleanup here
	// if the image changes remove all it's inside files from the database
	if( $image_id != NULL )
	{
		raise_error('Removing disk image: ' . $file, E_DEBUG);
		remove($last_path . '/', 'diskimage');
	}

	// Add diskimage first so if it fails then it won't try to read it again
	$fileinfo = get_files_info($last_path);
	
	// print status
	if( $image_id == NULL )
	{
		raise_error('Adding diskimage: ' . $fileinfo['Filepath'], E_DEBUG);
		
		// add to database
		$image_id = $GLOBALS['database']->query(array('INSERT' => 'diskimage', 'VALUES' => $fileinfo), false);
	}
	else
	{
		raise_error('Modifying diskimage: ' . $fileinfo['Filepath'], E_DEBUG);
		
		// update database
		$image_id = $GLOBALS['database']->query(array('UPDATE' => 'diskimage', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $image_id), false);
	}

	// set up empty ids array since we know archive_id will be the only entry
	$ids = array();
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(!is_wrapper($handler) && !is_internal($handler))
			$ids[$handler . '_id'] = false;
	}
	
	// add archive internal files to archive database
	$files = get_archive_info($last_path);
	foreach($files as $i => $fileinfo)
	{
		raise_error('Adding file in disk image: ' . stripslashes($fileinfo['Filepath']), E_DEBUG);
		$id = $GLOBALS['database']->query(array('INSERT' => 'diskimage', 'VALUES' => $fileinfo), false);
		$ids['diskimage_id'] = $id;
		add_ids(stripslashes($fileinfo['Filepath']), true, $ids);
	}
	
	// add root file which is the filepath but with a / for compatibility
	$fileinfo = get_files_info($last_path);
	if(substr($fileinfo['Filepath'], -1) != '/') $fileinfo['Filepath'] .= '/';
	
	raise_error('Adding file in diskimage: ' . stripslashes($fileinfo['Filepath']), E_DEBUG);
	$id = $GLOBALS['database']->query(array('INSERT' => 'diskimage', 'VALUES' => $fileinfo), false);

	return $image_id;
}

/** 
 * Implementation of output_handler
 * @ingroup output_handler
 */
function output_diskimage($file)
{
	$file = str_replace('\\', '/', $file);
	
	if(setting('admin_alias_enable') == true)
		$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
	$files = $GLOBALS['database']->query(array('SELECT' => 'diskimage', 'WHERE' => 'Filepath = "' . addslashes($file) . '"', 'LIMIT' => 1), true);
	if(count($files) > 0)
	{				
		return @fopen($GLOBALS['handlers']['diskimage']['streamer'] . '://' . $file, 'rb');
	}

	return false;
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_diskimage($request, &$count)
{
	// change the cat to the table we want to use
	$request['cat'] = validate(array('cat' => 'diskimage'), 'cat');
	
	if(isset($request['dir']) && handles($request['dir'], 'diskimage'))
	{
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		if(setting('admin_alias_enable') == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

		parseInner($request['dir'], $last_path, $inside_path);
		if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = '/' . $inside_path;
		$request['dir'] = $last_path . $inside_path;
		
		if(!is_file(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
		{
			unset($request['dir']);
			raise_error('Directory does not exist!', E_USER);
		}
	}
	
	$files = get_files($request, $count, 'files');
	
	return $files;
}

// ISO handler
class diskimage
{
	
	// this is for stream stuff when controlling output of the file
       
    protected $internal_fp = NULL;
    protected $internal_start = NULL;
    protected $internal_length = NULL;
    protected $internal_pos = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, strlen($GLOBALS['handlers']['db_diskimage']['streamer'] . '://')) == $GLOBALS['handlers']['db_diskimage']['streamer'] . '://')
			$path = substr($path, strlen($GLOBALS['handlers']['db_diskimage']['streamer'] . '://'));
			
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
}
