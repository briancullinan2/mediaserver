<?php


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
	foreach(get_handlers(false, false) as $handler => $config)
	{
		$struct[$handler . '_id'] = 'INT';
	}
	
	$GLOBALS['modules']['ids']['database'] = $struct;
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
		foreach(get_handlers(false, false) as $handler => $config)
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
	
	// only add to database if the filepath exists in another table
	if(count($fileinfo) > 0)
	{
		$fileinfo['Filepath'] = addslashes($file);
		$fileinfo['Hex'] = bin2hex($file);
		
		// add list of ids
		if( count($db_ids) == 0 )
		{
			raise_error('Adding id for file: ' . $file, E_DEBUG);
			
			// add to database
			return db_query('INSERT INTO ids (' . sql_keys($fileinfo) . ') VALUES (' . '' . ')', array_values($fileinfo));
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
		$return = db_query('SELECT * FROM ids WHERE ' . $request['cat'] . '_id = ' . join(' OR ' . $request['cat'] . '_id = ', $request['selected']) . ' AND ' . sql_users() . ' LIMIT ' . count($files));

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
			// look up file if it was not retrieved by the id information
			if(!isset($ids[$file['id']])) 
			{
				// handle file
				if(setting('admin_alias_enable') != false)
					$id = add_ids(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file['Filepath']), true, array($request['cat'] . '_id' => $file['id']));
				else
					$id = add_ids($file['Filepath'], true, array($request['cat'] . '_id' => $file['id']));
				
				$tmp_id = get_files(array(
					'cat' => 'ids',
					'id' => $id,
					'users' => session('users'),
				), $count);

				if($tmp_id == false || $count == 0)
				{
					raise_error('There was an error getting the IDs.', E_DEBUG|E_USER);
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
		// skip wrappers and internal handlers
		foreach(get_handlers(false, false) as $handler => $config)
		{
			// get file based on ID
			if(isset($request[$handler . '_id']) && is_numeric($request[$handler . '_id']))
			{
				$files = db_query(array(
					'cat'            => 'ids',
					$handler . '_id' => $request[$handler . '_id'],
					'users'          => session('users')
				), $count);
				break;
			}
		}
		
		// if the id is not found for the file, add it
		if(count($files) == 0)
		{
			if(setting('admin_alias_enable'))
				$id = add_ids(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']), true);
			else
				$id = add_ids($request['file']);
			
			$files = get_files(array(
				'cat'   => 'ids',
				'id'    => $id,
				'users' => session('users')
			), $count);
			
			if($count == 0)
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
		db_query('UPDATE ids SET ' . $handler . '_id = 0 WHERE Filepath = "?" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "?"', array($file, $file_dir));
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
	foreach(get_handlers(false, false) as $handler => $config)
	{
		$where .= ' ' . $handler . '_id=0 AND';
	}
	$where = substr($where, 0, strlen($where) - 3);
	
	db_query('DELETE FROM ids WHERE ' . $where);
}

