<?php

/**
 * Handle all physical files
 * display information like Filesize, Filepath, and detect MIME types
 */
class db_file
{	
	// most of these methods should just be static, no need to instantiate the class
	// just good for organization purposes
	const DATABASE = 'files';
	
	const NAME = 'Files from Database';
	
	// define if this handler is internal so templates won't try to use it
	const INTERNAL = false;
	
	// this function specifies the level of detail for the array of file info, ORDER matters!
	static function columns()
	{
		return array_keys(self::struct());
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'id' => 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id)',
			'Filename' => 'TEXT',
			'Filemime' => 'TEXT',
			'Filesize' => 'BIGINT',
			'Filedate' => 'DATETIME',
			'Filetype' => 'TEXT',
			'Filepath' => 'TEXT'
		);
	}
	
	/**
	 * return whether or not this handler handles trhe specified type of file
	 * @param file the file to test for handling
	 * @return true if this handler is capable of handling the specified file, false if this handler does not handle the file
	 */
	static function handles($file)
	{
		//print_r(self::struct());
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		if((is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)) || (is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)) && $file[strlen($file)-1] != '/')) && !in_array($file, $GLOBALS['ignored']))
		{
			$filename = basename($file);

			// make sure it isn't a hidden file
			if(strlen($filename) > 0 && $filename[0] != '.')
				return true;
			else
				return false;
		} else {
			return false;
		}
	}
	
	/**
	 * this function determines if the file qualifies for this type and handles it according
	 * @param file the file to add to the database
	 * @param force force checking if the file is in the database and add it
	 * @return The ID for the file added to the database, or false if nothing was done or it isn't handled
	 */
	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		// files always qualify, we are going to log every single one!
		if(self::handles($file))
		{
			
			// check if it is in the database
			$db_file = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				return self::add($file);
			}
			// not dependent on force because it checks for modification
			else
			{
				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					return self::add($file, $db_file[0]['id']);
				}
				else
				{
					PEAR::raiseError('Skipping file: ' . $file, E_DEBUG);
				}
				
			}
			
		}
		return false;
	}
	
	/**
	 * this is a common function for handlers, just abstracting the information about a file
	 * @param file The file to get the info for
	 * @return an associative array of information that can be inserted directly in to the database
	 */
	static function getInfo($file)
	{
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $file));
		$fileinfo['Filename'] = basename($file);
		$fileinfo['Filesize'] = filesize($file);
		$fileinfo['Filemime'] = getMime($file);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
		$fileinfo['Filetype'] = getFileType($file);
		
		return $fileinfo;
	}
	
	/**
	 * add a given file to the given database
	 * @param file the file to add to the database
	 * @param id the ID of the file entry to modify or update
	 * @return the id of the entry, if the id is given then it updates the entry
	 */
	static function add($file, $id = NULL)
	{
		// get file information
		$fileinfo = self::getInfo($file);
			
		// if the id is set then we are updating and entry
		if( $id == NULL )
		{
			PEAR::raiseError('Adding file: ' . $file, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
			return $id;
		}
		else
		{
			PEAR::raiseError('Modifying file: ' . $file, E_DEBUG);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $id), false);
		
			return $id;
		}
		
	}
	
	/**
	 * output provided file to given stream
	 // no headers is used to prevent changing the headers, if it is called by a module it may just need the stream and no header changes
	 * @param file the file to output
	 * @return a stream handle for use with fread, or false if there was an error
	 */
	static function out($file)
	{
		$file = str_replace('\\', '/', $file);
		
		if(USE_ALIAS == true)
			$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		// check to make sure file is valid
		if(is_file(str_replace('/', DIRECTORY_SEPARATOR, $file)))
		{
			$files = $GLOBALS['database']->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"', 'LIMIT' => 1), true);
			if(count($files) > 0)
			{				
				if($fp = @fopen($file, 'rb'))
				{
					return $fp;
				}
			}
		}
		return false;
	}
	
	/**----------------------- Magic, do not touch -----------------------
	 * - the mysql can be left null to get the files from a directory, in which case a directory must be specified
	 * - if the mysql is provided, then the file listings will be loaded from the database
	 * - this is a very generalized handler to provide a template for overriding, or for other handlers to modify the $request and pass to this one
	 *   - other handlers are responsible for any validation of input that is not listed here, like making sure files exist on the filesystem
	 *
	 * @param request the request information for the list of files to get from the file database
	 * @param count inserts the total number of files that meet the requested criteria in the database
	 * @param handler a utility parameter for other handlers to use the default file get() functionality but acting on their own columns() and DATABASE
	 * @return an indexed array of files from the database
	 */
	static function get($request, &$count, $handler = NULL)
	{
		if( $handler == NULL )
		{
			$handler = get_class();
		}
		
		$files = array();
		
		if(USE_DATABASE)
		{
			// set up initial props
			$props = array();
			$request['cat'] = $handler;
			$request['limit'] = validate_limit($request);
			$request['start'] = validate_start($request);
			$request['order_by'] = validate_order_by($request);
			$request['direction'] = validate_direction($request);
			$request['selected'] = validate_selected($request);
			
			$props = alter_query_core($request, $props);

//---------------------------------------- Selection ----------------------------------------\\
			$columns = call_user_func($handler . '::columns');

			// select an array of ids!
			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = '';
				// compile where statement for either numeric id or encoded path
				foreach($request['selected'] as $i => $id)
				{
					if(is_numeric($id))
					{
						$props['WHERE'] .= ' id = ' . $id . ' OR';
					}
					else
					{
						// unpack encoded path and add it to where
						$props['WHERE'] .= ' Hex = "' . $id . '" OR';
					}
				}
				// remove last or
				$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);

				// selected items have priority over all the other options!
				unset($props['LIMIT']);
				unset($props['ORDER']);
				unset($request);
				
				// get ids from centralized id database
				$files = $GLOBALS['database']->query(array('WHERE' => $props['WHERE'], 'SELECT' => db_ids::DATABASE), true);
				
				if(count($files) > 0)
				{
					// loop through ids and construct new where based on handler
					$props['WHERE'] = '';
					foreach($files as $i => $file)
					{
							$props['WHERE'] .= ' id = ' . $file[constant($handler . '::DATABASE') . '_id'] . ' OR';
					}
					$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);
				}
				else
				{
					PEAR::raiseError('IDs not found!', E_USER);
					return array();
				}
			}
		
			$props = alter_query_file($request, $props);

			$props = alter_query_search($request, $props);
			
//---------------------------------------- Query ----------------------------------------\\
			// finally start processing query
			$props['SELECT'] = constant($handler . '::DATABASE');
			if(isset($props['GROUP'])) $props['COLUMNS'] = ',count(*)' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
			$props['COLUMNS'] = '*' . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
			
			// get directory from database
			$files = $GLOBALS['database']->query($props, true);

			if($files !== false)
			{
				// now make some changes
				foreach($files as $index => $file)
				{
					// do alias replacement on every file path
					if(USE_ALIAS == true)
					{
						if(isset($file['Filepath']))
							$files[$index]['Filepath'] = preg_replace($GLOBALS['paths_regexp'], $GLOBALS['alias'], $file['Filepath']);
						$alias_flipped = array_flip($GLOBALS['alias']);
						// check if the replaced path was the entire alias path
						// in this case we want to replace the filename with the alias name
						if(isset($file['Filepath']) && isset($alias_flipped[$file['Filepath']]))
						{
							$index = $alias_flipped[$file['Filepath']];
							$files[$index]['Filename'] = substr($GLOBALS['alias'][$index], 1, strlen($GLOBALS['alias'][$index]) - 2);
						}
					}
				}
				
//---------------------------------------- Get Count ----------------------------------------\\
				// only get count if the query is not limited by the limit field
				//  get count if limit is not set, which is should always be because of validate()
				//  get count if it is greater than or equal to the limit, even though it will always be equal to or less then limit
				//  if it is less, only get count if start is set
				if(!isset($request['limit']) || count($files) >= $request['limit'] || (isset($request['start']) && $request['start'] > 0))
				{
					// this is how we get the count of all the items
					//  unset the limit to count it
					unset($props['LIMIT']);
					unset($props['ORDER']);
					$props['COLUMNS'] = '*';
					
					// if where is not set then there is no reason to count the entire database
					if(!isset($props['WHERE']) && !isset($props['GROUP']))
					{
						$props = array('SELECT' => constant($handler . '::DATABASE'));
					}
					else
					{
						// count the last query
						$props = array('SELECT' => '(' . DATABASE::statement_builder($props, true) . ') AS db_to_count');
					}
					$props['COLUMNS'] = 'count(*)';
					
					$result = $GLOBALS['database']->query($props, false);
					
					$count = intval($result[0]['count(*)']);
				}
				// set the count to whatever the number of files is
				else
				{
					$count = count($files);
				}
			}
		}
			
		return $files;
	}
	
	/**
	 * remove function to delete from from a database
	 * @param file the file or folder to remove from the database
	 * @param handler a utility parameter to allow handlers to use the default functionality
	 */
	static function remove($file, $handler = NULL)
	{
		if( $handler == NULL )
		{
			$handler = get_class();
		}
		
		$file = str_replace('\\', '/', $file);
		
		// remove files with inside paths like directories
		if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
		else $file_dir = $file;
		
		PEAR::raiseError('Removing ' . constant($handler . '::NAME') . ': ' . $file, E_DEBUG);
	
		// remove file(s) from database
		$GLOBALS['database']->query(array('DELETE' => constant($handler . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'), false);	

		// delete ids
		db_ids::remove($file, $handler);
	}
	
	
	/**
	 * cleanup the non-existant files
	 * @param handler the utility parameter to allow handler to use the default file functionality but acting on their own DATABASE
	 */
	static function cleanup($handler = NULL)
	{
		if( $handler == NULL )
		{
			$handler = get_class();
		}
	
		// first clear all the items that are no longer in the watch list
		// since the watch is resolved all the items in watch have to start with the watched path
		$watched_where = '';
		foreach($GLOBALS['watched'] as $i => $watch)
		{
			$tmp_watch = str_replace('\\', '/', $watch['Filepath']);
			// add the files that begin with a path from a watch directory
			$watched_where .= ' LEFT(Filepath, ' . strlen($tmp_watch) . ') != "' .  addslashes($tmp_watch) . '" AND';
		}
		// remove last AND
		$watched_where = substr($watched_where, 0, strlen($watched_where)-3);
		
		// but keep the ones leading up to watched directories
		// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
		$watched_to_where = '';
		$directories = array();
		for($i = 0; $i < count($GLOBALS['watched']); $i++)
		{
			$folders = split('/', $GLOBALS['watched'][$i]['Filepath']);
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
					$curr_dir .= $folders[$j] . '/';
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
		
		$where_str = $watched_to_where . ' AND ' . $watched_where;
		
		// remove items that aren't in where directories
		$GLOBALS['database']->query(array('DELETE' => constant($handler . '::DATABASE'), 'WHERE' => $where_str), false);
		
		if(count($GLOBALS['ignored']) > 0)
		{
			
			$ignored_where = '';
			// clean up items that are in the ignore list
			foreach($GLOBALS['ignored'] as $i => $ignore)
			{
				$tmp_ignore = str_replace('\\', '/', $ignore['Filepath']);
				$ignored_where .= ' LEFT(Filepath, ' . strlen($tmp_ignore) . ') = "' . addslashes($tmp_ignore) . '" OR';
			}
			// remove last OR
			$ignored_where = substr($ignored_where, 0, strlen($ignored_where)-2);
			
			// remove items that are ignored
			$GLOBALS['database']->query(array('DELETE' => constant($handler . '::DATABASE'), 'WHERE' => $ignored_where), false);
		}
		
		// remove any duplicates
		$files = $GLOBALS['database']->query(array(
				'SELECT' => constant($handler . '::DATABASE'),
				'COLUMNS' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
				'GROUP' => 'Filepath',
				'HAVING' => 'num > 1'
			)
		, false);
	
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			PEAR::raiseError('Removing Duplicate ' . constant($handler . '::NAME') . ': ' . $file['Filepath'], E_DEBUG);
			
			$GLOBALS['database']->query(array('DELETE' => constant($handler . '::DATABASE'), 'WHERE' => 'id=' . $file['id']), false);
		}
		
		PEAR::raiseError('Cleanup: for ' . constant($handler . '::NAME') . ' complete.', E_DEBUG);
		
	}
	
}

