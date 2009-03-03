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
		// since this function is called from plugins and used internally path replacements must be done for Hard aliases and Soft aliases
		//  this is only needed if the file is required to physically exist on disk, basically if is_dir(), is_file(), or file_exists() is used in this function
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['ALL']['alias_regexp'], $GLOBALS['ALL']['paths'], $file);
		
		// check it exists
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
	static function handle($database, $file)
	{
		// replace aliased path incase the handling is coming from across the network
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['HARD']['alias_regexp'], $GLOBALS['HARD']['paths'], $file);
		
		// files always qualify, we are going to log every single one!
		if(self::handles($file))
		{
			
			// check if it is in the database
			$db_file = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				$id = self::add($database, $file);
			}
			else
			{
				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = self::add($database, $file, $db_file[0]['id']);
				}
				else
				{
					log_error('Skipping file: ' . $file);
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
	static function add($database, $file, $id = NULL)
	{
		// get file information
		$fileinfo = self::getInfo($file);
			
		// if the id is set then we are updating and entry
		if( $id != NULL )
		{
			log_error('Modifying file: ' . $file);
			
			// update database
			$id = $database->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $id));
		
			return $id;
		}
		else
		{
			log_error('Adding file: ' . $file);
			
			// add to database
			$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
			return $id;
		}
		
	}
	
	// output provided file to given stream
	//  no headers is used to prevent changing the headers, if it is called by a plugin it may just need the stream and no header changes
	static function out($database, $file)
	{
		// aliases have to be replaced here so that plugins using the Filepath from a previous get() call can still work
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $file);
			
		// check to make sure file is valid
		if(is_file($file))
		{
			$files = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($files) > 0)
			{				
				if($fp = fopen($files[0]['Filepath'], 'rb'))
				{
					return $fp;
				}
			}
		}
		return false;
	}
	
	
	// the mysql can be left null to get the files from a directory, in which case a directory must be specified
	// if the mysql is provided, then the file listings will be loaded from the database
	// this is a very generalized module to provide a template for overriding, or for other modules to modify the $request and pass to this one
	//  other modules are responsible for any validation of input that is not listed here, like making sure files exist on the filesystem
	static function get($database, $request, &$count, &$error, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
		}
		
		$files = array();
		
		if(USE_DATABASE)
		{
			// do validation! for the fields we use
			$database->validate($request, $props, $module);

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
						$props['WHERE'] .= ' Filepath="' . addslashes(@pack('H*', $id)) . '" OR';
					}
				}
				// remove last or
				$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);

				// selected items have priority over all the other options!
				unset($props['LIMIT']);
				unset($request);
			}

			// add where includes
			if(isset($request['search']) && $request['search'] != '')
			{
				$props['WHERE'] = '';

				// check if they are searching for columns equal input
				$is_equal = false;
				if(strlen($request['search']) > 1 && $request['search'][0] == '=' && $request['search'][strlen($request['search'])-1] == '=')
				{
					$request['search'] = preg_quote(substr($request['search'], 1, strlen($request['search'])-2));
					$is_equal = true;
				}
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true)
					$request['search'] = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $request['search']);
					
				// escape quote marks to help prevent sql injection
				$regexp = addslashes($request['search']);
				
				// they can specify multiple columns to search for the same string
				if(isset($request['columns']))
				{
					$columns = split(',', $request['columns']);
				}
				// search every column for the same string
				else
				{
					$columns = call_user_func($module . '::columns');
				}
				
				// add a regular expression matching for each column in the table being searched
				$props['WHERE'] .= '(';
				foreach($columns as $i => $column)
				{
					if($is_equal)
						$columns[$i] .= ' = "' . $regexp . '"';
					else
						$columns[$i] .= ' REGEXP "' . $regexp . '"';
				}
				$props['WHERE'] .= join(' OR ', $columns) . ')';
			}
			
			// search for individual column queries
			//   search multiple columns for different string
			$columns = call_user_func($module . '::columns');
			foreach($columns as $i => $column)
			{
				$var = 'search_' . $column;
				if(isset($request[$var]) && $request[$var] != '')
				{
					if(!isset($props['WHERE'])) $props['WHERE'] = '';
					elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
					
					// check if they are searching for a cell equal to the input
					$is_equal = false;
					if(strlen($request[$var]) > 1 && $request[$var][0] == '=' && $request[$var][strlen($request[$var])-1] == '=')
					{
						$request[$var] = preg_quote(substr($request[$var], 1, strlen($request[$var])-2));
						$is_equal = true;
					}
					
					// incase an aliased path is being searched for replace it here too!
					if(USE_ALIAS == true)
						$request[$var] = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $request[$var]);
						
					// escape quote marks to help prevent sql injection
					$regexp = addslashes($request[$var]);
					
					// add a regular expression matching for each column in the table being searched
					if($is_equal)
						$props['WHERE'] .= $column . ' = "' . $regexp . '"';
					else
						$props['WHERE'] .= $column . ' REGEXP "' . $regexp . '"';
				}
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
				if(USE_ALIAS == true)
					$request['dir'] = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $request['dir']);
				
				// make sure file exists if we are using the file module
				if($module != 'db_file' || is_dir(realpath($request['dir'])) !== false)
				{
				
					// make sure directory is in the database
					$dirs = $database->query(array('SELECT' => constant($module . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
					// check the file database, some modules use their own database to store special paths,
					//  while other modules only store files and no directories, but these should still be searchable paths
					//  in which case the module is responsible for validation of it's own paths
					if(count($dirs) == 0)
						$dirs = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
					// top level directory / should always exist
					if($request['dir'] == realpath('/') || count($dirs) > 0)
					{
						if(!isset($props['WHERE'])) $props['WHERE'] = '';
						elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
						
						// if the includes is blank then only show files from current directory
						if(!isset($request['search']))
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($request['dir'])+1) . ') = LENGTH(Filepath)';
							else
								$props['WHERE'] .= 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND (LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($request['dir'])+1) . ') = 0 OR LOCATE("' . addslashes(DIRECTORY_SEPARATOR) . '", Filepath, ' . (strlen($request['dir'])+1) . ') = LENGTH(Filepath)) AND Filepath != "' . addslashes($request['dir']) . '"';
						}
						// show all results underneath directory
						else
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND RIGHT(Filepath, 1) = "' . addslashes(DIRECTORY_SEPARATOR) . '" AND Filepath != "' . addslashes($request['dir']) . '"';
							else
								$props['WHERE'] .= 'LEFT(Filepath, ' . strlen($request['dir']) . ') = "' . addslashes($request['dir']) . '" AND Filepath != "' . addslashes($request['dir']) . '"';
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
				if(USE_ALIAS == true)
					$request['file'] = preg_replace($GLOBALS['SOFT']['alias_regexp'], $GLOBALS['SOFT']['paths'], $request['file']);
				
				// make sure file exists if we are using the file module
				if($module != 'db_file' || file_exists(realpath($request['file'])) !== false)
				{
				
					if(!isset($props['WHERE'])) $props['WHERE'] = '';
					elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
				}
				else
				{
					$error = 'File does not exist!';
				}
				
				// add file to where
				$props['WHERE'] .= ' Filepath = "' . addslashes($request['file']) . '"';
			}
			
			if($error == '')
			{
				$props['SELECT'] = constant($module . '::DATABASE');
				if(isset($props['GROUP'])) $props['COLUMNS'] = '*,count(*)';
	
				// get directory from database
				$files = $database->query($props);
				
				if($files !== false)
				{
					// make some changes
					foreach($files as $index => $file)
					{
						// do alias replacement on every file path for security reasons do it here
						if(USE_ALIAS == true && isset($file['Filepath']))
						{
							$files[$index]['Filepath'] = preg_replace($GLOBALS['SOFT']['paths_regexp'], $GLOBALS['SOFT']['alias'], $file['Filepath']);
							$alias_flipped = array_flip($GLOBALS['SOFT']['alias']);
							// check if the replaced path was the entire alias path
							// in this case we want to replace the filename with the alias name
							if(isset($alias_flipped[$file['Filepath']]))
							{
								$index = $alias_flipped[$file['Filepath']];
								$files[$index]['Filename'] = substr($GLOBALS['SOFT']['alias'][$index], 1, strlen($GLOBALS['SOFT']['alias'][$index]) - 2);
							}
						}
					}
					
					// only get count if filepath is not set, otherwise we know it is only one
					if(!isset($request['file']))
					{
						// this is how we get the count of all the items
						//  unset the limit to count it
						unset($props['LIMIT']);
						
						// count the last query
						$props = array('SELECT' => '(' . SQL::statement_builder($props) . ') AS db_to_count');
						$props['COLUMNS'] = 'count(*)';
						
						$result = $database->query($props);
						
						$count = intval($result[0]['count(*)']);
					}
					else
					{
						$count = 1;
					}
				}
				else
				{
					$count = 0;
					$files = array();
				}
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
			$args['CONNECTION']->query(array('DELETE' => constant($args['MODULE'] . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($row['Filepath']) . '"'));
			
			log_error('Removing ' . constant($args['MODULE'] . '::NAME') . ': ' . $row['Filepath']);
		}
		
		// print progress
		$args['count']++;
		if(round(($args['count']-1)/$args['total'], 2) != round($args['count']/$args['total'], 2))
		{
			log_error('Checking paths ' . (round($args['count']/$args['total'], 2) * 100) . '% complete for ' . constant($args['MODULE'] . '::NAME'));
		}
	}
	
	
	// cleanup the non-existant files
	static function cleanup($database, $watched, $ignored, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
		}
	
		// first clear all the items that are no longer in the watch list
		// since the watch is resolved all the items in watch have to start with the watched path
		$watched_where = '';
		foreach($watched as $i => $watch)
		{
			// add the files that begin with a path from a watch directory
			$watched_where .= ' Filepath NOT REGEXP "^' . addslashes(preg_quote($watch['Filepath'])) . '" AND';
		}
		// remove last AND
		$watched_where = substr($watched_where, 0, strlen($watched_where)-3);
		
		// but keep the ones leading up to watched directories
		// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
		$watched_to_where = '';
		$directories = array();
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
					if((!USE_ALIAS || in_array($curr_dir, $GLOBALS['SOFT']['paths']) !== false))
					{
						// this allows for us to make sure that at least the beginning 
						//   of the path is an aliased path
						$between = true;
						
						if(!in_array($curr_dir, $directories))
						{
							$directories[] = $curr_dir;
							// if the USE_ALIAS is true this will only add the folder
							//    if it is in the list of aliases
							$watched_to_where .= ' Filepath != "' . addslashes($curr_dir) . '" AND';
						}
					}
					// but make an exception for folders between an alias and the watch path
					elseif(USE_ALIAS && $between && !in_array($curr_dir, $directories))
					{
						$directories[] = $curr_dir;
						
						$watched_to_where .= ' Filepath != "' . addslashes($curr_dir) . '" AND';
					}
				}
			}
		}
		// remove last AND
		$watched_to_where = substr($watched_to_where, 0, strlen($watched_to_where)-3);
		
		$ignored_where = '';
		// clean up items that are in the ignore list
		foreach($ignored as $i => $ignore)
		{
			$ignored_where .= ' Filepath REGEXP "^' . addslashes(preg_quote($ignore['Filepath'])) . '" OR';
		}
		// remove last OR
		$ignored_where = substr($ignored_where, 0, strlen($ignored_where)-2);
		
		$where_str = '(' . $ignored_where . ') OR (' . $watched_to_where . ' AND ' . $watched_where . ')';
		
		// remove items
		$database->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => $where_str));

		// get count 
		$result = $database->query(array('SELECT' => constant($module . '::DATABASE'), 'COLUMNS' => 'count(*)'));
		$total = $result[0]['count(*)'];
		$count = 0;

		// since all the ones not apart of a watched directory is removed, now just check is every file still in the database exists on disk
		$database->query(array(
			'SELECT' => constant($module . '::DATABASE'), 
			'CALLBACK' => array(
				'FUNCTION' => $module . '::cleanup_remove',
				'ARGUMENTS' => array('CONNECTION' => new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME), 'MODULE' => $module, 'count' => &$count, 'total' => &$total)
			)
		));
				
		// remove any duplicates
		$files = $database->query(array(
				'SELECT' => constant($module . '::DATABASE'),
				'COLUMNS' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
				'GROUP' => 'Filepath',
				'HAVING' => 'num > 1'
			)
		);
		
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			$database->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => 'id=' . $file['id']));
			
			log_error('Removing ' . constant($module . '::NAME') . ': ' . $file['Filepath']);
		}
		
		log_error('Cleanup for ' . constant($module . '::NAME') . ' complete.');
		
	}
	
}

?>