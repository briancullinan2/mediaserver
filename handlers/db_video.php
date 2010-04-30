<?php

//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -an -ss 00:00:03 -t 15 -an -r 1 -vframes 15 -y /home/share/Videos/%d.jpg';
//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -f mpeg -t 15 -ss 00:00:03 -sameq -y /home/share/Videos/out.mpeg';
//exec($cmd, $out, $ret);
//exit();
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_file.php';

// music handler
class db_video extends db_file
{
	const DATABASE = 'video';
	
	const NAME = 'Video from Database';

	static function init()
	{
		if(setting('exists_getid3'))
		{
			// include the id handler
			include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
			
			// set up id3 reader incase any files need it
			$GLOBALS['getID3'] = new getID3();
		}
		else
			PEAR::raiseError('getID3() missing from include directory! Archive handlers cannot function properly.', E_DEBUG);
	}

	static function columns()
	{
		return array_keys(self::struct());
	}
	
	static function struct()
	{
		return array(
			'Filepath' 		=> 'TEXT',
			'Title'			=> 'TEXT',
			'Length'		=> 'DOUBLE',
			'Comments'		=> 'TEXT',
			'Bitrate'		=> 'DOUBLE',
			'VideoBitrate'	=> 'DOUBLE',
			'AudioBitrate'	=> 'DOUBLE',
			'Channels'		=> 'INT',
			'FrameRate'		=> 'INT',
			'Resolution'	=> 'TEXT'
		);
	}
	
	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
				
		// get file extension
		$type = getExtType($file);
		
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
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			// try to get music information
			if( count($db_video) == 0 )
			{
				return self::add($file);
			}
			elseif($force)
			{
				return self::add($file, $db_video[0]['id']);
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
		if(!isset($GLOBALS['getID3']))
			self::init();
			
		// pull information from $info
		$fileinfo = self::getInfo($file);
	
		if( $video_id != NULL )
		{
			PEAR::raiseError('Adding video: ' . $file, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
			
			return $id;
		}
		else
		{
			PEAR::raiseError('Modifying video: ' . $file, E_DEBUG);
			
			// update database
			$id = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $video_id), false);
		
			return $video_id;
		}
		
	}
	
	static function get($request, &$count)
	{
		return parent::get($request, $count, get_class());
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
