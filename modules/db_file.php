<?php

// the basic file type

class db_file
{
	// most of these methods should just be static, no need to intantiate the class
	// just good for organization purposes
	const DATABASE = 'files';
	
	const NAME = 'File';
	
	// this function specifies the level of detail for the array of file info, ORDER matters!
	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Filedate', 'Filetype', 'Filepath');
	}

	
	// return whether or not this module handles trhe specified type of file
	static function handles($file)
	{
		if(is_dir($file) || is_file($file))
			return true;
		else
			return false;
	}
	
	// this function determines if the file qualifies for this type and handles it according
	static function handle($mysql, $file)
	{
		// files always qualify, we are going to log every single one!
		
		if(db_file::handles($file))
		{
			
			// check if it is in the database
			$db_file = $mysql->get('files', 
				array(
					'SELECT' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				$id = db_file::add($mysql, $file);
			}
			else
			{
				$filename = $file;

				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($filename)) != $db_file[0]['Filedate'] )
				{
					$id = db_file::add($mysql, $file, $db_file[0]['id']);
				}
				else
				{
					print 'Skipping file: ' . $file . "\n";
				}
				
			}
			
		}
		
	}
	
	static function getInfo($file)
	{
		// get file extension
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
		$fileinfo['Filename'] = basename($file);
		$fileinfo['Filesize'] = filesize($file);
		$fileinfo['Filemime'] = getMime($file);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
		$fileinfo['Filetype'] = getFileType($file);
		
		return $fileinfo;
	}
	
	// add a given file to the given database
	// returns the id of the entry
	// if the id is given then it updates the entry
	static function add($mysql, $file, $id = NULL)
	{
	
		// get file extension
		$fileinfo = db_file::getInfo($file);
			
		// if the id is set then we are updating and entry
		if( $id != NULL )
		{
			print 'Modifying file: ' . $file . "\n";
			
			// update database
			$fileid = $mysql->set('files', $fileinfo, array('id' => $id));
		
			return $id;
		}
		else
		{
			print 'Adding file: ' . $file . "\n";
			
			// add to database
			$fileid = $mysql->set('files', $fileinfo);
		
			return $fileid;
		}
		
		
		flush();
		
	}
	
	// output provided file to given stream
	static function out($mysql, $file, $stream)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			$files = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				$file = $files[0];
				header('Content-Transfer-Encoding: binary');
				header('Content-Type: ' . $file['Filemime']);
				header('Content-Length: ' . $file['Filesize']);
				header('Content-Disposition: attachment; filename="' . $file['Filename'] . '"');
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
				
				if($op !== false)
				{
					if($fp = fopen($files[0]['Filepath'], 'rb'))
					{
						while (!feof($fp)) {
							fwrite($op, fread($fp, BUFFER_SIZE));
						}				
						fclose($fp);
						return true;
					}
				}
			}
		}
		return false;
	}
	
	
	// the mysql can be left null to get the files from a directory, in which case a directory must be specified
	// if the mysql is provided, then the file listings will be loaded from the database
	static function get($mysql, $props)
	{
		$files = array();
		
		if($mysql == NULL)
		{
			if(!isset($props['DIR']))
				$props['DIR'] = realpath('/');
			if (is_dir($props['DIR']))
			{
				// if we are over 5.0 use the scandir method
				if( version_compare(phpversion(), "5.0.0") >= 0 )
				{
					$tmp_files = scandir($props['DIR']);
					foreach($tmp_files as $i => $file)
					{
						$files[$i] = db_file::getInfo($props['DIR'] . $file);
					}
					return $files;
				}
				else
				{
					// create file array
					if ($dh = opendir($props['DIR']))
					{
						while (($file = readdir($dh)) !== false)
						{
							$files[] = db_file::getInfo($props['DIR'] . $file);
						}
						closedir($dh);
					}
					
				}
				
			}
		}
		else
		{
			// construct where statement
			if(isset($props['DIR']) && !isset($props['WHERE']))
			{
				$props['WHERE'] = 'Filepath REGEXP "^' . addslashes(addslashes($dir)) . '"';
			}
		
			$props['SELECT'] = db_file::columns();
		
			// get directory from database
			$files = $mysql->get(db_file::DATABASE, $props);
		}
			
		return $files;
	}
	
	// callback for the sql file list query
	static function cleanup_remove($row, $args)
	{
		if( !file_exists($row['Filepath']) )
		{
			// remove row from database
			$args['SQL']->set($args['DB'], NULL, array('Filepath' => addslashes($row['Filepath'])));
			
			print 'Removing ' . $args['DB'] . ': ' . $row['Filepath'] . "\n";
		}
	}
	
	
	// cleanup the non-existant files
	static function cleanup($mysql, $watched, $ignored, $database = NULL)
	{
		if( $database == NULL )
		{
			$db = constant(get_class() . '::DATABASE');
		}
		else
		{
			$db = $database;
		}
	
		// first clear all the items that are no longer in the watch list
		// since the watch is resolved all the items in watch have to start with the watched path
		$where_str = '';
		foreach($watched as $i => $watch)
		{
			// add the files that begin with a path from a watch directory
			$where_str .= ' Filepath REGEXP "^' . addslashes(addslashes($watch['Filepath'])) . '" OR';
		}
		// but keep the ones leading up to watched directories
		// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
		for($i = 0; $i < count($watched); $i++)
		{
			$folders = split(addslashes(DIRECTORY_SEPARATOR), $watched[$i]['Filepath']);
			$curr_dir = (realpath('/') == '/')?'/':'';
			// don't add the watch directory here because it is already added by the previous loop!
			$length = count($folders);
			unset($folders[$length-1]); // remove the blank at the end
			unset($folders[$length-2]); // remove the last folder which is the watch
			$between = false; // directory must be between an aliased path and a watched path
			// add the directories leading up to the watch
			for($j = 0; $j < count($folders); $j++)
			{
				if($folders[$j] != '')
				{
					$curr_dir .= $folders[$j] . DIRECTORY_SEPARATOR;
					// if using aliases then only add the revert from the watch directory to the alias
					// ex. Watch = /home/share/Pictures/, Alias = /home/share/ => /Shared/
					//     only /home/share/ is added here
					if(!USE_ALIAS || in_array($curr_dir, $GLOBALS['paths']) !== false)
					{
						// this allows for us to make sure that at least the beginning 
						//   of the path is an aliased path
						$between = true;
						// if the USE_ALIAS is true this will only add the folder
						//    if it is in the list of aliases
						$where_str .= ' Filepath = "' . addslashes($curr_dir) . '" OR';
					}
					// but make an exception for folders between an alias and the watch path
					elseif(USE_ALIAS && $between)
					{
						$where_str .= ' Filepath = "' . addslashes($curr_dir) . '" OR';
					}
				}
			}
		}
		// remove last OR
		$where_str = substr($where_str, 0, strlen($where_str)-2);
		$where_str = ' !(' . $where_str . ')';
		// clean up items that are in the ignore list
		foreach($ignored as $i => $ignore)
		{
			$where_str = 'Filepath REGEXP "^' . addslashes(addslashes($ignore)) . '" OR ' . $where_str;
		}
		
		// remove items
		$mysql->set($db, NULL, $where_str);

		// since all the ones not apart of a watched directory is removed, now just check is every file still in the database exists on disk
		$mysql->query('SELECT Filepath FROM ' . $mysql->table_prefix . $db);
		
		$mysql->result_callback(array('db_file', 'cleanup_remove'), array('SQL' => new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME), 'DB' => $db));
				
		// remove any duplicates
		$files = $mysql->get($db,
			array(
				'SELECT' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
				'OTHER' => 'GROUP BY Filepath HAVING num > 1'
			)
		);
		
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			$mysql->set($db, NULL, array('id' => $file['id']));
			
			print 'Removing ' . $db . ': ' . $file['Filepath'] . "\n";
		}
		
		print 'Cleanup for ' . (($database==NULL)?'files':$database) . " complete.\n";
		flush();
		
	}
	
}

?>