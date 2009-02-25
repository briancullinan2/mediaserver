<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_audio extends db_file
{
	const DATABASE = 'audio';
	
	const NAME = 'Audio from Database';

	static function columns()
	{
		return array('id', 'Track', 'Title', 'Artist', 'Album', 'Genre', 'Year', 'Length', 'Bitrate', 'Comments', 'Filepath');
	}
	

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt(basename($file));
		$type = getExtType($ext);
		
		if( $type == 'audio' )
		{
			return true;
		}
	
		return false;

	}

	static function handle($database, $file)
	{
		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_audio = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_audio) == 0 )
			{
				$fileid = self::add($database, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $database->query(array(
						'SELECT' => db_file::DATABASE,
						'COLUMNS' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = self::add($database, $file, $db_audio[0]['id']);
				}
				
			}

		}
		
	}
	
	static function getInfo($file)
	{
		$info = $GLOBALS['getID3']->analyze($file);
		getid3_lib::CopyTagsToComments($info);

		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
		$fileinfo['Title'] = @$info['comments_html']['title'][0];
		$fileinfo['Artist'] = @$info['comments_html']['artist'][0];
		$fileinfo['Album'] = @$info['comments_html']['album'][0];
		$fileinfo['Track'] = @$info['comments_html']['track'][0];
		$fileinfo['Year'] = @$info['comments_html']['year'][0];
		$fileinfo['Genre'] = @$info['comments_html']['genre'][0];
		$fileinfo['Length'] = @$info['playtime_seconds'];
		$fileinfo['Comments'] = @$info['comments_html']['comments'][0];
		$fileinfo['Bitrate'] = @$info['bitrate'];
		
		return $fileinfo;
	}

	static function add($database, $file, $audio_id = NULL)
	{
		// pull information from $info
		$fileinfo = self::getInfo($file);
	
		if( $audio_id != NULL )
		{
			print 'Modifying audio: ' . $file . "\n";
			
			// update database
			$id = $database->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $audio_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding audio: ' . $file . "\n";
			
			// add to database
			$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
		
		flush();
		
	}
	
	
	static function get($database, $request, &$count, &$error)
	{
		return parent::get($database, $request, $count, $error, get_class());
	}


	static function cleanup($database, $watched, $ignored)
	{
		// call default cleanup function
		parent::cleanup($database, $watched, $ignored, get_class());
	}

}

?>
