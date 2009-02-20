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
				// get a list of files to search recursively
				$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
				
				foreach($files as $i => $file)
				{
					if( is_dir($file['Filepath']) )
					{
					}
				}
				
			}
			
		}
		
	}
	
	static function add($mysql, $file)
	{
		// pull information from $info
		$fileinfo = array();
		$fileinfo['Filepath'] = $file;
	
		print 'Adding watch: ' . $file . "\n";
		
		// add to database
		$id = $mysql->query(array('INSERT' => 'watch', 'VALUES' => $fileinfo));
		
		return $id;
		
		flush();
		
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		return parent::get($mysql, $request, $count, $error, get_class());
	}
	
	static function cleanup($mysql, $watched, $ignored)
	{
		$watched = $mysql->query(array('SELECT' => db_watch::DATABASE, 'COLUMNS' => 'id,Filepath'));
		
		foreach($watched as $i => $watch)
		{
			if(!is_dir($watch['Filepath']))
			{
				$mysql->query(array('DELETE' => db_watch::DATABASE, 'WHERE' => 'id=' . $watch['id']));
			}
		}
	}

}

?>