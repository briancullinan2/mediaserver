<?php

define('MAX_TIME', 		   30);

// add 30 seconds becase the cleanup shouldn't take any longer then that
set_time_limit( MAX_TIME + 30 );

$tm_start = array_sum(explode(' ', microtime()));

// this is an iterator to update the server database and all the media listings

require 'include/common.php';

// include the id handler
require 'include/ID3/getid3.php';

// some things to take into consideration:
// Access the database in intervals of files, not every individual file
// Sleep so output can be recorded to disk or downloaded in a browser
// Only update files that don't exist in database, have changed timestamps, have changed in size

// get the directories to watch from the watch database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// set up id3 reader incase any files need it
$getID3 = new getID3();

// get the watched directories
$watched = $mysql->getWatched();

// start the page with a pre to output messages that can be viewed in a browser
print '<pre>';

// turn on output buffer
ob_start();

// the number of files scanned
$count = 0;

// directories that have already been scanned used to prevent recursion
$dirs = array();

// the directories for the current state so we can start here the next time the script runs
$state = array();

// get previous state if it exists
if( file_exists(SITE_LOCALROOT . 'state_dirs.txt') )
	$state = unserialize(implode("", @file(SITE_LOCALROOT . "state_dirs.txt")));

$i = 0;

// check state information
if( isset($state) && is_array($state) ) $state_current = array_pop($state);

// get starting index
if( isset($state_current) && isset($watched[$state_current['index']]) && $watched[$state_current['index']]['Filepath'] == $state_current['file'] )
{
	$i = $state_current['index'];
}
else
{
	// put it back on because it doesn't match
	array_push($state, $state_current);
}

// loop through each watched folder and get a list of all the files
for($i; $i < count($watched); $i++)
{
	$watch = $watched[$i];
	
	$status = getdir($watch['Filepath']);
	
	// if exited because of time then save state
	if( $status == false )
	{
		// record the current directory
		array_push($state, array('index' => $i, 'file' => $watch['Filepath']));
		
		// serialize and save
		$fp = fopen(SITE_LOCALROOT . "state_dirs.txt", "w");
		fwrite($fp, serialize($state));
		fclose($fp);
		
	}
	else
	{
		// clear state information
		unlink(SITE_LOCALROOT . "state_dirs.txt");
		
		// set the last updated time in the watched table
		
	}
}

// now do some cleanup

// first clear all the items that are no longer in the watch list
// since the watch is resolved all the items in watch have to start with the watched path
$where_str = '';
foreach($watched as $i => $watch)
{
	$where_str .= ' Filepath REGEXP "^' . $watch['Filepath'] . '" OR';
}
// remove last OR
$where_str = substr($where_str, 0, strlen($where_str)-2);
$where_str = ' !(' . $where_str . ')';

// remove items
$mysql->set('files', NULL, $where_str);


// store the ids of all the valid files for the other mediums to use
$ids = array();

// since all the ones not apart of a watched directory is removed, now just check is every file still in the database exists on disk
$files = $mysql->get('files', array('id', 'Filepath'));

// loop through each file
foreach($files as $i => $file)
{
	if( !file_exists($file['Filepath']) )
	{
		// remove row from database
		$mysql->set('files', NULL, array('id' => $file['id']));
		
		print 'Removing file: ' . $file['Filepath'] . "\n";
		
		// pause so browser can recieve data
		usleep(1);
		
		ob_flush();
	}
	else
	{
		// if it does exists rearrange to make a list of ids
		$ids[] = $file['id'];
	}
}

// use the ids array created above to check is the audio pointer exists

// now do the same thing for each recognized medium
$audios = $mysql->get('audio', array('id', 'Fileinfo'));
foreach($audios as $i => $audio)
{
	// remove rows that point to none existent files
	if( !in_array($audio['Fileinfo'], $ids) )
	{
		$mysql->set('audio', NULL, array('id' => $audio['id']));
		
		print 'Removing audio: ' . $audio['id'] . "\n";
	
		// pause so browser can recieve data
		usleep(1);
		
		ob_flush();
	}
}


// close output buffer
ob_end_clean();


// handle adding files to the files database
function add_file( $file, $id = NULL )
{
	global $mysql;

	print 'Adding file: ' . $file . "\n";

	// get file extension
	$ext = getExt($file);
	$type = getExtType($ext);
	
	$fileinfo = array();
	$fileinfo['Filepath'] = $file;
	$fileinfo['Filename'] = basename($file);
	$fileinfo['Filesize'] = filesize($file);
	$fileinfo['Filemime'] = getFileType($file);
	$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
	$fileinfo['Filetype'] = $type;
		
	// if the id is set then we are updating and entry
	if( $id != NULL )
	{
		// update database
		$fileid = $mysql->set('files', $fileinfo, array('id' => $id));
	
		return $id;
	}
	else
	{
		// add to database
		$fileid = $mysql->set('files', $fileinfo);
	
		return $fileid;
	}
}


// handle adding audio files to the database
function add_audio( $file, $id )
{
	global $mysql, $getID3;
	
	'Adding audio: ' . $file . "\n";
	
	$info = $getID3->analyze($file);
	getid3_lib::CopyTagsToComments($info);
	
	// pull information from $info
	$fileinfo = array();
	$fileinfo['Title'] = @$info['comments_html']['title'][0];
	$fileinfo['Artist'] = @$info['comments_html']['artist'][0];
	$fileinfo['Album'] = @$info['comments_html']['album'][0];
	$fileinfo['Track'] = @$info['comments_html']['track'][0];
	$fileinfo['Year'] = @$info['comments_html']['year'][0];
	$fileinfo['Genre'] = @$info['comments_html']['genre'][0];
	$fileinfo['Length'] = @$info['playtime_seconds'];
	$fileinfo['Comments'] = @$info['comments_html']['comments'][0];
	$fileinfo['Bitrate'] = @$info['bitrate'];
	$fileinfo['Fileinfo'] = $id;

	// add to database
	$id = $mysql->set('audio', $fileinfo);
	
	return $id;
	
}

// handle adding image files to the database
function add_image( $file, $id )
{
	global $mysql, $getID3;
	
	'Adding image: ' . $file . "\n";

	$info = $getID3->analyze($file);
}

// check if file is already in database
function getfile( $file )
{
	global $mysql;

	// check if it is in the database
	$db_file = $mysql->get('files', array('id', 'Filetype', 'Fileinfo', 'Filedate'), array('Filepath' => $file));
	
	if( count($db_file) == 0 )
	{
		
		// get file extension
		$ext = getExt($file);
		$type = getExtType($ext);
		
		// always add to file database
		$id = add_file($file);

		if( $type == 'audio' )
		{
			// try to get music information
			$fileid = add_audio($file, $id);
		}
		elseif( $type == 'image' )
		{
			// try to get exif info
			$fileid = add_image($file, $id);
		}

	}
	else
	{
		$id = $db_file[0]['id'];
	
		// check if medium information exists
		if( $db_file[0]['Filetype'] == 'audio' )
		{
			$db_audio = $mysql->get('audio', array('id'), array('id' => $db_file[0]['Fileinfo']));
			
			// try to get music information
			if( count($db_audio) == 0 )
			{
				$fileid = add_audio($file, $id);
			}
			else
			{
				// check if modified time on file is different
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = add_file($file, $db_file[0]['id']);
					
					$fileid = add_audio($file, $id);
				}
			}
		}
		elseif( $db_file[0]['Filetype'] == 'image' )
		{
			$db_image = $mysql->get('audio', array('id'), array('id' => $db_file[0]['Fileinfo']));
			
			// try to get exif info
			if( count($db_image) == 0 )
			{
				$fileid = add_image($file, $id);
			}
			else
			{
				// check if modified time on file is different
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = add_file($file, $db_file[0]['id']);
					
					$fileid = add_image($file, $id);
				}
			}
		}		
	}
	
	// add fileinfo id to the file from the mediums database
	if( isset($fileid) )
	{
		$mysql->set('files', array('Fileinfo' => $fileid), array('id' => $id));

		// pause so browser can recieve data
		usleep(1);
	}
	else
	{
		print 'Skipping medium: ' . $file . "\n";
	}
	
	ob_flush();

}


// a function for iterating through each directory and returning the files
function getdir( $dir )
{
	global $dirs, $count, $tm_start, $state;
	
	$files = scandir($dir);
	
	$i = 0;
	
	if( isset($state) && is_array($state) ) $state_current = array_pop($state);
	
	// check state for starting index
	if( isset($state_current) && isset($files[$state_current['index']]) && $files[$state_current['index']] == $state_current['file'] )
	{
		$i = $state_current['index'];
	}
	else
	{
		// put it back on because it doesn't match
		array_push($state, $state_current);
	}
	
	$max = count($files);
	// keep going until all files in directory have been read
	for($i; $i < $max; $i++)
	{
		$file = $files[$i];
		
		// check if execution time is too long
		$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
		
		if( $secs_total > MAX_TIME )
		{
			// reset previous state when time runs out
			$state = array();
		
			// save some state information
			array_push($state, array('index' => $i, 'file' => $file));
			
			return false;
		}
		

		// if $file isn't this directory or its parent, 
		// add it to the results array
		if ($file != '.' && $file != '..')
		{
			//if( $debug_count < 10000 ) $debug_count++;
			//else return;
			$count++;
			print 'Found: ' . $count . "\n";
			
			// don't resolve symbolic links here, the watch paths are already resolved, and we want to always add on to that path
			$new_file = $dir . $file;

			// get files recursively
			if( is_dir($new_file) )
			{
				// add a slash then add it to the filelist
				$new_file .= '/';
				
				// prevent recursion by making sure it isn't already in the list of directories
				if( in_array($new_file, $dirs) == false )
				{
					$dirs[] = $new_file;
				
					getfile( $new_file );

					$status = getdir( $new_file );
					
					// if the status is false then save current directory and return
					// do this here so we can reset the state when the time runs out
					if( $status == false )
					{
						array_push($state, array('index' => $i, 'file' => $file));
						
						return false;
					}
				}
			}
			else
			{
				// add it to the files list
				getfile( $new_file );
			}
		}
		
	}
	
	
	return true;
	
}

?>