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
		foreach($GLOBALS['databases'] as $i => $db)
		{
			$columns[] = $db . '_id';
		}
		return $columns;
	}
	
	static function handles($file)
	{
		return true;
	}

	static function handle($file)
	{
	}
	
	static function get($request, &$count, &$error)
	{
	}
	
	static function cleanup()
	{
	}

}

?>