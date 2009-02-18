<?php

// the basic file type

class db_file
{
	// most of these methods should just be static, no need to intantiate the class
	// just good for organization purposes
	const DATABASE = 'files';
	
	const NAME = 'Files from Database';
	
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
			$db_file = $mysql->get(array(
					'TABLE' => db_file::DATABASE,
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
			$files = $mysql->get(array('TABLE' => db_file::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
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
						fclose($op);
						return true;
					}
				}
			}
		}
		return false;
	}
	
	
	// the mysql can be left null to get the files from a directory, in which case a directory must be specified
	// if the mysql is provided, then the file listings will be loaded from the database
	static function get($mysql, $request, &$count, &$error, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
		}
		
		$files = array();
		
		if(USE_DATABASE)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( !isset($request['order_by']) || !in_array($request['order_by'], call_user_func($module . '::columns')) )
				$request['order_by'] = 'Filepath';
			if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
				$request['direction'] = 'ASC';
			if( isset($request['id']) )
				$request['item'] = $request['id'];
			if( isset($request['group_by']) && !in_array($request['group_by'], call_user_func($module . '::columns')) )
				unset($request['group_by']);
			getIDsFromRequest($request, $request['selected']);

			$props = array();
			if(isset($request['group_by'])) $props['GROUP'] = $request['group_by'];
			$props['ORDER'] = $request['order_by'] . ' ' . $request['direction'];
			$props['LIMIT'] = $request['start'] . ',' . $request['limit'];

			// select an array of ids!
			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = '';
				// compile where statement for either numeric id or encoded path
				foreach($request['selected'] as $i => $id)
				{
					if(is_numeric($id))
					{
						$props['WHERE'] .= ' id=' . $id . ' OR';
					}
					else
					{
						// unpack encoded path and add it to where
						$props['WHERE'] .= ' Filepath="' . addslashes(pack('H*', $id)) . '" OR';
					}
				}
				// remove last or
				$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);

				// selected items have priority over all the other options!
				unset($props['LIMIT']);
				unset($request);
			}

			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				$columns = call_user_func($module . '::columns');
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
					$dirs = $mysql->get(array('TABLE' => db_file::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
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
			
			// add file filter to where - this is mostly for internal use
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
		
			if($error == '')
			{
				$props['SELECT'] =  call_user_func($module . '::columns');
				if(isset($props['GROUP'])) $props['SELECT'][] = 'count(*)';
				$props['TABLE'] = constant($module . '::DATABASE');
	
				// get directory from database
				$files = $mysql->get($props);
				
				// this is how we get the count of all the items
				unset($props['LIMIT']);
				$props = array('FROM' => '(' . $mysql->statement_builder($props) . ') AS db_audio');
				$props['SELECT'] = 'count(*)';
				
				$result = $mysql->get($props);
				
				$count = intval($result[0]['count(*)']);
			}
			else
			{
				$count = 0;
				$files = array();
			}
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

		$args['count']++;
		if(round(($args['count']-1)/$args['total'], 2) != round($args['count']/$args['total'], 2))
		{
			print 'Checking paths ' . (round($args['count']/$args['total'], 2) * 100) . '% complete for ' . $args['DB'] . "\n";
			flush();
		}
	}
	
	
	// cleanup the non-existant files
	static function cleanup($mysql, $watched, $ignored, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
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
		$mysql->set(constant($module . '::DATABASE'), NULL, $where_str);

		// get count 
		$result = $mysql->get(array('TABLE' => constant($module . '::DATABASE'), 'SELECT' => 'count(*)'));
		$total = $result[0]['count(*)'];
		$count = 0;

		// since all the ones not apart of a watched directory is removed, now just check is every file still in the database exists on disk
		$mysql->query('SELECT Filepath FROM ' . $mysql->table_prefix . constant($module . '::DATABASE'));
		
		$mysql->result_callback('db_file::cleanup_remove', array('SQL' => new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME), 'DB' => constant($module . '::DATABASE'), 'count' => &$count, 'total' => &$total));
				
		// remove any duplicates
		$files = $mysql->get(array(
				'TABLE' => constant($module . '::DATABASE'),
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