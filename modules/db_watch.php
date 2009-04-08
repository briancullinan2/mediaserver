<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_watch extends db_file
{
	const DATABASE = 'watch';
	
	const NAME = 'Watched Directories from Database';
	
	const INTERNAL = true;

	static function columns()
	{
		return array('id', 'Filepath');
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'Filepath' 	=> 'TEXT',
			// add a space to the end so that it can be NULL in the database
			'Lastwatch' => 'DATETIME '
		);
	}
	
	static function handles($file)
	{
		$dir = str_replace('\\', '/', $file);
		
		if($file[0] == '!' || $file[0] == '^')
		{
			$file = substr($file, 1);
			if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)))
			{
				return true;
			}
		}
		
		return false;
	}

	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
			
		if(self::handles($file))
		{
			
			// add ending backslash
			if( substr($file, strlen($file)-1) != '/' ) $file .= '/';
			
			$db_watch = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			);
			
			if( count($db_watch) == 0 )
			{
				return self::add($file);
			}
			else
			{
				// just pass the first directories to watch_list module
				return db_watch_list::handle(substr($file, 1));
			}
			
		}
		return false;
	}
	
	static function add($file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		log_error('Adding watch: ' . $file);
		
		// add to database
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
		// add to watch_list and to files database
		db_watch_list::handle(substr($file, 1));
		
		db_watch_list::handle_file(substr($file, 1));
		
		return $id;
		
	}
	
	static function get($request, &$count, &$error)
	{
		$files = parent::get($request, $count, $error, get_class());
		
		// make some changes
		foreach($files as $i => $file)
		{
			$files[$i]['Filepath'] = substr($file['Filepath'], 1);
		}
		
		return $files;
	}
	
	static function remove($file)
	{
		// watch directories are never removed by the script
		return false;
	}
	
	static function cleanup()
	{
		// do not do anything, watch directories are completely managed
		return false;
	}

}

?>