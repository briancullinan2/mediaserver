<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_video()
{
	return array(
		'name' => 'Videos',
		'description' => 'Loads video files.',
		'database' => array(
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
		),
	);
}

/**
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_video()
{
	if(isset($GLOBALS['getID3']))
		return;
		
	// include the id handler
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
	
	// set up id3 reader incase any files need it
	$GLOBALS['getID3'] = new getID3();
}

/**
 * Implementation of handles
 * @ingroup handles
 */
function handles_video($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
			
	// get file extension
	$type = getExtType($file);
	
	if( $type == 'video' )
	{
		return true;
	}

	return false;

}

/**
 * Helper function
 */
function get_video_info($file)
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

