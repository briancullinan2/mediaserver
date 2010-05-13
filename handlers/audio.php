<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_audio()
{
	return array(
		'name' => 'Audio',
		'description' => 'Store audio id3 information.',
		'database' => array(
			'Filepath' 	=> 'TEXT',
			'Title'		=> 'TEXT',
			'Artist' 	=> 'TEXT',
			'Album'		=> 'TEXT',
			'Track'		=> 'INT',
			'Year'		=> 'INT',
			'Genre'		=> 'TEXT',
			'Length'	=> 'DOUBLE',
			'Comments'	=> 'TEXT',
			'Bitrate'	=> 'DOUBLE'
		),
		'depends on' => array('getid3_installed'),
	);
}

/** 
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_audio()
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
function handles_audio($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	// get file extension
	$type = getExtType($file);
	
	if( $type == 'audio' )
	{
		return true;
	}

	return false;

}

function get_audio_info($file)
{
	$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
	
	$info = $GLOBALS['getID3']->analyze($file);
	getid3_lib::CopyTagsToComments($info);

	$fileinfo = array();
	$fileinfo['Filepath'] = addslashes(str_replace('\\', '/', $file));
	$fileinfo['Title'] = @addslashes($info['comments_html']['title'][0]);
	$fileinfo['Artist'] = @addslashes($info['comments_html']['artist'][0]);
	$fileinfo['Album'] = @addslashes($info['comments_html']['album'][0]);
	$fileinfo['Track'] = @addslashes($info['comments_html']['track'][0]);
	$fileinfo['Year'] = @addslashes($info['comments_html']['year'][0]);
	$fileinfo['Genre'] = @addslashes($info['comments_html']['genre'][0]);
	$fileinfo['Length'] = @addslashes($info['playtime_seconds']);
	$fileinfo['Comments'] = @addslashes($info['comments_html']['comments'][0]);
	$fileinfo['Bitrate'] = @addslashes($info['bitrate']);
	
	return $fileinfo;
}

