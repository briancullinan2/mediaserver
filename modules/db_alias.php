<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
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
			'Paths' 		=> 'TEXT',
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
		$props['COLUMNS'] = '*,Alias as Filepath';
		
		$files = $GLOBALS['database']->query($props);
		
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