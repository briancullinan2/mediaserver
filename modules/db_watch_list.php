<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_watch_list extends db_file
{
	const DATABASE = 'watch_list';
	
	const NAME = 'Changed Directories from Database';

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	static function handles($dir)
	{
		global $mysql;
		
		// changed directories or directories that don't exist in the database
		$db_files = $mysql->query(array(
				'SELECT' => 'files',
				'COLUMNS' => array('id', 'Filedate'),
				'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
			)
		);
		
		if( count($db_files) == 0 || date("Y-m-d h:i:s", filemtime($dir)) != $db_files[0]['Filedate'] )
		{
			return true;
		}
		
		return false;
	}

	static function handle($mysql, $file)
	{
		if(db_watch_list::handles($file))
		{
			$db_watch_list = $mysql->query(array(
					'SELECT' => db_watch::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_watch_list) == 0 )
			{
				$id = db_watch_list::add($mysql, $file);
			}
			else
			{
			}
			
		}
		
	}
	
	static function add($mysql, $file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = $file;
	
		print 'Adding changed directory: ' . $file . "\n";
		
		// add to database
		$id = $mysql->query(array('INSERT' => 'watch_list', 'VALUES' => $fileinfo));
		
		return $id;

		flush();
		
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		return parent::get($mysql, $request, $count, $error, get_class());
	}
	
	static function cleanup($mysql, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($mysql, $watched, $ignored, get_class());
	}
}

?>