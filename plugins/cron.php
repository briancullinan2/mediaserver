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
set_time_limit(DIRECTORY_SEEK_TIME + FILE_SEEK_TIME + CLEAN_UP_BUFFER_TIME);

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
$database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get the watched directories
$watched_list = db_watch::get($database, array(), $count, $error);

print_r($watched_list);

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
elseif( file_exists(TMP_DIR . 'state_dirs.txt') )
	$state = unserialize(implode("", @file(TMP_DIR . "state_dirs.txt")));
if(!is_array($state))
	$state = array();

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
	$fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w");
	if($fp === false) // try tmp dir
		$fp = fopen(TMP_DIR . "state_dirs.txt", "w");
	if($fp !== false)
	{
		print "State mismatch: State cleared\n";
		fwrite($fp, '');
		fclose($fp);
	}
	$state = array();
}

if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($watched) && $_REQUEST['entry'] > 0)
	$i = $_REQUEST['entry'];

print "Phase 1: Checkind for modified Directories; Recursively\n";

// loop through each watched folder and get a list of all the files
for($i; $i < count($watched); $i++)
{
	$watch = '^' . $watched[$i];

	$status = db_watch::handle($database, $watch);

	// if exited because of time, then save state
	if( $status === false )
	{
		// record the current directory
		array_push($state, array('index' => $i, 'file' => substr($watch, 1)));
		
		// serialize and save
		print "Ran out of Time: State saved\n";
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
			$fp = fopen(LOCAL_ROOT . "state_dirs.txt", "w");
			if($fp === false) // try tmp dir
				$fp = fopen(TMP_DIR . "state_dirs.txt", "w");
			if($fp !== false)
			{
				print "Completed successfully: State cleared\n";
				fwrite($fp, '');
				fclose($fp);
			}
		}
		
		// set the last updated time in the watched table
		$database->query(array('UPDATE' => db_watch::DATABASE, 'VALUES' => array('Lastwatch' => date("Y-m-d h:i:s")), 'WHERE' => 'Filepath = "' . $watch . '"'));
	}
	
	if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($watched) && $_REQUEST['entry'] > 0)
		break;
}
print "Phase 1: Complete!\n";

// clean up the watch_list and remove stuff that doesn't exist in watch anymore
db_watch_list::cleanup($database, $watched, $ignored);

// now scan some files
$tm_start = array_sum(explode(' ', microtime()));

print "Phase 2: Checking modified directories for modified files\n";

do
{
	// get 1 folder from the database to search the files for
	$db_dirs = db_watch_list::get($database, array('limit' => 1), $count, $error);
	
	if(count($db_dirs) > 0)
	{
		$dir = $db_dirs[0]['Filepath'];
		
		$status = db_watch_list::handle($database, $dir);
	}

	// check if execution time is too long
	$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
	
	if($secs_total > FILE_SEEK_TIME)
		print "Ran out of Time: Changed directories still in database\n";
	
	flush();
	
} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 );

print "Phase 2: Complete!\n";

// now do some cleanup
//exit;

print "Phase 3: Cleaning up\n";

foreach($GLOBALS['modules'] as $i => $module)
{
	if($module != 'fs_file')
		call_user_func_array($module . '::cleanup', array($database, $watched, $ignored));
}

// read all the folders that lead up to the watched folder
// these might be delete by cleanup, so check again because there are only a couple
for($i = 0; $i < count($watched); $i++)
{
	$folders = split(addslashes(DIRECTORY_SEPARATOR), $watched[$i]);
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
				db_watch_list::handle_file($database, $curr_dir);
			}
			// but make an exception for folders between an alias and the watch path
			elseif(USE_ALIAS && $between)
			{
				db_watch_list::handle_file($database, $curr_dir);
			}
		}
	}
}

print "Phase 3: Complete!\n";

?>
</pre>
