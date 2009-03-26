<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_ids extends db_file
{
	const DATABASE = 'ids';
	
	const NAME = 'IDs from Database';

	static function columns()
	{
		$columns = array('id', 'Filepath', 'Hex');
		foreach($GLOBALS['tables'] as $i => $db)
		{
			$columns[] = $db . '_id';
		}
		return $columns;
	}
	
	static function handles($file)
	{
		return true;
	}

	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		// check if it is in the database
		$db_ids = $GLOBALS['database']->query(array(
				'SELECT' => self::DATABASE,
				'COLUMNS' => array('id'),
				'WHERE' => 'Filepath = "' . addslashes($file) . '"'
			)
		);
		
		// get all the ids from all the tables
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
		$fileinfo['Hex'] = bin2hex($file);
		foreach($GLOBALS['tables'] as $i => $db)
		{
			if($db != 'ids')
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
		
		if(count($fileinfo) > 2)
		{
			// add list of ids
			if( count($db_ids) == 0 )
			{
				log_error('Adding id for file: ' . $file);
				
				// add to database
				$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
				return true;
			}
			// update ids
			elseif($force)
			{
				log_error('Modifying id for file: ' . $file);
				
				$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_ids[0]['id']));
				return 1;
			}
		}
		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		$files = parent::get($request, $count, $error, get_class());
		
		if(isset($request['file']) && $count == 0)
		{
			self::handle(preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']));
			$files = parent::get($request, $count, $error, get_class());
		}
		
		return $files;
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