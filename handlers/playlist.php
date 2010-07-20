<?php

// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_playlist()
{
	return array(
		'name' => 'Playlists',
		'description' => 'Load playlists for later playback.',
		'database' => array(
			'SongCount' => 'INT',
			'Files' => 'TEXT',
			'Filepath' => 'TEXT'
		),
	);
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_playlist($file)
{
	// get file extension
	$ext = getExt($file);
	
	switch($ext)
	{
		case 'wpl':
		case 'm3u':
			return true;
		case 'txt':
			// read in the buffer size from the file and check to see if it even contains a file path
			if(is_readable($file) && $fp = fopen($file, 'rb'))
			{
				$buffer = fread($fp, setting('buffer_size'));
				fclose($fp);
				$count = preg_match('/(([^\\\\\\/\\:\\*\\?\\<\\>\\|]+[\\\\\\/])+[^\\\\\\/\\:\\*\\?\\<\\>\\|]+\.[a-z0-9]+)([^a-z0-9]|$)/i', $buffer, $matches);
				if($count > 0 && trim($matches[1]) != '')
					return true;
			}
		default:
			return false;
	}
	
	return false;

}

/** 
 * Implementation of handle
 * @ingroup handle
 */
function add_playlist($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	if(handles($file, 'playlist'))
	{
		// check to see if it is in the database
		$db_playlist = $GLOBALS['database']->query(array(
				'SELECT' => 'playlist',
				'COLUMNS' => 'id',
				'WHERE' => 'Filepath = "' . addslashes($file) . '"',
				'LIMIT' => 1
			)
		, false);
		
		$fileinfo = array();
		$fileinfo['Filepath'] = addslashes($file);
		
		// try to get music information
		if( count($db_playlist) == 0 )
		{
			raise_error('Adding playlist: ' . $file, E_DEBUG);
			
			// only get files if we have to
			$paths = get_playlist_info($file);
			
			$fileinfo['SongCount'] = count($paths);
			$fileinfo['Files'] = addslashes(serialize($paths));
			
			$id = $GLOBALS['database']->query(array('INSERT' => 'playlist', 'VALUES' => $fileinfo), false);
			
			return $id;
		}
		elseif($force)
		{
			raise_error('Modifying playlist: ' . $file, E_DEBUG);
			
			// only get files if we have to
			$paths = get_playlist_info($file);
			
			$fileinfo['SongCount'] = count($paths);
			$fileinfo['Files'] = addslashes(serialize($paths));
			
			$id = $GLOBALS['database']->query(array('UPDATE' => 'playlist', 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $db_playlist[0]['id']), false);
			
			return $db_playlist[0]['id'];
		}

	}
	return false;
}

/** 
 * Helper function
 */
function get_playlist_info($file)
{
	$file = str_replace('/', DIRECTORY_SEPARATOR, $file);
	
	$paths = array();
	
	// open playlist and parse out paths
	if($fp = @fopen($file, 'rb'))
	{
		$tmp_files = array();
		switch(getFileType($file))
		{
			case 'WPL':
				while(!feof($fp))
				{
					$buffer = trim(fgets($fp, 4096));
					$count = preg_match('/\<media src="([^"]*)"( ?(t|c)id=| ?\/\>)/i', $buffer, $matches);
					if($count > 0 && trim($matches[1]) != '')
					{
						$tmp_files[] = str_replace(array('&apos;', '&amp;'), array('\'', '&'), strip_tags(trim($matches[1])));
					}
				}
			case 'M3U':
				while(!feof($fp))
				{
					$buffer = trim(fgets($fp, 4096));
					$count = preg_match('/^\s*([^#])(.*)/i', $buffer, $matches);
					if($count > 0 && trim($matches[1] . $matches[2]) != '')
					{
						$tmp_files[] = urldecode(trim($matches[1] . $matches[2]));
					}
				}
			case 'TXT':
				// try and find some paths or something
				$buffer = fgets($fp, 4096);
				while(!feof($fp))
				{
					$buffer = trim(fgets($fp, 4096));
					$buffer = str_replace(chr(0), '', $buffer);
					// there must be at least 1 directory
					$count = preg_match('/(([^\\\\\\/\\:\\*\\?\\<\\>\\|]+[\\\\\\/])+[^\\\\\\/\\:\\*\\?\\<\\>\\|]+\.[a-z0-9]+)([^a-z0-9]|$)/i', $buffer, $matches);
					if($count > 0 && trim($matches[1]) != '')
					{
						$tmp_files[] = trim($matches[1]);
					}
				}
				
		}
		fclose($fp);
		
		// now process the matches
		$common_pieces = array();
		if(isset($tmp_files[0])) $common_pieces = array_unique(split('[^a-zA-Z0-9]', $tmp_files[0]));
		
		// remove some common parts
		for($i = 0; $i < min(6, count($tmp_files)); $i++)
		{
			$common_pieces = array_intersect($common_pieces, array_unique(split('[^a-zA-Z0-9]', $tmp_files[$i])));
			if(count($common_pieces) / count(array_unique(split('[^a-zA-Z0-9]', $tmp_files[$i]))) > .40)
			{
				// remove some
				unset($common_pieces[count($common_pieces)-1]);
			}
		}
		
		// go through each file and do multiple steps from most presice to most general and try to find the file
		foreach($tmp_files as $i => $file)
		{
			// remove extension we don't care what format it is
			$file = substr($file, 0, strrpos($file, '.'));
			$file = str_replace('\\', '/', $file);
			$dir1 = substr($file, strrpos($file, '/'));
			$dir1 = substr($file, strrpos(substr($file, 0, strlen($file) - strlen($dir1)), '/'));
			$dir2 = substr($file, strrpos(substr($file, 0, strlen($file) - strlen($dir1)), '/'));
			
			// TODO put alias stuff here
			$result = array();
			
			// check minimized filename and directories
			$tokens = tokenize($file);
			$valid_pieces = $tokens['Some'];
			
			// remove other wierdness
			if(count($valid_pieces) > 5)
			{
				foreach($valid_pieces as $i => $piece)
				{
					if(strtoupper($valid_pieces[$i]) == $valid_pieces[$i] || in_array($valid_pieces[$i], $common_pieces))
					{
						unset($valid_pieces[$i]);
					}
				}
			}
			
			// if there are no valid parts then discard
			if(count($valid_pieces) == 0)
				unset($tmp_files[$i]);
				
			// search for file using terms
			$result = get_files(array('search' => join(' ', $valid_pieces), 'limit' => 1, 'cat' => 'audio'), $tmp_count, 'audio');
			if($tmp_count > 0)
			{
				$paths[] = array('id' => $result[0]['id'], 'Filepath' => $result[0]['Filepath']);
				continue;
			}

			// search for file using terms
			$result = get_video(array('search' => join(' ', $valid_pieces), 'limit' => 1, 'cat' => 'video'), $tmp_count, 'video');
			if($tmp_count > 0)
			{
				$paths[] = array('id' => $result[0]['id'], 'Filepath' => $result[0]['Filepath']);
				continue;
			}
			
			// search for file using terms
			$result = get_files(array('search' => join(' ', $valid_pieces), 'limit' => 1, 'cat' => 'files'), $tmp_count, 'files');
			if($tmp_count > 0)
			{
				$paths[] = array('id' => $result[0]['id'], 'Filepath' => $result[0]['Filepath']);
				continue;
			}

			// file can't be found
			raise_error('Error: Can\'t find file from playlist ' . $file, E_DEBUG);
		}
	}
	
	// loop through files and get centralized id
	//   make a list of file ids and filepaths
	
	return $paths;
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_playlist($request, &$count, $handler = NULL)
{
	$files = array();
	
	// change the cat to the table we want to use
	$request['cat'] = validate(array('cat' => 'audio'), 'cat');
	
	if(isset($request['dir']))
	{
	
		$playlist = get_files($request, $tmp_count, 'files');
		
		// get all the files from the playlist
		if(count($playlist) > 0)
		{
			$files = unserialize($playlist[0]['Files']);
		}
		else
		{
			$count = 0;
		}
	}
	else
	{
		$files = get_files($request, $count, 'files');
	}
	
	return $files;
}
