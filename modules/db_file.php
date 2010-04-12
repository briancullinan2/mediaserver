<?php

// the basic file type

class db_file
{	
	// most of these methods should just be static, no need to instantiate the class
	// just good for organization purposes
	const DATABASE = 'files';
	
	const NAME = 'Files from Database';
	
	// define if this module is internal so templates won't try to use it
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
	
	// return whether or not this module handles trhe specified type of file
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
	
	// this function determines if the file qualifies for this type and handles it according
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
	
	// add a given file to the given database
	// returns the id of the entry
	// if the id is given then it updates the entry
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
	
	// output provided file to given stream
	//  no headers is used to prevent changing the headers, if it is called by a plugin it may just need the stream and no header changes
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
	
	//----------------------- Magic, do not touch -----------------------
	// the mysql can be left null to get the files from a directory, in which case a directory must be specified
	// if the mysql is provided, then the file listings will be loaded from the database
	// this is a very generalized module to provide a template for overriding, or for other modules to modify the $request and pass to this one
	//  other modules are responsible for any validation of input that is not listed here, like making sure files exist on the filesystem
	static function get($request, &$count, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
		}
		
		$files = array();
		
		if(USE_DATABASE)
		{
			// set up initial props
			$props = array();
			$request['cat'] = $module;
			$request['limit'] = validate_limit($request);
			$request['start'] = validate_start($request);
			$request['order_by'] = validate_order_by($request);
			$request['direction'] = validate_direction($request);
			$request['selected'] = validate_selected($request);
			
			$props = alter_query_core($request, $props);

//---------------------------------------- Selection ----------------------------------------\\
			$columns = call_user_func($module . '::columns');

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
					// loop through ids and construct new where based on module
					$props['WHERE'] = '';
					foreach($files as $i => $file)
					{
							$props['WHERE'] .= ' id = ' . $file[constant($module . '::DATABASE') . '_id'] . ' OR';
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

//---------------------------------------- Search All ----------------------------------------\\
			// add where includes
			if(isset($request['search']) && $request['search'] != '')
			{
				if(!isset($props['WHERE'])) $props['WHERE'] = '';
				elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
				
				// check if they are searching for a literal string
				$is_literal = false;
				$is_equal = false;
				$is_regular = false;
				if(strlen($request['search']) > 1 && $request['search'][0] == '"' && $request['search'][strlen($request['search'])-1] == '"')
				{
					$request['search'] = substr($request['search'], 1, strlen($request['search'])-2);
					$is_literal = true;
				}
				// check if they are searching for columns equal input
				elseif(strlen($request['search']) > 1 && $request['search'][0] == '=' && $request['search'][strlen($request['search'])-1] == '=')
				{
					$request['search'] = substr($request['search'], 1, strlen($request['search'])-2);
					$is_equal = true;
				}
				// check if they are performing a regular expression search
				elseif(strlen($request['search']) > 1 && $request['search'][0] == '/' && $request['search'][strlen($request['search'])-1] == '/')
				{
					$request['search'] = substr($request['search'], 1, strlen($request['search'])-2);
					$is_regular = true;
				}
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true)
					$request['search'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['search']);
				
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
				$parts = array();
				if(!$is_equal && !$is_regular && !$is_literal)
				{
					// loop through search terms and construct query
					$pieces = split(' ', $request['search']);
					$pieces = array_unique($pieces);
					$empty = array_search('', $pieces, true);
					if($empty !== false) unset($pieces[$empty]);
					$pieces = array_values($pieces);
					
					// sort items by inclusive, exclusive, and string size
					// rearrange pieces, but keep track of index so we can sort them correctly
					uasort($pieces, 'termSort');
					$length = strlen(join(' ', $pieces));
					
					foreach($columns as $i => $column)
					{
						if($column != 'id' && (!isset($request['search_' . $column]) || $request['search_' . $column] == ''))
						{
							$required = '';
							$excluded = '';
							$includes = '';
							if($request['order_by'] == 'Relevance')
								$props['ORDER'] = 'r_count' . $i . ' ASC,' . (isset($props['ORDER'])?$props['ORDER']:'');
							foreach($pieces as $j => $piece)
							{
								if($piece[0] == '+')
								{
									if($required != '') $required .= ' AND';
									$piece = substr($piece, 1);
									$required .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0';
									$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0) AS result' . $i . $j;
								}
								elseif($piece[0] == '-')
								{
									if($excluded != '') $excluded .= ' AND';
									$piece = substr($piece, 1);
									$excluded .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') = 0';
									$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') = 0) AS result' . $i . $j;
								}
								else
								{
									if($includes != '') $includes .= ' OR';
									$includes .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0';
									$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0) AS result' . $i . $j;
								}
								if($request['order_by'] == 'Relevance')
									$props['ORDER'] = 'result' . $i . (count($pieces) - $j - 1) . ' DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
							}
							$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',ABS(LENGTH(' . $column . ') - ' . $length . ') as r_count' . $i;
							$part = '';
							$part .= (($required != '')?(($part != '')?' AND':'') . $required:'');
							$part .= (($excluded != '')?(($part != '')?' AND':'') . $excluded:'');
							$part .= (($includes != '')?(($part != '')?' AND':'') . $includes:'');
							$parts[] = $part;
						}
					}
				}
				else
				{
					foreach($columns as $i => $column)
					{
						if(!isset($request['search_' . $column]) || $request['search_' . $column] == '')
						{
							if($is_equal)
							{
								$parts[] = $column . ' = "' . addslashes($request['search']) . '"';
							}
							elseif($is_regular)
							{
								$parts[] = $column . ' REGEXP "' . addslashes($request['search']) . '"';
							}
							elseif($is_literal)
							{
								$parts[] = ' LOCATE("' . addslashes($request['search']) . '", ' . $column . ')';
							}
						}
					}
				}
				$props['WHERE'] .= join((isset($request['search_operator'])?(' ' . $request['search_operator'] . ' '):' OR '), $parts) . ')';
			}
			
//---------------------------------------- Search Individual ----------------------------------------\\
			// search for individual column queries
			//   search multiple columns for different string
			$individual = '';
			foreach($columns as $i => $column)
			{
				$var = 'search_' . $column;
				if(isset($request[$var]) && $request[$var] != '')
				{
					$is_literal = false;
					$is_equal = false;
					$is_regular = false;
					// check if they are searching a literal string
					if(strlen($request[$var]) > 1 && $request[$var][0] == '"' && $request[$var][strlen($request[$var])-1] == '"')
					{
						$request[$var] = substr($request[$var], 1, strlen($request[$var])-2);
						$is_literal = true;
					}
					// check if they are searching for a cell equal to the input
					elseif(strlen($request[$var]) > 1 && $request[$var][0] == '=' && $request[$var][strlen($request[$var])-1] == '=')
					{
						$request[$var] = substr($request[$var], 1, strlen($request[$var])-2);
						$is_equal = true;
					}
					elseif(strlen($request[$var]) > 1 && $request[$var][0] == '/' && $request[$var][strlen($request[$var])-1] == '/')
					{
						$request[$var] = substr($request[$var], 1, strlen($request[$var])-2);
						$is_regular = true;
					}
					
					// incase an aliased path is being searched for replace it here too!
					if(USE_ALIAS == true)
						$request[$var] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request[$var]);
					
					// add a regular expression matching for each column in the table being searched
					if(!$is_equal && !$is_regular && !$is_literal)
					{
						// loop through search terms and construct query
						$pieces = split(' ', $request[$var]);
						$pieces = array_unique($pieces);
						$empty = array_search('', $pieces, true);
						if($empty !== false) unset($pieces[$empty]);
						$pieces = array_values($pieces);
						
						// sort items by inclusive, exclusive, and string size
						// rearrange pieces, but keep track of index so we can sort them correctly
						uasort($pieces, 'termSort');
						$length = strlen(join(' ', $pieces));
					
						$required = '';
						$excluded = '';
						$includes = '';
						if($request['order_by'] == 'Relevance')
							$props['ORDER'] = 'r_count' . $i . ' ASC,' . (isset($props['ORDER'])?$props['ORDER']:'');
						foreach($pieces as $j => $piece)
						{
							if($piece[0] == '+')
							{
								if($required != '') $required .= ' AND';
								$piece = substr($piece, 1);
								$required .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0';
								$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0) AS result' . $i . $j;
							}
							elseif($piece[0] == '-')
							{
								if($excluded != '') $excluded .= ' AND';
								$piece = substr($piece, 1);
								$excluded .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') = 0';
								$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') = 0) AS result' . $i . $j;
							}
							else
							{
								if($includes != '') $includes .= ' OR';
								$includes .= ' LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0';
								$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", ' . $column . ') > 0) AS result' . $i . $j;
							}
							if($request['order_by'] == 'Relevance')
								$props['ORDER'] = 'result' . $i . (count($pieces) - $j - 1) . ' DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
						}
						$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',ABS(LENGTH(' . $column . ') - ' . $length . ') as r_count' . $i;
						$individual .= (($required != '')?(' ' . ($individual != '')?(isset($request['search_operator'])?$request['search_operator']:'AND'):'') . ' ' . $required:'');
						$individual .= (($excluded != '')?(' ' . ($individual != '')?(isset($request['search_operator'])?$request['search_operator']:'AND'):'') . ' ' . $excluded:'');
						$individual .= (($includes != '')?(' ' . ($individual != '')?(isset($request['search_operator'])?$request['search_operator']:'AND'):'') . ' ' . $includes:'');
					}
					else
					{
						if($individual != '') $individual .= ' ' . (isset($request['search_operator'])?$request['search_operator']:'AND') . ' ';
						if($is_equal)
						{
							$individual .= $column . ' = "' . addslashes($request[$var]) . '"';
						}
						elseif($is_regular)
						{
							$individual .= $column . ' REGEXP "' . addslashes($request[$var]) . '"';
						}
						elseif($is_literal)
						{
							$individual .= 'LOCATE("' . addslashes($request[$var]) . '", ' . $column . ')';
						}
					}
				}
			}
			if($individual != '')
			{
				if(!isset($props['WHERE']))
					$props['WHERE'] = '';
				if($props['WHERE'] != '')
					$props['WHERE'] .= ' AND ';
				$props['WHERE'] .= '(' . $individual . ')';
			}
			
//---------------------------------------- Query ----------------------------------------\\
			// finally start processing query
			$props['SELECT'] = constant($module . '::DATABASE');
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
						$props = array('SELECT' => constant($module . '::DATABASE'));
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
	
	// remove function to delete from from a database
	static function remove($file, $module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
		}
		
		$file = str_replace('\\', '/', $file);
		
		// remove files with inside paths like directories
		if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
		else $file_dir = $file;
		
		PEAR::raiseError('Removing ' . constant($module . '::NAME') . ': ' . $file, E_DEBUG);
	
		// remove file(s) from database
		$GLOBALS['database']->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'), false);	

		// delete ids
		db_ids::remove($file, $module);
	}
	
	
	// cleanup the non-existant files
	static function cleanup($module = NULL)
	{
		if( $module == NULL )
		{
			$module = get_class();
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
		$GLOBALS['database']->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => $where_str), false);
		
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
			$GLOBALS['database']->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => $ignored_where), false);
		}
		
		// remove any duplicates
		$files = $GLOBALS['database']->query(array(
				'SELECT' => constant($module . '::DATABASE'),
				'COLUMNS' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
				'GROUP' => 'Filepath',
				'HAVING' => 'num > 1'
			)
		, false);
	
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			PEAR::raiseError('Removing Duplicate ' . constant($module . '::NAME') . ': ' . $file['Filepath'], E_DEBUG);
			
			$GLOBALS['database']->query(array('DELETE' => constant($module . '::DATABASE'), 'WHERE' => 'id=' . $file['id']), false);
		}
		
		PEAR::raiseError('Cleanup: for ' . constant($module . '::NAME') . ' complete.', E_DEBUG);
		
	}
	
}

?>