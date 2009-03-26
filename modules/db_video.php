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

	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_video = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_video) == 0 )
			{
				$fileid = self::add($file);
				return true;
			}
			elseif($force)
			{
				$id = self::add($file, $db_video[0]['id']);
				return 1;
			}

		}
		return false;
	}
	
	static function getInfo($file)
	{
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$info = $GLOBALS['getID3']->analyze($file);
		getid3_lib::CopyTagsToComments($info);
		
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $file));
		
		$fileinfo['Title'] = @addslashes($info['comments_html']['title'][0]);
		$fileinfo['Comments'] = @addslashes($info['comments_html']['comments'][0]);
		$fileinfo['Bitrate'] = @$info['bitrate'];
		$fileinfo['Length'] = @$info['playtime_seconds'];
		$fileinfo['Channels'] = @$info['audio']['channels'];
		$fileinfo['AudioBitrate'] = @$info['audio']['bitrate'];
		$fileinfo['VideoBitrate'] = @$info['video']['bitrate'];
		$fileinfo['Resolution'] = @$info['video']['resolution_x'] . 'x' . @$info['video']['resolution_y'];
		$fileinfo['FrameRate'] = @$info['video']['frame_rate'];
		
		return $fileinfo;
	}

	static function add($file, $video_id = NULL)
	{
		// pull information from $info
		$fileinfo = self::getInfo($file);
	
		if( $video_id != NULL )
		{
			log_error('Modifying video: ' . $file);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $video_id));
		
			return $audio_id;
		}
		else
		{
			log_error('Adding video: ' . $file);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
			
			return $id;
		}
		
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
		// call default cleanup function
		parent::cleanup(get_class());
	}

}

?>
