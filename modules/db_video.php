<?php

//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -an -ss 00:00:03 -t 15 -an -r 1 -vframes 15 -y /home/share/Videos/%d.jpg';
//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -f mpeg -t 15 -ss 00:00:03 -sameq -y /home/share/Videos/out.mpeg';
//exec($cmd, $out, $ret);
//exit();

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_video extends db_file
{
	const DATABASE = 'video';
	
	const NAME = 'Video from Database';

	static function columns()
	{
		return array('id', 'Length', 'Bitrate', 'VideoBitrate', 'AudioBitrate', 'Title', 'Comments', 'Channels', 'Resolution', 'FrameRate', 'Filepath');
	}
	
	static function handles($file)
	{
				
		// get file extension
		$ext = getExt(basename($file));
		$type = getExtType($ext);
		
		if( $type == 'video' )
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
			$db_video = $database->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_video) == 0 )
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
					$id = self::add($database, $file, $db_video[0]['id']);
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
		$fileinfo['Comments'] = @$info['comments_html']['comments'][0];
		$fileinfo['Bitrate'] = @$info['bitrate'];
		$fileinfo['Length'] = @$info['playtime_seconds'];
		$fileinfo['Channels'] = @$info['audio']['channels'];
		$fileinfo['AudioBitrate'] = @$info['audio']['bitrate'];
		$fileinfo['VideoBitrate'] = @$info['video']['bitrate'];
		$fileinfo['Resolution'] = @$info['video']['resolution_x'] . 'x' . @$info['video']['resolution_y'];
		$fileinfo['FrameRate'] = @$info['video']['frame_rate'];
		
		return $fileinfo;
	}

	static function add($database, $file, $video_id = NULL)
	{
		// pull information from $info
		$fileinfo = self::getInfo($file);
	
		if( $video_id != NULL )
		{
			print 'Modifying video: ' . $file . "\n";
			
			// update database
			$id = $database->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $video_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding video: ' . $file . "\n";
			
			// add to database
			$id = $database->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
		
		flush();
		
	}
	
	// output provided file to given stream
	static function out($database, $file)
	{
		// check to make sure file is valid
		if(is_file($file))
		{
			$files = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($files) > 0)
			{				
				$file = $files[0];
				
				if($fp = fopen($file['Filepath'], 'rb'))
					return $fp;
			}
		}
		return false;
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
