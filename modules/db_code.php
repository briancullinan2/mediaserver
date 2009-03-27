<?php

// read in code files and cache the hilighted version
//  use highlighting library from codepaster.com


/*$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'highlighter' . DIRECTORY_SEPARATOR . 'Highlighter.php';

// music handler
class db_code extends db_file
{
	const DATABASE = 'code';
	
	const NAME = 'Code from Database';

	static function columns()
	{
		return array('id', 'Code', 'Highlighted', 'LineCount', 'Language', 'Filepath');
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
		return parent::get($request, $count, $error, get_class());
	}
	
	static function remove($file)
	{
		parent::remove($file, get_class());
	}
	
	static function cleanup()
	{
		parent::cleanup(get_class());
	}

}


?>
*/