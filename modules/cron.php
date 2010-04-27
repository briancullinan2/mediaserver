<?php
// this controlls all of the inputing of data into the database
// to add extra type handling create a function that inserts data into the database based on a filepath
// add calls to that function in the getfile procedure

// some things to take into consideration:
// Access the database in intervals of files, not every individual file
// Sleep so output can be recorded to disk or downloaded in a browser
// Only update files that don't exist in database, have changed timestamps, have changed in size

// this is an iterator to update the server database and all the media listings

function register_cron()
{
	return array(
		'name' => 'Cron Updater',
		'description' => 'Update the database to match the file system.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true
	);
}

function save_state($state)
{
	$fp = @fopen(LOCAL_ROOT . "state_dirs.txt", "w");
	if($fp === false) // try tmp dir
		$fp = @fopen(TMP_DIR . "state_dirs.txt", "w");
	if($fp !== false)
	{
		fwrite($fp, serialize($state));
		fclose($fp);
	}
}

function clear_state()
{
	save_state(array());
}

function validate_scan_entry($request)
{
	if(isset($request['scan_entry']) && is_numeric($request['scan_entry']) && $request['scan_entry'] > 0 && $request['scan_entry'] < count($GLOBALS['watched']))
		return $request['scan_entry'];
	elseif(isset($request['scan_dir']))
	{
		foreach($GLOBALS['watched'] as $i => $watch)
		{
			if(substr($request['scan_dir'], 0, strlen($watch['Filepath'])) == $watch['Filepath'])
				return $i;
		}
	}
}

function validate_scan_dir($request)
{
	$request['scan_entry'] = validate_scan_entry($request);
	
	if(isset($request['scan_dir']))
	{
		if(db_watch_list::is_watched($request['scan_dir']) && 
			(
			 	!isset($request['scan_entry']) ||
				substr($request['scan_dir'], 0, strlen($GLOBALS['watched'][$request['scan_entry']]['Filepath'])) == $GLOBALS['watched'][$request['scan_entry']]['Filepath']
			)
		)
			return $request['scan_dir'];
		else
			unset($request['scan_dir']);
	}
	
	if(!isset($request['scan_dir']))
		return isset($request['scan_entry'])?$GLOBALS['watched'][$request['scan_entry']]['Filepath']: $GLOBALS['watched'][0]['Filepath'];
}

function validate_ignore_lock($request)
{
	if(isset($request['ignore_lock']) && ($request['ignore_lock'] == 'true' || $request['ignore_lock'] == true))
		return true;
	else
		return false;
}

function validate_clean_count($request)
{
	if(isset($request['clean_count']) && is_numeric($request['clean_count']) && $request['clean_count'] > 0 && $request['clean_count'] < CLEAN_UP_THREASHOLD)
		return $request['clean_count'];
	else
		return CLEAN_UP_THREASHOLD;
}

function validate_scan_skip($request)
{
	if(isset($request['scan_skip']) && ($request['scan_skip'] == 'true' || $request['scan_skip'] == true))
		return true;
	else
		return false;
}

function read_changed($request)
{
	global $should_clean;
	
	$request['scan_skip'] = validate_scan_skip($request);
	
	// allow skipping of scanning and go straight to file processing
	if($request['scan_skip'] == false)
	{
		PEAR::raiseError("Phase 1: Checking for modified Directories; Recursively", E_DEBUG);
		
		$request['scan_entry'] = validate_scan_entry($request);
		$request['scan_dir'] = validate_scan_dir($request);
		$request['clean_count'] = validate_clean_count($request);
		
		$has_resumed = false;
		// loop through each watched folder and get a list of all the files
		foreach($GLOBALS['watched'] as $i => $watch)
		{
			if($has_resumed == false && substr($request['scan_dir'], 0, strlen($watch['Filepath'])) != $watch['Filepath'])
				continue;
			$has_resumed = true;
			
			// check to make sure it exists so that it isn't accidentaly removed when not mounted
			if(!file_exists(str_replace('/', DIRECTORY_SEPARATOR, $request['scan_dir'])))
			{
				PEAR::raiseError("Error: Directory does not exist! " . $GLOBALS['watched'][$i]['Filepath'] . " is missing!", E_DEBUG);
				$should_clean = 0;
				continue;
			}
			
			// scan the directory
			$current_dir = db_watch::handle('^' . $request['scan_dir']);
		
			// if exited because of time, then save state
			if( $current_dir !== true )
			{		
				// serialize and save
				PEAR::raiseError("Ran out of Time: State saved", E_DEBUG);
				
				save_state(array('clean_count' => $request['clean_count'], 'dir' => $current_dir));
				
				break;
			}
			else
			{
				// clear state information
				clear_state();
		
				// set the last updated time in the watched table
				$GLOBALS['database']->query(array('UPDATE' => db_watch::DATABASE, 'VALUES' => array('Lastwatch' => date("Y-m-d h:i:s")), 'WHERE' => 'id = ' . $GLOBALS['watched'][$request['scan_entry']]['id']), false);
				
				// break after specified entry is complete
				if(isset($request['scan_entry']))
					break;
			}
		}
		
		PEAR::raiseError("Phase 1: Complete!", E_DEBUG);
	}
	else
	{
		PEAR::raiseError("Phase 1: Skipped because of skip_scan argument.", E_DEBUG);
	}
	
	if(connection_status()!=0)
	{
		@fclose($log_fp);
		exit;
	}
}

function read_files()
{
	global $should_clean;
	
	PEAR::raiseError("Phase 2: Checking modified directories for modified files", E_DEBUG);
	
	do
	{
		// get 1 folder from the database to search the files for
		$db_dirs = db_watch_list::get(array('limit' => 1, 'order_by' => 'id', 'direction' => 'ASC'), $count);
		
		if(count($db_dirs) > 0)
		{
			$dir = $db_dirs[0]['Filepath'];
			if(USE_ALIAS == true)
				$dir = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $dir);
			$status = db_watch_list::scan_dir($dir);
			
			// do not call self::remove because we want to leave the folders inside of the current one so they will be scanned also
			// delete the selected folder from the database
			$GLOBALS['database']->query(array('DELETE' => db_watch_list::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'), false);
		}
	
		// don't put too much load on the system
		usleep(1);
	
		// check if execution time is too long
		$secs_total = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];
		
		if($secs_total > FILE_SEEK_TIME)
			PEAR::raiseError("Ran out of Time: Changed directories still in database", E_DEBUG);
		
	// if the connection is lost complete current directory then quit
	} while( $secs_total < FILE_SEEK_TIME && count($db_dirs) > 0 && connection_status()==0 );
	
	PEAR::raiseError("Phase 2: Complete!", E_DEBUG);

	if(connection_status()!=0)
	{
		@fclose($log_fp);
		exit;
	}
}

function output_cron($request)
{
	global $log_fp, $should_clean;
	
	// set new error callback to cron one so errors are printed out imediately
	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'cron_error_callback');
	
	header('Content-type: text/html; charset=UTF-8');
	
	session_write_close();
	
	//------------- DON'T CHANGE THIS - USE /include/settings.php TO MODIFY THESE VALUES ---------//
	// add 30 seconds becase the cleanup shouldn't take any longer then that
	set_time_limit(DIRECTORY_SEEK_TIME + FILE_SEEK_TIME + CLEAN_UP_BUFFER_TIME);
	
	// ignore user abort because the script will handle it
	ignore_user_abort(1);
	
	// start output buffer so we can save in tmp file
	$log_fp = @fopen(TMP_DIR . 'mediaserver.log', 'wb');
	ob_start(create_function('$buffer', 'global $log_fp; @fwrite($log_fp, $buffer); return $buffer;'));
	
	// start the page with a pre to output messages that can be viewed in a browser
	?><script language="javascript">var timer=null;var last_height=0;var same_count=0;function body_scroll() {timer=setTimeout('body_scroll()', 100);if(document.body.scrollHeight!=last_height) {same_count=0;last_height=document.body.scrollHeight;document.body.scrollTop = document.body.scrollHeight;} else {same_count++;}if(same_count == 1000) {clearTimeout(timer);}}timer=setTimeout('body_scroll()', 100)</script><code style="white-space:nowrap;">
	<?php

	// lock the file so the next script can detect it
	$lock_result = flock($log_fp, LOCK_EX+LOCK_NB, $would_lock);
	$request['ignore_lock'] = validate_ignore_lock($request);
	if($lock_result === false)
	{
		if($request['ignore_lock'] != true)
		{
			fclose($log_fp);
			PEAR::raiseError('Error: Log file locked, this usually means the script is already running, override with ?ignore_lock=true in the request', E_DEBUG);
			exit;
		}
		else
		{
			PEAR::raiseError('Error: Log file locked, continuing', E_DEBUG);
		}
	}
	
	$GLOBALS['tm_start'] = array_sum(explode(' ', microtime()));
	
	PEAR::raiseError('Cron Script: ' . VERSION . '_' . VERSION_NAME, E_DEBUG);
	
	// the cron script is useless if it has nowhere to store the information it reads
	if(USE_DATABASE == false || count($GLOBALS['watched']) == 0)
	{
		@fclose($log_fp);
		exit;
	}
	
	// get the watched directories
	PEAR::raiseError('Ignored: ' . serialize($GLOBALS['ignored']), E_DEBUG);
	PEAR::raiseError('Watched: ' . serialize($GLOBALS['watched']), E_DEBUG);
	
	// directories that have already been scanned used to prevent recursion
	$GLOBALS['scan_dirs'] = array();
	
	// the directories for the current state so we can start here the next time the script runs
	$state = array();
	
	// get previous state if it exists
	if( file_exists(LOCAL_ROOT . 'state_dirs.txt') )
		$state = unserialize(implode("", @file(LOCAL_ROOT . "state_dirs.txt")));
	elseif( file_exists(TMP_DIR . 'state_dirs.txt') )
		$state = unserialize(implode("", @file(TMP_DIR . "state_dirs.txt")));
	
	// something is wrong
	if(!is_array($state))
	{
		$state = array();
		clear_state();
	}
	
	PEAR::raiseError('State: ' . serialize($state), E_DEBUG);
	
	// set the state as part of the request
	if(!isset($request['scan_dir']) && isset($state['dir']))
		$request['scan_dir'] = $state['dir'];
		
	if(!isset($request['clean_count']) && isset($state['clean_count']))
		$request['clean_count'] = $state['clean_count'];
		
	// validate the state
	$request['scan_dir'] = validate_scan_dir($request);
	$request['clean_count'] = validate_clean_count($request);
	
	// there are a few conditions that change whether or not the database should be cleaned
	$should_clean = false;
	if($request['clean_count'] >= CLEAN_UP_THREASHOLD)
	{
		PEAR::raiseError("Clean Count: " . $request['clean_count'] . ', clean up will happen this time!', E_DEBUG);
		
		$should_clean = true;
	}
	else
	{
		PEAR::raiseError("Clean Count: " . $request['clean_count'], E_DEBUG);
	}
	
	read_changed($request);
	
	// clean up the watch_list and remove stuff that doesn't exist in watch anymore
	if($should_clean !== 0)
		db_watch_list::cleanup();
	
	// now scan some files
	$GLOBALS['tm_start'] = array_sum(explode(' ', microtime()));
	
	read_files();
	
	// now do some cleanup
	//  but only if we need it!
	if($should_clean === false)
	{
		PEAR::raiseError("Phase 3: Skipping cleaning, count is " . $request['clean_count'], E_DEBUG);
		@fclose($log_fp);
		exit;
	}
	elseif($should_clean === 0)
	{
		PEAR::raiseError("Phase 3: Skipping cleaning because of error!", E_DEBUG);
		@fclose($log_fp);
		exit;
	}
	//exit;
	
	PEAR::raiseError("Phase 3: Cleaning up", E_DEBUG);
	
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
	
	PEAR::raiseError("Phase 3: Complete!", E_DEBUG);
	
	@fclose($log_fp);
	
	?></code><?php
	
}

function cron_error_callback($error)
{
	if(substr($error->message, 0, 9) == 'DATABASE:')
		return;
	print $error->message . '<br />';
	flush();
	ob_flush();
}
