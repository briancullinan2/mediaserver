<?php
// this controlls all of the inputing of data into the database
// to add extra type handling create a function that inserts data into the database based on a filepath
// add calls to that function in the getfile procedure

// how long to search for directories that have changed
define('DIRECTORY_SEEK_TIME',		30);

// how long to search for changed files or add new files
define('FILE_SEEK_TIME', 		   30);

// how long to clean up files
define('CLEAN_UP_BUFFER_TIME',				45);

// add 30 seconds becase the cleanup shouldn't take any longer then that
set_time_limit( DIRECTORY_SEEK_TIME + FILE_SEEK_TIME + CLEAN_UP_BUFFER_TIME);


$tm_start = array_sum(explode(' ', microtime()));

// this is an iterator to update the server database and all the media listings
// use some extra code so cron can be run from any directory
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// some things to take into consideration:
// Access the database in intervals of files, not every individual file
// Sleep so output can be recorded to disk or downloaded in a browser
// Only update files that don't exist in database, have changed timestamps, have changed in size

// start the page with a pre to output messages that can be viewed in a browser
?><script language="javascript">var timer=null;var same_count=0;var last_height=0;function body_scroll() {document.body.scrollTop = document.body.scrollHeight;timer=setTimeout('body_scroll()', 100);if(document.body.scrollHeight!=last_height) {last_height=document.body.scrollHeight;same_count=0;} else {same_count++;}if(same_count == 100) {clearTimeout(timer);}}timer=setTimeout('body_scroll()', 100)</script><pre><?php

// the cron script is useless if it has nowhere to store the information it reads
if(USE_DATABASE == false)
	exit;

// get the directories to watch from the watch database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get the watched directories
$watched_list = db_watch::get($mysql, array(), $count, $error);

// sort out the files we don't want watched they begin ith the ! (NOT) sign
$ignored = array();
$watched = array(); // remove missing indeces
foreach($watched_list as $index => $watch)
{
	if($watch['Filepath'][0] == '!')
	{
		$ignored[] = substr($watch['Filepath'], 1);
	}
	else
	{
		$watched[] = substr($watch['Filepath'], 1);
	}
}

// directories that have already been scanned used to prevent recursion
$dirs = array();

// the directories for the current state so we can start here the next time the script runs
$state = array();

// get previous state if it exists
if( file_exists(LOCAL_ROOT . 'state_dirs.txt') )
	$state = unserialize(implode("", @file(LOCAL_ROOT . "state_dirs.txt")));

$i = 0;

print_r($state);
	
// check state information
if( isset($state) && is_array($state) ) $state_current = array_pop($state);

// get starting index
if( isset($state_current) && isset($watched[$state_current['index']]) && $watched[$state_current['index']] == $state_current['file'] )
{
	$i = $state_current['index'];
}
elseif(isset($state_current))
{
	// if it isn't set in the watched list at all 
	//   something must be wrong with our state so reset it
	if($fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w"))
	{
		print "State cleared\n";
		fwrite($fp, '');
		fclose($fp);
	}
	$state = array();
}

if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($watched) && $_REQUEST['entry'] > 0)
	$i = $_REQUEST['entry'];

// loop through each watched folder and get a list of all the files
for($i; $i < count($watched); $i++)
{
	$watch = $watched[$i];

	$status = getdir($watch);
	
	// if exited because of time, then save state
	if( $status == false )
	{
		// record the current directory
		array_push($state, array('index' => $i, 'file' => $watch));
		
		// serialize and save
		print "State saved\n";
		if($fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w"))
		{
			fwrite($fp, serialize($state));
			fclose($fp);
		}
		
		// since it exited because of time we don't want to continue our for loop
		//   exit out of the loop so it start off in the same place next time
		break;
	}
	else
	{
		// clear state information
		if(file_exists(LOCAL_ROOT . "state_dirs.txt"))
		{
			if($fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w"))
			{
				print "State cleared\n";
				fwrite($fp, '');
				fclose($fp);
			}
		}
		
		// set the last updated time in the watched table
		$mysql->query(array('UPDATE' => 'watch', 'VALUES' => array('Lastwatch' => date("Y-m-d h:i:s")), 'WHERE' => 'Filepath = "' . $watch . '"'));
	}
	
	if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($watched) && $_REQUEST['entry'] > 0)
		break;
}


// clean up the watch_list and remove stuff that doesn't exist in watch anymore
db_watch_list::cleanup($mysql, $watched, $ignored);

// now scan some files
$tm_start = array_sum(explode(' ', microtime()));

do
{
	// get 1 folder from the database to search the files for
	$db_dirs = db_watch_list::get($mysql, array(), $count, $error);
	
	if(count($db_dirs) > 0)
	{
		$dir = $db_dirs[0]['Filepath'];
		
		if(in_array($dir, $ignored)) // just precautionary measure
			continue;
		
		print 'Searching directory: ' . $dir . "\n";
		flush();
		
		// search all the files in the directory
		
 		$count = 0;
		$error = '';
		
		// get directory contents
		$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
		
		foreach($files as $i => $file)
		{
		
			// if $file isn't this directory or its parent, 
			if ($file['Filename'] != '.' && $file['Filename'] != '..' && !is_dir($file['Filepath']))
			{
				getfile($file['Filepath']);
			}

		}
	
		// delete the selected folder from the database
		$mysql->query(array('DELETE' => 'watch_list', 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'));
	}

	// check if execution time is too long
	$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
	
	flush();
	
} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 );


// now do some cleanup
//exit;

foreach($GLOBALS['modules'] as $i => $module)
{
	if($module != 'fs_file')
		call_user_func_array($module . '::cleanup', array($mysql, $watched, $ignored));
}

// read all the folders that lead up to the watched folder
// these might be delete by cleanup, so check again because there are only a couple
for($i = 0; $i < count($watched); $i++)
{
	$folders = split(addslashes(DIRECTORY_SEPARATOR), $watched[$i]['Filepath']);
	$curr_dir = (realpath('/') == '/')?'/':'';
	// don't add the watch directory here because it must be added to the watch list first!
	$length = count($folders);
	unset($folders[$length-1]); // remove the blank at the end
	unset($folders[$length-2]); // remove the last folder which is the watch
	$between = false; // directory must be between an aliased path and a watched path
	// add the directories leading up to the watch
	for($j = 0; $j < count($folders); $j++)
	{
		if($folders[$j] != '')
		{
			$curr_dir .= $folders[$j] . DIRECTORY_SEPARATOR;
			// if using aliases then only add the revert from the watch directory to the alias
			// ex. Watch = /home/share/Pictures/, Alias = /home/share/ => /Shared/
			//     only /home/share/ is added here
			if(!USE_ALIAS || in_array($curr_dir, $GLOBALS['paths']) !== false)
			{
				// this allows for us to make sure that at least the beginning 
				//   of the path is an aliased path
				$between = true;
				// if the USE_ALIAS is true this will only add the folder
				//    if it is in the list of aliases
				getfile($curr_dir);
			}
			// but make an exception for folders between an alias and the watch path
			elseif(USE_ALIAS && $between)
			{
				getfile($curr_dir);
			}
		}
	}
}


// check if file is already in database
function getfile( $file )
{
	global $mysql;
	
	// pass the file to each module to test if it should handle it
	foreach($GLOBALS['modules'] as $i => $module)
	{
		// never pass is to fs_file, it is only used to internals in this case
		if($module != 'fs_file')
			call_user_func_array($module . '::handle', array($mysql, $file));
	}

}


// a function for iterating through each directory and returning the files
function getdir( $dir )
{
	global $dirs, $tm_start, $state, $mysql, $ignored;
					
	// check directory passed in, only add directory to watch list if it has changed
	$db_file = $mysql->query(array(
			'SELECT' => 'files',
			'COLUMNS' => array('id', 'Filedate'),
			'WHERE' => 'Filepath = "' . addslashes($dir) . '"'
		)
	);
	
	// make sure directory is not in ignored list
	if(in_array($dir, $ignored))
		return true; // return true and just pretend directory is complete
	
	if( count($db_file) == 0 || date("Y-m-d h:i:s", filemtime($dir)) != $db_file[0]['Filedate'] )
	{
		// add directory then continue into directory
		getfile( $dir );
		
		// add to watch list
		$mysql->query(array('INSERT' => 'watch_list', 'VALUES' => array('Filepath' => addslashes($dir))));
		
		print 'Queueing directory: ' . $dir . "\n";
	}
	
	
	$count = 0;
	$error = '';
	// get directory contents
	$files = fs_file::get(NULL, array('dir' => $dir, 'limit' => 32000), $count, $error, true);
	
	$i = 0;
	
	if( isset($state) && is_array($state) ) $state_current = array_pop($state);
	
	// check state for starting index
	if( isset($state_current) && isset($files[$state_current['index']]) && $files[$state_current['index']]['Filepath'] == $state_current['file'] )
	{
		$i = $state_current['index'];
	}
	elseif(isset($state_current))
	{
		// put it back on because it doesn't match
		array_push($state, $state_current);
	}
	
	print 'Looking for changes in: ' . $dir . "\n";
	flush();
	
	$max = count($files);
	// keep going until all files in directory have been read
	for($i; $i < $max; $i++)
	{
		$file = $files[$i]['Filepath'];
		
		// check if execution time is too long
		$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
		
		if( $secs_total > DIRECTORY_SEEK_TIME )
		{

			// reset previous state when time runs out
			$state = array();
		
			// save some state information
			array_push($state, array('index' => $i, 'file' => $file));
			
			return false;
		}
		

		// if $file isn't this directory or its parent, 
		if (substr(basename($file), 0, 1) != '.')
		{

			// get files recursively
			if( is_dir($file) )
			{
				// prevent recursion by making sure it isn't already in the list of directories
				if( in_array($file, $dirs) == false && in_array(realpath($file), $dirs) == false )
				{
					$dirs[] = $file;
					// add the real path incase they are different
					if(realpath($file) != $file)
						$dirs[] = realpath($file);
					
					// always descend and search for more directories
					$status = getdir( $file );
					
					// if the status is false then save current directory and return
					// do this here so we can reset the state when the time runs out
					if( $status == false )
					{
						array_push($state, array('index' => $i, 'file' => $file));
						
						return false;
					}
				}
			}
		}
	}
	
	
	return true;
	
}

?>
</pre>
