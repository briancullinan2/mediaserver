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
?><pre><?php

// the cron script is useless if it has nowhere to store the information it reads
if(USE_DATABASE == false)
	exit;

// get the directories to watch from the watch database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get the watched directories
$watched = $mysql->getWatched();

// sort out the files we don't want watched they begin ith the ! (NOT) sign
$ignored = array();
foreach($watched as $index => $watch)
{
	if($watch['Filepath'][0] == '!')
	{
		$ignored[] = substr($watch['Filepath'], 1);
		unset($watched[$index]);
	}
}
$watched = array_values($watched); // remove missing indeces

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
if( isset($state_current) && isset($watched[$state_current['index']]) && $watched[$state_current['index']]['Filepath'] == $state_current['file'] )
{
	$i = $state_current['index'];
}
elseif(isset($state_current))
{
	// if it isn't set in the watched list at all 
	//   something must be wrong with our state so reset it
	@unlink(LOCAL_ROOT . "state_dirs.txt");
	$state = array();
}

// loop through each watched folder and get a list of all the files
for($i; $i < count($watched); $i++)
{
	$watch = $watched[$i];

	$status = getdir($watch['Filepath']);
	
	// if exited because of time, then save state
	if( $status == false )
	{
		// record the current directory
		array_push($state, array('index' => $i, 'file' => $watch['Filepath']));
		
		// serialize and save
		print "State saved\n";
		$fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w");
		fwrite($fp, serialize($state));
		fclose($fp);
		
		// since it exited because of time we don't want to continue our for loop
		//   exit out of the loop so it start off in the same place next time
		break;
	}
	else
	{
		// clear state information
		if(file_exists(LOCAL_ROOT . "state_dirs.txt"))
		{
			@unlink(LOCAL_ROOT . "state_dirs.txt");
			if(file_exists(LOCAL_ROOT . "state_dirs.txt")) $fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w");
			if(isset($fp))
			{
				print "State cleared\n";
				fwrite($fp, '');
				fclose($fp);
			}
		}
		
		// set the last updated time in the watched table
		$mysql->set('watch', array('Lastwatch' => date("Y-m-d h:i:s")), array('Filepath' => addslashes($watch['Filepath'])));
	}
}


// clean up the watch_list and remove stuff that doesn't exist in watch anymore
$where_str = '';
foreach($watched as $i => $watch)
{
	$where_str .= ' Filepath REGEXP "^' . addslashes(addslashes($watch['Filepath'])) . '" OR';
}
// remove last OR
$where_str = substr($where_str, 0, strlen($where_str)-2);
$where_str = ' !(' . $where_str . ')';
// clean up items that are in the ignore list
foreach($ignored as $i => $ignore)
{
	$where_str = 'Filepath REGEXP "^' . addslashes(addslashes($ignore)) . '" OR ' . $where_str;
}

// remove items
$mysql->set('watch_list', NULL, $where_str);

print 'Cleaned watch_list.' . "\n";
flush();


// now scan some files
$tm_start = array_sum(explode(' ', microtime()));

do
{

	// get 1 folder from the database to search the files for
	$db_dirs = $mysql->get('watch_list', 
		array(
			'SELECT' => 'Filepath',
			'OTHER' => 'LIMIT 1'
		)
	);
	
	if(count($db_dirs) > 0)
	{
		$dir = $db_dirs[0]['Filepath'];
		
		if(in_array($dir, $ignored)) // just precautionary measure
			continue;
		
		print 'Searching directory: ' . $dir . "\n";
		
		// search all the files in the directory
		
		// get directory contents
		$files = fs_file::get(NULL, array('dir' => $dir));
		
		foreach($files as $i => $file)
		{
		
			// if $file isn't this directory or its parent, 
			if ($file != '.' && $file != '..' && !is_dir($dir . $file))
			{
				getfile($dir . $file);
			}

		}
	
		// delete the selected folder from the database
		$mysql->set('watch_list', NULL, array('Filepath' => addslashes($dir)));
	}

	// check if execution time is too long
	$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
	
	flush();
	
} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 );


// now do some cleanup

foreach($GLOBALS['modules'] as $i => $module)
{
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
	global $mysql, $modules;
	
	// pass the file to each module to test if it should handle it
	foreach($modules as $i => $name)
	{
		call_user_func_array($name . '::handle', array($mysql, $file));
	}

}


// a function for iterating through each directory and returning the files
function getdir( $dir )
{
	global $dirs, $tm_start, $state, $mysql, $ignored;
					
	// check directory passed in, only add directory to watch list if it has changed
	$db_file = $mysql->get('files', 
		array(
			'SELECT' => array('id', 'Filedate'),
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
		$mysql->set('watch_list', array('Filepath' => addslashes($dir)), NULL);
		
		print 'Queueing directory: ' . $dir . "\n";
		
		flush();

	}
	
	
	// get directory contents
	$files = fs_file::get(NULL, array('dir' => $dir));
	
	$i = 0;
	
	if( isset($state) && is_array($state) ) $state_current = array_pop($state);
	
	// check state for starting index
	if( isset($state_current) && isset($files[$state_current['index']]) && $files[$state_current['index']] == $state_current['file'] )
	{
		$i = $state_current['index'];
	}
	elseif(isset($state_current))
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
		
		if( $secs_total > DIRECTORY_SEEK_TIME )
		{
			// reset previous state when time runs out
			$state = array();
		
			// save some state information
			array_push($state, array('index' => $i, 'file' => $file));
			
			return false;
		}
		

		// if $file isn't this directory or its parent, 
		if ($file != '.' && $file != '..')
		{
			
			// don't resolve symbolic links here, the watch paths are already resolved, and we want to always add on to that path
			$new_file = $dir . $file;

			// get files recursively
			if( is_dir($new_file) )
			{
				// add a slash then add it to the filelist
				$new_file .= DIRECTORY_SEPARATOR;
				
				// prevent recursion by making sure it isn't already in the list of directories
				if( in_array($new_file, $dirs) == false && in_array(realpath($new_file), $dirs) == false )
				{
					$dirs[] = $new_file;
					// add the real path incase they are different
					if(realpath($new_file) != $new_file)
						$dirs[] = realpath($new_file);
					
					// always descend and search for more directories
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
		}
	}
	
	
	return true;
	
}

?>