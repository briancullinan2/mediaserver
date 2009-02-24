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
	static function handle($database, $file)
	{
		// files always qualify, we are going to log every single one!
		
		if(db_file::handles($file))
		{
			
			// check if it is in the database
			$db_file = $database->query(array(
					'SELECT' => db_file::DATABASE,
					'COLUMNS' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				$id = db_file::add($database, $file);
			}
			else
			{
				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = db_file::add($database, $file, $db_file[0]['id']);
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
	static function add($database, $file, $id = NULL)
	{
		// get file information
		$fileinfo = db_file::getInfo($file);
			
		// if the id is set then we are updating and entry
		if( $id != NULL )
		{
			print 'Modifying file: ' . $file . "\n";
			
			// update database
			$id = $database->query(array('UPDATE' => db_file::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $id));
		
			return $id;
		}
		else
		{
			print 'Adding file: ' . $file . "\n";
			
			// add to database
			$id = $database->query(array('INSERT' => db_file::DATABASE, 'VALUES' => $fileinfo));
		
			return $id;
		}
		
		
		flush();
		
	}
	
	// output provided file to given stream
	static function out($database, $file, $stream)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			$files = $database->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				$file = $files[0];
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
				
				if($op !== false)
				{
					if($fp = fopen($files[0]['Filepath'], 'rb'))
					{
						ob_start();
						
						if(isset($_SESSION)) session_write_close();
						
						// set up some general headers
						header('Content-Transfer-Encoding: binary');
						header('Content-Type: ' . $file['Filemime']);
						header('Content-Disposition: attachment; filename="' . $file['Filename'] . '"');
						
						// check for range request
						if(isset($_SERVER['HTTP_RANGE']))
						{
							list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
					
							if ($size_unit == 'bytes')
							{
								// multiple ranges could be specified at the same time, but for simplicity only serve the first range
								// http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
								if(strpos($range_orig, ',') !== false)
									list($range, $extra_ranges) = explode(',', $range_orig, 2);
								else
									$range = $range_orig;
							}
							else
							{
								$range = '-';
							}
						}
						else
						{
							$range = '-';
						}
						
						// figure out download piece from range (if set)
						list($seek_start, $seek_end) = explode('-', $range, 2);
					
						// set start and end based on range (if set), else set defaults
						// also check for invalid ranges.
						$seek_end = (empty($seek_end)) ? ($file['Filesize'] - 1) : min(abs(intval($seek_end)),($file['Filesize'] - 1));
						//$seek_end = $file['Filesize'] - 1;
						$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
						
						// Only send partial content header if downloading a piece of the file (IE workaround)
						if ($seek_start > 0 || $seek_end < ($file['Filesize'] - 1))
						{
							header('HTTP/1.1 206 Partial Content');
						}
				
						header('Accept-Ranges: bytes');
						header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $file['Filesize']);
					
						//headers for IE Bugs (is this necessary?)
						//header("Cache-Control: cache, must-revalidate");  
						//header("Pragma: public");
					
						header('Content-Length: ' . ($seek_end - $seek_start + 1));
						
						// seek to start of missing part
						fseek($fp, $seek_start);
						
						$buffer = ob_get_contents();
						ob_end_clean();
						
						$tp = fopen('/tmp/test.txt', 'a');
						fwrite($tp, $seek_start . ' - ' . $seek_end . (isset($_SERVER['HTTP_RANGE'])?(' - ' . $_SERVER['HTTP_RANGE']):'') . "\n");
						fwrite($tp, $buffer);
						fclose($tp);

						// output file
						while (!feof($fp)) {
							fwrite($op, fread($fp, BUFFER_SIZE));
						}
						
						// close file handles and return succeeded
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
				$regexp = addslashes($request['includes']);
				
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
				
				// make sure file exists if we are using the file module
				if($module != 'db_file' || is_dir(realpath($request['dir'])) !== false)
				{
				
					// make sure directory is in the database
					$dirs = $database->query(array('SELECT' => constant($module . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
					// check the file database, some modules use their own database to store special paths,
					//  while other modules only store files and no directories, but these should still be searchable paths
					//  in which case the module is responsible for validation of it's own paths
					if(count($dirs) == 0)
						$dirs = $database->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					
					// top level directory / should always exist
					if($request['dir'] == realpath('/') || count($dirs) > 0)
					{
						if(!isset($props['WHERE'])) $props['WHERE'] = '';
						elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
						
						// if the includes is blank then only show files from current directory
						if(!isset($request['includes']))
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(preg_quote($request['dir'])) . '[^' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . ']+' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . '$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(preg_quote($request['dir'])) . '[^' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . ']+' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . '?$"';
						}
						// show all results underneath directory
						else
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(preg_quote($request['dir'])) . '([^' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . ']+' . addslashes(preg_quote(DIRECTORY_SEPARATOR)) . ')*$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(preg_quote($request['dir'])) . '"';
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
				
				// this is how we get the count of all the items
				unset($props['LIMIT']);
				$props = array('SELECT' => '(' . SQL::statement_builder($props) . ') AS db_to_count');
				$props['COLUMNS'] = 'count(*)';
				
				$result = $database->query($props);
				
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
			$args['CONNECTION']->query(array('DELETE' => constant($args['MODULE'] . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($row['Filepath']) . '"'));
			
			print 'Removing ' . constant($args['MODULE'] . '::NAME') . ': ' . $row['Filepath'] . "\n";
		}
		
		// print progress
		$args['count']++;
		if(round(($args['count']-1)/$args['total'], 2) != round($args['count']/$args['total'], 2))
		{
			print 'Checking paths ' . (round($args['count']/$args['total'], 2) * 100) . '% complete for ' . constant($args['MODULE'] . '::NAME') . "\n";
			flush();
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
		$where_str = '';
		foreach($watched as $i => $watch)
		{
			// add the files that begin with a path from a watch directory
			$where_str .= ' Filepath REGEXP "^' . addslashes(preg_quote($watch)) . '" OR';
		}
		// but keep the ones leading up to watched directories
		// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
		$directories = array();
		for($i = 0; $i < count($watched); $i++)
		{
			$folders = split(addslashes(DIRECTORY_SEPARATOR), $watched[$i]);
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
					if((!USE_ALIAS || in_array($curr_dir, $GLOBALS['paths']) !== false))
					{
						// this allows for us to make sure that at least the beginning 
						//   of the path is an aliased path
						$between = true;
						
						if(!in_array($curr_dir, $directories))
						{
							$directories[] = $curr_dir;
							// if the USE_ALIAS is true this will only add the folder
							//    if it is in the list of aliases
							$where_str .= ' Filepath = "' . addslashes($curr_dir) . '" OR';
						}
					}
					// but make an exception for folders between an alias and the watch path
					elseif(USE_ALIAS && $between && !in_array($curr_dir, $directories))
					{
						$directories[] = $curr_dir;
						
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
			$where_str = 'Filepath REGEXP "^' . addslashes(preg_quote($ignore)) . '" OR ' . $where_str;
		}
		
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
				'FUNCTION' => function_exists($module . '::cleanup_remove')?$module . '::cleanup_remove':'db_file::cleanup_remove',
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
			
			print 'Removing ' . constant($module . '::NAME') . ': ' . $file['Filepath'] . "\n";
		}
		
		print 'Cleanup for ' . constant($module . '::NAME') . " complete.\n";
		flush();
		
	}
	
}

?>