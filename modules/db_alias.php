<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_alias extends db_file
{
	const DATABASE = 'alias';
	
	const NAME = 'Aliased Paths from Database';

	static function columns()
	{
		return array('id', 'Paths', 'Paths_regexp', 'Alias', 'Alias_regexp');
	}
	
	static function handles($file)
	{
		return false;
	}

	static function handle($database, $file)
	{
	}
	
	static function get($database, $request, &$count, &$error)
	{
		$request['order_by'] = 'id';
		
		$database->validate($request, $props, get_class());
		
		$props['SELECT'] = self::DATABASE;
		
		$files = $database->query($props);
		
		return $files;
	}
	
	static function cleanup($database)
	{
	}

}

?>