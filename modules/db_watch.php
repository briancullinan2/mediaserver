<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_watch extends db_file
{
	const DATABASE = 'watch';
	
	const NAME = 'Watched Directories from Database';

	static function columns()
	{
		return array('id', 'Lastwatch', 'Filepath');
	}
	
	static function handles($file)
	{
		// add ending backslash
		if( substr($file, strlen($file)-1) != DIRECTORY_SEPARATOR ) $file .= DIRECTORY_SEPARATOR;
			
		if($file[0] == '!' || $file[0] == '^')
		{
			$file = substr($file, 1);
			if(is_dir($file))
			{
				return true;
			}
		}
		
		return false;
	}

	static function handle($mysql, $file)
	{
		if(db_watch::handles($file))
		{
			// add ending backslash
			if( substr($file, strlen($file)-1) != DIRECTORY_SEPARATOR ) $file .= DIRECTORY_SEPARATOR;
			
			$db_watch = $mysql->query(array(
					'SELECT' => db_watch::DATABASE,
					'COLUMNS' => array('id'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_watch) == 0 )
			{
				$id = db_watch::add($mysql, $file);
			}
			else
			{
				// just pass the first directories to watch_list module
				return db_watch_list::handle($mysql, substr($file, 1));
			}
			
		}
		
	}
	
	static function add($mysql, $file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
	
		print 'Adding watch: ' . $file . "\n";
		
		// add to database
		$id = $mysql->query(array('INSERT' => db_watch::DATABASE, 'VALUES' => $fileinfo));
		
		return $id;
		
		flush();
		
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		return parent::get($mysql, $request, $count, $error, get_class());
	}
	
	static function cleanup($mysql, $watched, $ignored)
	{
		$watched = $mysql->query(array('SELECT' => db_watch::DATABASE, 'COLUMNS' => 'id, Filepath'));
		
		foreach($watched as $i => $watch)
		{
			if(!is_dir(substr($watch['Filepath'], 1)))
			{
				$mysql->query(array('DELETE' => db_watch::DATABASE, 'WHERE' => 'id=' . $watch['id']));
			}
		}
	}

}

?>