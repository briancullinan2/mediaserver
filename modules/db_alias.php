<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// don't actually do anything in this class, this is just used for holding the database, and the get() method
class db_alias extends db_file
{
	const DATABASE = 'alias';
	
	const NAME = 'Aliased Paths from Database';
	
	const INTERNAL = true;

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'Filepath' 		=> 'TEXT',
			'Alias' 		=> 'TEXT',
			'Paths_regexp'	=> 'TEXT',
			'Alias_regexp'	=> 'TEXT'
		);
	}
	
	static function handles($file)
	{
		return false;
	}

	static function handle($file, $force = false)
	{
		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		$request['order_by'] = 'id';
		
		$GLOBALS['database']->validate($request, $props, get_class());
		
		$props['SELECT'] = self::DATABASE;
		
		$files = $GLOBALS['database']->query($props, false);
		
		return $files;
	}
	
	static function remove($file)
	{
	}
	
	static function cleanup()
	{
	}

}

?>