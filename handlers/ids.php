<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_ids()
{
	return array(
		'name' => 'IDs',
		'description' => 'A list of all IDs from every Filename that exists in the database.',
		'internal' => true,
	);
}


/**
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_ids()
{
	$struct = array(
		'Filepath' 		=> 'TEXT',
		'Hex'			=> 'TEXT',
	);
	// get all the tables for handlers
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(!is_internal($handler) && !is_wrapper($handler))
			$struct[$handler . '_id'] = 'INT';
	}
	
	$GLOBALS['handlers']['ids']['database'] = $struct;
}

/**
 * Implementation of handles
 * @ingroup handles
 */
function handles_ids($file)
{
	return true;
}

/**
 * Implementation of handle
 * @ingroup handle
 */
function add_ids($file, $force = false, $ids = array())
{
	$file = str_replace('\\', '/', $file);
	
	// check if it is in the database
	$db_ids = $GLOBALS['database']->query(array(
			'SELECT' => 'ids',
			'COLUMNS' => array('id'),
			'WHERE' => 'Filepath = "' . addslashes($file) . '"',
			'LIMIT' => 1
		)
	, false);
	
	// only do this very expensive part if it is not in database or force is true
	$fileinfo = array();
	if(count($db_ids) == 0 || $force == true)
	{
		// get all the ids from all the tables
		$fileinfo['Filepath'] = addslashes($file);
		$fileinfo['Hex'] = bin2hex($file);
		foreach($GLOBALS['handlers'] as $handler => $config)
		{
			if(!is_wrapper($handler) && !is_internal($handler) && isset($config['database']))
			{
				if(isset($ids[$handler . '_id']))
				{
					if($ids[$handler . '_id'] !== false)
						$fileinfo[$handler . '_id'] = $ids[$handler . '_id'];
				}
				elseif(handles($file, $handler))
				{
					$tmp_ids = $GLOBALS['database']->query(array(
							'SELECT' => $handler,
							'COLUMNS' => 'id',
							'WHERE' => 'Filepath = "' . addslashes($file) . '"',
							'LIMIT' => 1
						)
					, false);
					if(isset($tmp_ids[0])) $fileinfo[$handler . '_id'] = $tmp_ids[0]['id'];
				}
			}
		}
	}
	
	// only add to database if the filepath exists in another table
	if(count($fileinfo) > 2)
	{
		// add list of ids
		if( count($db_ids) == 0 )
		{
			raise_error('Adding id for file: ' . $file, E_DEBUG);
			
			// add to database
			return $GLOBALS['database']->query(array('INSERT' => 'ids', 'VALUES' => $fileinfo), false);
		}
		// update ids
		elseif($force)
		{
			raise_error('Modifying id for file: ' . $file, E_DEBUG);
			
			$id = $GLOBALS['database']->query(array('UPDATE' => 'ids', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_ids[0]['id']), false);
			return $db_ids[0]['id'];
		}
	}
	return false;
}

/**
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_ids($request, &$count, $files = array())
{
	if(!setting('database_enable'))
	{
		raise_error('db_ids' . '::get() called by mistake, database_enable is set to false', E_DEBUG);
		$count = 0;
		return array();
	}

	if(count($files) > 0 && !isset($request['selected']))
	{
		$request['item'] = '';
		foreach($files as $index => $file)
		{
			$request['item'] .= $file['id'] . ',';
		}
		$request['item'] = substr($request['item'], 0, strlen($request['item'])-1);
	}

	$request['selected'] = validate($request, 'selected');

	// select an array of ids!
	if(isset($request['selected']) && count($request['selected']) > 0 )
	{
		$return = $GLOBALS['database']->query(array(
				'SELECT' => 'ids',
				'WHERE' => $request['cat'] . '_id = ' . join(' OR ' . $request['cat'] . '_id = ', $request['selected']),
				'LIMIT' => count($files)
			)
		, true);

		if(count($return) > 0)
		{
			// replace key for easy lookup
			$ids = array();
			foreach($return as $i => $id)
			{
				$ids[$id[$request['cat'] . '_id']] = $id;
			}
		}
		
		// add id information to file
		foreach($files as $index => $file)
		{
			// look up file if it was not be retrieved by the id information
			if(!isset($ids[$file['id']])) 
			{
				// handle file
				$id = add_ids(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file['Filepath']), true, array($request['cat'] . '_id' => $file['id']));
				$tmp_id = $GLOBALS['database']->query(array(
						'SELECT' => 'ids',
						'WHERE' => 'id = ' . $id,
						'LIMIT' => 1
					)
				, true);
				
				if(count($tmp_id) == 0)
				{
					raise_error('There was an error getting the IDs.', E_USER);
					return array();
				}
				
				$ids[$file['id']] = $tmp_id[0];
			}
			
			// merge with output array
			$files[$index] = array_merge($ids[$file['id']], $files[$index]);
	
			// also set id to centralize id
			$files[$index]['id'] = $ids[$file['id']]['id'];
		}
	}
	elseif(isset($request['file']))
	{
		$files = array();
		foreach($GLOBALS['handlers'] as $handler => $config)
		{
			// skip wrappers and internal handlers
			if(is_wrapper($handler) || is_internal($handler) || !isset($config['database']))
				continue;
				
			// get file based on ID
			if(isset($request[$handler . '_id']) && is_numeric($request[$handler . '_id']))
			{
				$files = $GLOBALS['database']->query(array(
						'SELECT' => 'ids',
						'WHERE' => $handler . '_id = ' . $request[$handler . '_id'],
						'LIMIT' => 1
					)
				, true);
				break;
			}
		}
		
		// if the id is not found for the file, add it
		if(count($files) == 0)
		{
			$id = add_ids(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']), true);
			$files = $GLOBALS['database']->query(array(
					'SELECT' => 'ids',
					'WHERE' => 'id = ' . $id,
					'LIMIT' => 1
				)
			, true);
			
			if(count($files) == 0)
			{
				raise_error('There was an error getting the IDs.', E_USER);
				return array();
			}
		}
	}
	else
	{
		// change the cat to the table we want to use
		$request['cat'] = validate(array('cat' => 'ids'), 'cat');
	
		$files = get_files($request, $count, 'files');
	}
	
	return $files;
}

/**
 * Implementation of remove_handler
 * @ingroup remove_handler
 */
function remove_ids($file, $handler = NULL)
{
	if($handler != NULL)
	{
		// do the same thing db_file does except update and set handler_id to 0
		$file = str_replace('\\', '/', $file);
		
		// remove files with inside paths like directories
		if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
		else $file_dir = $file;
		
		// all the removing will be done by other handlers
		$GLOBALS['database']->query(array('UPDATE' => 'ids', 'VALUES' => array($handler . '_id' => 0), 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'), false);	
	}
}

/**
 * Implementation of cleanup_handler
 * @ingroup cleanup_handler
 */
function cleanup_ids()
{
	cleanup('files');
	
	// remove empty ids
	$where = '';
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		if(!is_wrapper($handler) && !is_internal($handler) && isset($config['database']))
			$where .= ' ' . $handler . '_id=0 AND';
	}
	$where = substr($where, 0, strlen($where) - 3);

	$GLOBALS['database']->query(array(
		'DELETE' => 'ids',
		'WHERE' => $where
	), false);
}

