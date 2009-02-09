<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?

require_once LOCAL_ROOT . 'modules/db_file.php';

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_archive extends db_file
{
	const DATABASE = 'archives';
	
	const NAME = 'Archives';

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt($file);
		
		switch($ext)
		{
			case 'zip':
			case 'rar':
			case 'gzip':
			case 'szip':
			case 'tar':
				return true;
			default:
				return false;
		}
		
		return false;

	}

	// this function determines if the file qualifies for this type and handles it according
	static function handle($mysql, $file)
	{
		// files always qualify, we are going to log every single one!
		
		/*if(db_file::handles($file))
		{
			
			// check if it is in the database
			$fs_archive = $mysql->get('files', 
				array(
					'SELECT' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				$id = fs_archive::add($mysql, $file);
			}
			else
			{
				$filename = $file;

				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($filename)) != $fs_archive[0]['Filedate'] )
				{
					$id = fs_archive::add($mysql, $file, $fs_archive[0]['id']);
				}
				else
				{
					print 'Skipping file: ' . $file . "\n";
				}
				
			}
			
		}*/
		
	}
	
	static function get($mysql, $props)
	{
		$files = array();
		
		print_r($props);
		
		if (is_file($props['FILE']))
		{
			if(fs_archive::handles($props['FILE']))
			{
				$info = $GLOBALS['getID3']->analyze($props['FILE']);
				$tmp_files = $info['zip']['central_directory'];
				foreach($tmp_files as $i => $file)
				{
					$info = array();
					$info['Filepath'] = $props['FILE'] . DIRECTORY_SEPARATOR . $file['filename'];
					
					$files[] = $info;
				}
			}
		}
		
		return $files;

	}


	static function cleanup($mysql, $watched)
	{
	}
}

?>