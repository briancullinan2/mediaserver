<?php

//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -an -ss 00:00:03 -t 15 -an -r 1 -vframes 15 -y /home/share/Videos/%d.jpg';
//$cmd = 'ffmpeg -i /home/share/Videos/john.adams.part2.hdtv-lol.avi -f mpeg -t 15 -ss 00:00:03 -sameq -y /home/share/Videos/out.mpeg';
//exec($cmd, $out, $ret);
//exit();

// music handler
class fs_video extends fs_file
{
	const NAME = 'Video from Database';
	
	// define if this module is internal so templates won't try to use it
	const INTERNAL = false;

	static function init()
	{
		// include the id handler
		require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
		
		// set up id3 reader incase any files need it
		$GLOBALS['getID3'] = new getID3();
	}

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
		if(!isset($GLOBALS['getID3']))
			self::init();
			
		$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
		
		$fileinfo = db_video::getInfo($file);
		$fileinfo['id'] = bin2hex($file);
		$fileinfo['Filepath'] = stripslashes($fileinfo['Filepath']);
		
		return $fileinfo;
	}
	
	static function get($request, &$count, &$error)
	{
		return parent::get($request, $count, $error, get_class());
	}


}

?>
