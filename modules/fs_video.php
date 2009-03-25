<?php

//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -an -ss 00:00:03 -t 15 -an -r 1 -vframes 15 -y /home/share/Videos/%d.jpg';
//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -f mpeg -t 15 -ss 00:00:03 -sameq -y /home/share/Videos/out.mpeg';
//exec($cmd, $out, $ret);
//exit();

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_video extends fs_file
{
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
	
	static function getInfo($file)
	{
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$info = $GLOBALS['getID3']->analyze($file);
		getid3_lib::CopyTagsToComments($info);
		
		$fileinfo = array();
		$fileinfo['Filepath'] = str_replace('\\', '/', $file);
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
	
	static function get($request, &$count, &$error)
	{
		return parent::get(NULL, $request, $count, $error, get_class());
	}


}

?>
