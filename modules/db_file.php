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
		{
			$filename = basename($file);

			// make sure it isn't a hidden file
			if($filename[0] != '.')
				return true;
			else
				return false;
		} else {
			return false;
		}
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
		// get file information
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
	static function get($mysql, $request, &$count, &$error)
	{
		// do validation! for the fields we use
		if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
			$request['start'] = 0;
		if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
			$request['limit'] = 15;
		if( !isset($request['order_by']) || !in_array($request['order_by'], db_file::columns()) )
			$request['order_by'] = 'Filepath';
		if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
			$request['direction'] = 'ASC';
		if( isset($request['id']) )
			$request['item'] = $request['id'];
		getIDsFromRequest($request, $request['selected']);

		$files = array();
		
		if($mysql == NULL)
		{
			if(isset($request['file']))
			{
			}
			else
			{
				// set a directory is one isn't set already
				if(!isset($request['dir']))
					$request['dir'] = realpath('/');
				// check to make sure is it a valid directory before continuing
				if (is_dir($request['dir']))
				{
					// scandir - read in a list of the directory content
					$tmp_files = scandir($request['dir']);
					$count = count($tmp_files);
					// parse out all the files that this module doesn't handle, just like a filter
					for($j = 0; $j < $count; $j++)
						if(!db_file::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					// get the values again, this will reset all the indices
					$tmp_files = array_values($tmp_files);
					// set the count to the total length of the file list
					$count = count($tmp_files);
					// start the information getting and combining of file info
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						// get the information from the module for 1 file
						$info = db_file::getInfo($request['dir'] . $tmp_files[$i]);
						// make some modifications
						$info['Filepath'] = stripslashes($info['Filepath']);
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
						// set the informations in the total list of files
						$files[] = $info;
					}
					return $files;
				}
				else{ $error = 'Directory does not exist!'; return false; }
			}
		}
		else
		{
			$props = array();
			
			$props['OTHER'] = ' ORDER BY ' . $request['order_by'] . ' ' . $request['direction'] . ' LIMIT ' . $request['start'] . ',' . $request['limit'];
			
			// select an array of ids!
			if( isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = 'id=' . join(' OR id=', $selected);
				unset($props['OTHER']);
			}

			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				// add a regular expression matching for each column in the table being searched
				$props['WHERE'] .= '(';
				foreach($columns as $i => $column)
				{
					$columns[$i] .= ' REGEXP "' . $regexp . '"';
				}
				$props['WHERE'] .= join(' OR ', $columns) . ')';
			}
			
			// add dir filter to where
			if(isset($request['dir']))
			{
				if($request['dir'] == '') $request['dir'] = DIRECTORY_SEPARATOR;
				// this is necissary for dealing with windows and cross platform queries coming from templates
				//  yes: the template should probably handle this by itself, but this is convenient and easy
				//   it is purely for making all the paths look prettier
				if($request['dir'][0] == '/' || $request['dir'][0] == '\\') $request['dir'] = realpath('/') . substr($request['dir'], 1);
				
				// replace aliased path with actual path
				if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
				
				// only search for file if is valid dir
				if(realpath($request['dir']) !== false && is_dir(realpath($request['dir'])))
				{
					// make sure directory is in the database
					$dirs = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
					// top level directory / should always exist
					if($request['dir'] == realpath('/') || count($dirs) > 0)
					{
						if(!isset($props['WHERE'])) $props['WHERE'] = '';
						elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
						
						// if the includes is blank then only show files from current directory
						if(!isset($request['includes']))
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '?$"';
						}
						// show all results underneath directory
						else
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '([^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ')*$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '"';
						}
					}
					else
					{
						$error = 'Directory does not exist!';
					}
				}
				else
				{
					$error = 'Directory does not exist!';
				}
			}
			
			// add file filter to where
			if(isset($request['file']))
			{
				// this is necissary for dealing with windows and cross platform queries coming from templates
				if($request['file'][0] == '/' || $request['file'][0] == '\\') $request['file'] = realpath('/') . substr($request['file'], 1);

				// replace aliased path with actual path
				if(USE_ALIAS == true) $request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
				
				// only search for file if is a valid file
				if(realpath($request['file']) !== false && is_file(realpath($request['file'])))
				{
					if(!isset($props['WHERE'])) $props['WHERE'] = '';
					elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
					
					// add file to where
					$props['WHERE'] .= ' Filepath = "' . addslashes($request['file']) . '"';
				}
				// file does no exist
				else
				{
					$error = 'File does not exist!';
				}
			}
		
			$props['SELECT'] = db_file::columns();
		
			// get directory from database
			$files = $mysql->get(db_file::DATABASE, $props);
			
			// this is how we get the count of all the items
			unset($props['OTHER']);
			$props['SELECT'] = 'count(*)';
			
			$result = $mysql->get(db_file::DATABASE, $props);
			
			$count = intval($result[0]['count(*)']);
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