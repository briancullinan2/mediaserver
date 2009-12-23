<?php
// this controlls all of the inputing of data into the database
// to add extra type handling create a function that inserts data into the database based on a filepath
// add calls to that function in the getfile procedure

// some things to take into consideration:
// Access the database in intervals of files, not every individual file
// Sleep so output can be recorded to disk or downloaded in a browser
// Only update files that don't exist in database, have changed timestamps, have changed in size

// this is an iterator to update the server database and all the media listings

header('Content-type: text/html; charset=UTF-8');

// use some extra code so cron can be run from any directory
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

session_write_close();

//------------- DON'T CHANGE THIS - USE /include/settings.php TO MODIFY THESE VALUES ---------//
// add 30 seconds becase the cleanup shouldn't take any longer then that
set_time_limit(DIRECTORY_SEEK_TIME + FILE_SEEK_TIME + CLEAN_UP_BUFFER_TIME);

// ignore user abort because the script will handle it
ignore_user_abort(1);

// start output buffer so we can save in tmp file
$log_fp = @fopen(TMP_DIR . 'mediaserver.log', 'wb');
ob_start(create_function('$buffer', 'global $log_fp; @fwrite($log_fp, $buffer); return $buffer;'));

// lock the file so the next script can detect it
$lock_result = flock($log_fp, LOCK_EX+LOCK_NB, $would_lock);
if($lock_result === false)
{
	if(!isset($_REQUEST['ignore']) || $_REQUEST['ignore'] != true)
	{
		fclose($log_fp);
		log_error('Error: Log file locked, this usually means the script is already running, override with ?ignore=true in the request');
		exit;
	}
	else
	{
		log_error('Error: Log file locked, continuing');
	}
}

$tm_start = array_sum(explode(' ', microtime()));

log_error('Cron Script: ' . VERSION . '_' . VERSION_NAME);

// start the page with a pre to output messages that can be viewed in a browser
?><script language="javascript">var timer=null;var last_height=0;var same_count=0;function body_scroll() {timer=setTimeout('body_scroll()', 100);if(document.body.scrollHeight!=last_height) {same_count=0;last_height=document.body.scrollHeight;document.body.scrollTop = document.body.scrollHeight;} else {same_count++;}if(same_count == 1000) {clearTimeout(timer);}}timer=setTimeout('body_scroll()', 100)</script><code style="white-space:nowrap;">
<?php

// the cron script is useless if it has nowhere to store the information it reads
if(USE_DATABASE == false || count($GLOBALS['watched']) == 0)
{
	@fclose($log_fp);
	exit;
}

// get the watched directories
log_error('Ignored: ' . serialize($GLOBALS['ignored']));
log_error('Watched: ' . serialize($GLOBALS['watched']));

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

$clean_count = 0;
$should_clean = false;
// get clean count
if(count($state) > 0)
{
	$first = array_pop($state);
	if(isset($first['clean_count']) && is_numeric($first['clean_count']))
	{
		$clean_count = ++$first['clean_count'];
	}
	else
	{
		array_push($state, $first);
	}
}

log_error('State: ' . serialize($state));

if($clean_count > CLEAN_UP_THREASHOLD)
{
	log_error("Clean Count: " . $clean_count . ', clean up will happen this time!');
	
	$should_clean = true;
	$clean_count = 0;
}
else
{
	log_error("Clean Count: " . $clean_count);
}
	
// check state information
if( isset($state) && is_array($state) ) $state_current = array_pop($state);

$i = 0;
// get starting index
if( isset($state_current) && isset($GLOBALS['watched'][$state_current['index']]) && $GLOBALS['watched'][$state_current['index']]['Filepath'] == $state_current['file'] )
{
	$i = $state_current['index'];
}
elseif(isset($state_current))
{
	// if it isn't set in the watched list at all 
	//   something must be wrong with our state so reset it
	$fp = @fopen(LOCAL_ROOT . "state_dirs.txt", "w");
	if($fp === false) // try tmp dir
		$fp = @fopen(TMP_DIR . "state_dirs.txt", "w");
	if($fp !== false)
	{
		log_error("State mismatch: State cleared");
		$state = array();
		array_push($state, array('clean_count' => $clean_count));
		fwrite($fp, serialize($state));
		fclose($fp);
		// remove clean because it will mess up the script further down
		array_pop($state);
	}
	$state = array();
}

// allow skipping of scanning and go straight to file processing
if(!isset($_REQUEST['skip_scan']))
{
	
	if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($GLOBALS['watched']) && $_REQUEST['entry'] >= 0)
		$i = $_REQUEST['entry'];
	
	log_error("Phase 1: Checking for modified Directories; Recursively");
	
	// loop through each watched folder and get a list of all the files
	for($i; $i < count($GLOBALS['watched']); $i++)
	{
		if(!file_exists(str_replace('/', DIRECTORY_SEPARATOR, $GLOBALS['watched'][$i]['Filepath'])))
		{
			log_error("Error: Directory does not exist! " . $GLOBALS['watched'][$i]['Filepath'] . " is missing!");
			$should_clean = 0;
			continue;
		}
		$watch = '^' . $GLOBALS['watched'][$i]['Filepath'];
	
		$status = db_watch::handle($watch);
	
		// if exited because of time, then save state
		if( $status === false )
		{
			// record the current directory
			array_push($state, array('index' => $i, 'file' => substr($watch, 1)));
			array_push($state, array('clean_count' => $clean_count));
			
			// serialize and save
			log_error("Ran out of Time: State saved");
			$fp = @fopen(LOCAL_ROOT . "state_dirs.txt", "w");
			if($fp === false) // try tmp dir
				$fp = @fopen(TMP_DIR . "state_dirs.txt", "w");
			if($fp !== false)
			{
				fwrite($fp, serialize($state));
				fclose($fp);
			}
			// remove clean because it will mess up the script further down
			array_pop($state);
			
			// since it exited because of time we don't want to continue our for loop
			//   exit out of the loop so it start off in the same place next time
			break;
		}
		else
		{
			// clear state information
			if(file_exists(LOCAL_ROOT . "state_dirs.txt"))
			{
				$fp = @fopen(LOCAL_ROOT . "state_dirs.txt", "w");
				if($fp === false) // try tmp dir
					$fp = @fopen(TMP_DIR . "state_dirs.txt", "w");
				if($fp !== false)
				{
					log_error("Completed successfully: State cleared");
					$state = array();
					array_push($state, array('clean_count' => $clean_count));
					fwrite($fp, serialize($state));
					fclose($fp);
					// remove clean because it will mess up the script further down
					array_pop($state);
				}
			}
			
			// set the last updated time in the watched table
			$GLOBALS['database']->query(array('UPDATE' => db_watch::DATABASE, 'VALUES' => array('Lastwatch' => date("Y-m-d h:i:s")), 'WHERE' => 'Filepath = "' . $watch . '"'), false);
		}
		
		if(isset($_REQUEST['entry']) && is_numeric($_REQUEST['entry']) && $_REQUEST['entry'] < count($GLOBALS['watched']) && $_REQUEST['entry'] >= 0)
			break;
	}
	log_error("Phase 1: Complete!");
	
	if(connection_status()!=0)
	{
		@fclose($log_fp);
		exit;
	}
}
else
{
	log_error("Phase 1: Skipped because of skip_scan argument.");
}

// clean up the watch_list and remove stuff that doesn't exist in watch anymore
if($should_clean !== 0)
	db_watch_list::cleanup();

// now scan some files
$tm_start = array_sum(explode(' ', microtime()));

log_error("Phase 2: Checking modified directories for modified files");

do
{
	// get 1 folder from the database to search the files for
	$db_dirs = db_watch_list::get(array('limit' => 1, 'order_by' => 'id', 'direction' => 'ASC'), $count, $error);
	
	if(count($db_dirs) > 0)
	{
		$dir = $db_dirs[0]['Filepath'];
		if(USE_ALIAS == true)
			$dir = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $dir);
		$status = db_watch_list::handle($dir);
		
		// do not call self::remove because we want to leave the folders inside of the current one so they will be scanned also
		// delete the selected folder from the database
		$GLOBALS['database']->query(array('DELETE' => db_watch_list::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'), false);
	}

	// don't put too much load on the system
	usleep(1);

	// check if execution time is too long
	$secs_total = array_sum(explode(' ', microtime())) - $tm_start;
	
	if($secs_total > FILE_SEEK_TIME)
		log_error("Ran out of Time: Changed directories still in database");
	
// if the connection is lost complete current directory then quit
} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 && connection_status()==0 );

log_error("Phase 2: Complete!");

if(connection_status()!=0)
{
	@fclose($log_fp);
	exit;
}

// now do some cleanup
//  but only if we need it!
if($should_clean === false)
{
	log_error("Phase 3: Skipping cleaning, count is " . $clean_count);
	@fclose($log_fp);
	exit;
}
elseif($should_clean === 0)
{
	log_error("Phase 3: Skipping cleaning because of error!");
	@fclose($log_fp);
	exit;
}
//exit;

log_error("Phase 3: Cleaning up");

foreach($GLOBALS['modules'] as $i => $module)
{
	call_user_func_array($module . '::cleanup', array());
}

// read all the folders that lead up to the watched folder
// these might be delete by cleanup, so check again because there are only a couple
$directories = array();
for($i = 0; $i < count($GLOBALS['watched']); $i++)
{
	$folders = split('/', $GLOBALS['watched'][$i]['Filepath']);
	$curr_dir = (realpath('/') == '/')?'/':'';
	
	// don't add the watch directory here because it must be added to the watch list first!
	$length = count($folders);
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
				
				// don't add twice
				if(!in_array($curr_dir, $directories))
				{
					$directories[] = $curr_dir;
					// if the USE_ALIAS is true this will only add the folder
					//    if it is in the list of aliases
					db_watch_list::handle_file($curr_dir);
				}
			}
			// but make an exception for folders between an alias and the watch path
			elseif(USE_ALIAS && $between && !in_array($curr_dir, $directories))
			{
				$directories[] = $curr_dir;
				
				db_watch_list::handle_file($curr_dir);
			}
		}
	}
}

log_error("Phase 3: Complete!");

@fclose($log_fp);

?>
</code>
