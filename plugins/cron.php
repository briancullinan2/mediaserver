<?php
// this controlls all of the inputing of data into the database
// to add extra type handling create a function that inserts data into the database based on a filepath
// add calls to that function in the getfile procedure

// how long to search for directories that have changed
define('DIRECTORY_SEEK_TIME',		30);

// how long to search for changed files or add new files
define('FILE_SEEK_TIME', 		   60);

// how long to clean up files
define('CLEAN_UP_BUFFER_TIME',				45);

// add 30 seconds becase the cleanup shouldn't take any longer then that
set_time_limit( DIRECTORY_SEEK_TIME + FILE_SEEK_TIME + CLEAN_UP_BUFFER_TIME);

$tm_start = array_sum(explode(' ', microtime()));

// this is an iterator to update the server database and all the media listings
// use some extra code so cron can be run from any directory
require dirname(__FILE__) . '/../include/common.php';

// some things to take into consideration:
// Access the database in intervals of files, not every individual file
// Sleep so output can be recorded to disk or downloaded in a browser
// Only update files that don't exist in database, have changed timestamps, have changed in size

// start the page with a pre to output messages that can be viewed in a browser
?><pre><?

// turn on output buffer
ob_start();

// get the directories to watch from the watch database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get the watched directories
$watched = $mysql->getWatched();

// directories that have already been scanned used to prevent recursion
$dirs = array();

// the directories for the current state so we can start here the next time the script runs
$state = array();

// get previous state if it exists
if( file_exists(SITE_LOCALROOT . 'state_dirs.txt') )
	$state = unserialize(implode("", @file(SITE_LOCALROOT . "state_dirs.txt")));

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
	@unlink(SITE_LOCALROOT . "state_dirs.txt");
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
		$fp = fopen(SITE_LOCALROOT . "state_dirs.txt", "w");
		fwrite($fp, serialize($state));
		fclose($fp);
		
		// since it exited because of time we don't want to continue our for loop
		//   exit out of the loop so it start off in the same place next time
		break;
	}
	else
	{
		// clear state information
		if(file_exists(SITE_LOCALROOT . "state_dirs.txt"))
		{
			@unlink(SITE_LOCALROOT . "state_dirs.txt");
			if(file_exists(SITE_LOCALROOT . "state_dirs.txt")) $fp = fopen(SITE_LOCALROOT . "state_dirs.txt", "w");
			if(isset($fp))
			{
				print "State cleared\n";
				fwrite($fp, '');
				fclose($fp);
			}
		}
		
		// set the last updated time in the watched table
		$mysql->set('watch', array('Lastwatch' => date("Y-m-d h:i:s")), array('Filepath' => $watch));
	}
}


// clean up the watch_list and remove stuff that doesn't exist in watch anymore
$where_str = '';
foreach($watched as $i => $watch)
{
	$where_str .= ' Filepath REGEXP "^' . $watch['Filepath'] . '" OR';
}
// remove last OR
$where_str = substr($where_str, 0, strlen($where_str)-2);
$where_str = ' !(' . $where_str . ')';

// remove items
$mysql->set('watch_list', NULL, $where_str);

print 'Cleaned watch_list.' . "\n";
usleep(1);
ob_flush();


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
		
		print 'Searching directory: ' . $dir . "\n";
		
		// search all the files in the directory
		
		// get directory contents
		$files = db_file::get(NULL, array('DIR' => $dir));
		
		foreach($files as $i => $file)
		{
		
			// if $file isn't this directory or its parent, 
			if ($file != '.' && $file != '..' && !is_dir($dir . $file))
			{
				getfile($dir . $file);
			}

		}
	
		// delete the selected folder from the database
		$mysql->set('watch_list', NULL, array('Filepath' => $dir));
	}

	// check if execution time is too long
	$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
	
	usleep(1);
	ob_flush();
	
} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 );


// now do some cleanup

foreach($GLOBALS['modules'] as $i => $module)
{
	call_user_func(array($module, 'cleanup'), $mysql, $watched);
}

// read all the folders that lead up to the watched folder
// these will always be deleted by the cleanup, but there are only a couple
/*for($i = 0; $i < count($watched); $i++)
{
	$folders = split('/', $watched[$i]['Filepath']);
	$curr_dir = '/';
	for($j = 0; $j < count($folders); $j++)
	{
		if($folders[$j] != '')
		{
			$curr_dir .= $folders[$j] . '/';
			// don't add directory here because it must be added to the watch list first!
			if($curr_dir != $watched[$i]['Filepath']) getfile($curr_dir);
		}
	}
}*/

// close output buffer
ob_end_flush();


// check if file is already in database
function getfile( $file )
{
	global $mysql, $modules;
	
	// pass the file to each module to test if it should handle it
	foreach($modules as $i => $name)
	{
		call_user_func(array($name, 'handle'), $mysql, $file);
	}

}


// a function for iterating through each directory and returning the files
function getdir( $dir )
{
	global $dirs, $tm_start, $state, $mysql;
					
	// check directory passed in, only add directory to watch list if it has changed
	$db_file = $mysql->get('files', 
		array(
			'SELECT' => array('id', 'Filedate'),
			'WHERE' => 'Filepath = "' . $dir . '"'
		)
	);
	
	if( count($db_file) == 0 || date("Y-m-d h:i:s", filemtime($dir)) != $db_file[0]['Filedate'] )
	{

		// add directory then continue into directory
		getfile( $dir );
		
		// add to watch list
		$mysql->set('watch_list', array('Filepath' => $dir), NULL);
		
		print 'Queueing directory: ' . $dir . "\n";
		
		usleep(1);
		
		ob_flush();

	}
	
	
	// get directory contents
	$files = db_file::get(NULL, array('DIR' => $dir));
	
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
				$new_file .= '/';
				
				// prevent recursion by making sure it isn't already in the list of directories
				if( in_array($new_file, $dirs) == false )
				{
					$dirs[] = $new_file;
					
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