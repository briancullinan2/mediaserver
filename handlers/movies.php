<?php

// search IMDb for movie
// cache useful information
// search for the title of single video files, if it exists in a directory call movies
//  use parseFilename to search with

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_movies()
{
	return array(
		'name' => 'Movies',
		'description' => 'Load movie files and download information.',
		'database' => array(
			'Title' => 'TEXT',
			'Director' => 'TEXT',
			'ReleaseDate' => 'DATETIME',
			'Genre' => 'TEXT',
			'Plot' => 'TEXT',
			'Cast' => 'TEXT',
			'Runtime' => 'INT',
			'Language' => 'TEXT',
			'AspectRatio' => 'DOUBLE',
			'Filepath' => 'TEXT',
		),
	);
}

/** 
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_movies()
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
function handles_movies($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	// parse through the file path and try to find a zip
	$paths = split('/', $file);
	$last_path = '';
	$last_ext = '';
	foreach($paths as $i => $tmp_file)
	{
		// this will continue until either the end of the requested file (a .zip extension for example)
		// or if the entire path exists then it must be an actual folder on disk with a .zip in the name
		if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path . $tmp_file)) || $last_path == '')
		{
			$last_ext = getExt($last_path . $tmp_file);
			$last_path = $last_path . $tmp_file . '/';
		}
		else
		{
			// if the last path exists and the last $ext is an archive then we know the path is inside an archive
			if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, $last_path)))
			{
				// we can break
				break;
			}
		}
	}
	
	$tokens = tokenize($last_path);
	
	// if it is an extracted dvd image
	if(is_dir($last_path))
	{
		if(in_array('video_ts', $tokens['Unique']))
		{
			return true;
		}
	}
	// if it is a potential movie in compressed file format
	elseif(handles($file, 'video') && in_array('movies', $tokens['Unique']))
	{
		return true;
	}
	// if it is an iso image with a video_ts folder in it
	elseif($last_ext == 'iso')
	{
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if(isset($info['iso']) && isset($info['iso']['directories']))
		{
			
		}
	}
	
	return false;

}

/** 
 * Implementation of handle
 * @ingroup handle
 */
function handle_movies($file, $force = false)
{
	return false;
}

/** 
 * Helper function
 */
function get_movie_info($filename)
{
	return array();
}

function out($file)
{
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_db_movies($request, &$count)
{
	return get_db_file($request, $count, 'db_movies');
}

/** 
 * Implementation of remove_handler
 * @ingroup remove_handler
 */
function remove_db_movies($file)
{
	//parent::remove($file, 'db_movies');
}

/** 
 * Implementation of cleanup_handler
 * @ingroup cleanup_handler
 */
function cleanup_db_movies()
{
	// call default cleanup function
	//db_file::cleanup('db_movies');
}


