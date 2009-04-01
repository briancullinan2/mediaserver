<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// id handler
class db_ids extends db_file
{
	const DATABASE = 'ids';
	
	const NAME = 'IDs from Database';
	
	const INTERNAL = true;

	static function columns()
	{
		return array_keys(self::struct());
	}
	
	static function struct()
	{
		$struct = array(
			'Filepath' 		=> 'TEXT',
			'Hex'			=> 'TEXT',
		);
		foreach($GLOBALS['tables'] as $i => $db)
		{
			if($db != db_ids::DATABASE && $db != db_watch_list::DATABASE)
				$struct[$db . '_id'] = 'BIGINT';
		}
		
		return $struct;
	}
	
	static function handles($file)
	{
		return true;
	}

	static function handle($file, $force = false, $ids = array())
	{
		$file = str_replace('\\', '/', $file);
		
		// check if it is in the database
		$db_ids = $GLOBALS['database']->query(array(
				'SELECT' => self::DATABASE,
				'COLUMNS' => array('id'),
				'WHERE' => 'Filepath = "' . addslashes($file) . '"'
			)
		);
		
		// only do this very expensive part if it is not in database or force is true
		$fileinfo = array();
		if(count($db_ids) == 0 || $force == true)
		{
			// get all the ids from all the tables
			$fileinfo['Filepath'] = addslashes($file);
			$fileinfo['Hex'] = bin2hex($file);
			foreach($GLOBALS['tables'] as $i => $db)
			{
				if($db != db_ids::DATABASE && $db != db_watch_list::DATABASE)
				{
					if(isset($ids[$db . '_id']))
					{
						if($ids[$db . '_id'] !== false)
							$fileinfo[$db . '_id'] = $ids[$db . '_id'];
					}
					else
					{
						$ids = $GLOBALS['database']->query(array(
								'SELECT' => $db,
								'COLUMNS' => 'id',
								'WHERE' => 'Filepath = "' . addslashes($file) . '"'
							)
						);
						if(isset($ids[0])) $fileinfo[$db . '_id'] = $ids[0]['id'];
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
				log_error('Adding id for file: ' . $file);
				
				// add to database
				return $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			}
			// update ids
			elseif($force)
			{
				log_error('Modifying id for file: ' . $file);
				
				$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_ids[0]['id']));
				return $db_ids[0]['id'];
			}
		}
		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		// find an id column being searched for
		$columns = db_ids::columns();
		foreach($columns as $i => $column)
		{
			if(isset($request['search_' . $column]))
			{
				$files = parent::get(array('search_' . $column => $request['search_' . $column]), $count, $error, get_class());
				break;
			}
		}
		
		// if the id is not found for the file, add it
		if(isset($request['file']) && $count == 0)
		{
			self::handle(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']), true);
			$files = parent::get($request, $count, $error, get_class());
		}
		
		return $files;
	}
	
	static function remove($file, $module = NULL)
	{
		if($module != NULL)
		{
			// do the same thing db_file does except update and set module_id to 0
			$file = str_replace('\\', '/', $file);
			
			// remove files with inside paths like directories
			if($file[strlen($file)-1] != '/') $file_dir = $file . '/';
			else $file_dir = $file;
			
			// all the removing will be done by other modules
			$GLOBALS['database']->query(array('UPDATE' => constant($module . '::DATABASE'), 'VALUES' => array(constant($module . '::DATABASE') . '_id' => 0), 'WHERE' => 'Filepath = "' . addslashes($file) . '" OR LEFT(Filepath, ' . strlen($file_dir) . ') = "' . addslashes($file_dir) . '"'));	
		}
	}
	
	static function cleanup()
	{
		parent::cleanup(get_class());
		
		// remove empty ids
		$where = '';
		foreach($GLOBALS['tables'] as $i => $db)
		{
			$where .= $db . '_id=0 AND';
		}
		$where = substr($where, 0, strlen($where) - 3);

		$GLOBALS['database']->query(array(
			'DELETE' => self::DATABASE,
			'WHERE' => $where
		));
	}

}

?>