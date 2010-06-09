<?php
/**
 * this controlls all of the inputing of data into the database
 * to add extra type handling create a function that inserts data into the database based on a filepath
 * add calls to that function in the getfile procedure

 * some things to take into consideration:
 * Access the database in intervals of files, not every individual file
 * Sleep so output can be recorded to disk or downloaded in a browser
 * Only update files that don't exist in database, have changed timestamps, have changed in size

 * this is an iterator to update the server database and all the media listings
 */
 
/**
 * Implementation of register
 * @ingroup register
 */
function register_cron()
{
	return array(
		'name' => lang('cron name', 'Cron Updater'),
		'description' => lang('cron description', 'Update the database to match the file system.'),
		'privilage' => 1,
		'path' => __FILE__,
		'template' => false,
		'settings' => array('dir_seek_time', 'file_seek_time', 'cleanup_buffer_time', 'cleanup_threashold'),
		'depends on' => array('database', 'cron_last_run'),
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_cron()
{
	$status = array();

	if(dependency('database'))
	{
		$status['cron'] = array(
			'name' => lang('cron status title', 'Cron'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('cron status description', 'Cron updating functionality is available.'),
				),
			),
			'value' => array(
				'text' => array(
					'Cron updating available',
				),
			),
		);
	}
	else
	{
		$status['users'] = array(
			'name' => lang('users status title', 'Cron'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('cron status fail description', 'Cron cannot update the database because it is not configured.'),
				),
			),
			'value' => array(
				'text' => array(
					'Cron cannot update',
				),
			),
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_cron($settings, $request)
{
	$settings['dir_seek_time'] = setting_dir_seek_time($settings);
	$settings['file_seek_time'] = setting_file_seek_time($settings);
	$settings['cleanup_buffer_time'] = setting_cleanup_buffer_time($settings);
	$settings['cleanup_threashold'] = setting_cleanup_threashold($settings);
	
	$options = array();
	
	$options['cron'] = array(
		'name' => 'Running the Cron',
		'status' => '',
		'description' => array(
			'list' => array(
				'In order for the cron script to run, it must be installed in the OS to run periodically throughout the day.',
			),
		),
		'value' => array(
			'On Unix and Linux:',
			array(
				'code' => '&nbsp;&nbsp;&nbsp;&nbsp;0 * * * * /usr/bin/php /&lt;site path&gt;/modules/cron.php &gt;/dev/null 2&gt;&amp;1<br />
				&nbsp;&nbsp;&nbsp;&nbsp;30 * * * * /usr/bin/php /&lt;site path&gt;/modules/cron.php &gt;/dev/null 2&gt;&amp;1<br />',
			),
			'On Windows:',
			'Run this command from the command line to install the cron script as a task:',
		),
	);
	
	// dir seek time
	$options['dir_seek_time'] = array(
		'name' => 'Directory Seek Time',
		'status' => '',
		'description' => array(
			'list' => array(
				'This script allows you to specify an amount of time to spend on searching directories.  This is so the script only runs for a few minutes every hour or every half hour.',
				'The directory seek time is the amount of time the script will spend searching directories for changed files.',
			),
		),
		'type' => 'time',
		'value' => $settings['dir_seek_time'],
	);
	
	// file seek time
	$options['file_seek_time'] = array(
		'name' => 'File Seek Time',
		'status' => '',
		'description' => array(
			'list' => array(
				'The file seek time is the amount of time the script will spend reading file information and putting it in to the database.',
			),
		),
		'type' => 'time',
		'value' => $settings['dir_seek_time'],
	);
	
	$options['cleanup_buffer_time'] = array(
		'name' => 'Clean-Up Buffer Time',
		'status' => '',
		'description' => array(
			'list' => array(
				'The clean up buffer time is used to add an extra amount of run time for database cleanup, such as removing non-existent files or duplicate files.',
			),
		),
		'type' => 'time',
		'value' => $settings['cleanup_buffer_time'],
	);
	
	$options['cleanup_theashold'] = array(
		'name' => 'Clean-Up Threashold',
		'status' => '',
		'description' => array(
			'list' => array(
				'How many time should the script script run before cleaning up.',
				'Sometimes cleanups can be time consuming, if the accuracy of what is on the filesystem is not a concern, this value should be high.',
			),
		),
		'type' => 'text',
		'value' => $settings['cleanup_threashold'],
	);
	
	return $options;
}

/**
 * Implementation of dependency
 * Check the late date the cron ran and show status if it is too long ago
 * @ingroup dependency
 */
function dependency_cron_last_run()
{
	return true; // TODO: more here
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 60 by default, accepts a number over zero or numeric [value] * numeric [multiplier]
 */
function setting_dir_seek_time($settings)
{
	if(isset($settings['dir_seek_time']['value']) && isset($settings['dir_seek_time']['multiplier']) && 
		is_numeric($settings['dir_seek_time']['value']) && is_numeric($settings['dir_seek_time']['multiplier'])
	)
		$settings['dir_seek_time'] = $settings['dir_seek_time']['value'] * $settings['dir_seek_time']['multiplier'];
	
	if(isset($settings['dir_seek_time']) && is_numeric($settings['dir_seek_time']) && $settings['dir_seek_time'] > 0)
		return $settings['dir_seek_time'];
	else
		return 60;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 60 by default
 */
function setting_file_seek_time($settings)
{
	if(isset($settings['file_seek_time']['value']) && isset($settings['file_seek_time']['multiplier']) && 
		is_numeric($settings['file_seek_time']['value']) && is_numeric($settings['file_seek_time']['multiplier'])
	)
		$settings['file_seek_time'] = $settings['file_seek_time']['value'] * $settings['file_seek_time']['multiplier'];
	
	if(isset($settings['file_seek_time']) && is_numeric($settings['file_seek_time']) && $settings['file_seek_time'] > 0)
		return $settings['file_seek_time'];
	else
		return 60;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 45 by default
 */
function setting_cleanup_buffer_time($settings)
{
	if(isset($settings['cleanup_buffer_time']['value']) && isset($settings['cleanup_buffer_time']['multiplier']) && 
		is_numeric($settings['cleanup_buffer_time']['value']) && is_numeric($settings['cleanup_buffer_time']['multiplier'])
	)
		$settings['cleanup_buffer_time'] = $settings['cleanup_buffer_time']['value'] * $settings['cleanup_buffer_time']['multiplier'];
	
	if(isset($settings['cleanup_buffer_time']) && is_numeric($settings['cleanup_buffer_time']) && $settings['cleanup_buffer_time'] > 0)
		return $settings['cleanup_buffer_time'];
	else
		return 45;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 5 by default
 */
function setting_cleanup_threashold($settings)
{
	if(isset($settings['cleanup_threashold']) && is_numeric($settings['cleanup_threashold']))
		return $settings['cleanup_threashold'];
	else
		return 5;
}

/**
 * Save the inputted state to a file either in the site root or in temp depending on permissions
 * @param state The state array containing the current directory and the clean_count
 */
function save_state($state)
{
	$fp = @fopen(setting('local_root') . "state_dirs.txt", "w");
	if($fp === false) // try tmp dir
	{
		PEAR::raiseError('Error saving state in default state_dirs.txt file!', E_DEBUG);
		$fp = @fopen(setting('tmp_dir') . "state_dirs.txt", "w");
	}
	
	if($fp !== false)
	{
		fwrite($fp, serialize($state));
		fclose($fp);
	}
	else
		PEAR::raiseError('Error saving state!', E_DEBUG);
}

/**
 * Save a blank state to clear it
 */
function clear_state()
{
	save_state(array());
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, valid input is the index of the watched directory to scan
 */
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

/**
 * Imeplementation of validate
 * @ingroup validate
 * @return NULL by default, valid input is the full watched path of the directory to scan
 */
function validate_scan_dir($request)
{
	$request['scan_entry'] = validate_scan_entry($request);
	
	if(isset($request['scan_dir']))
	{
		if(is_watched($request['scan_dir']) && 
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return false by default, true to ingore the lock on the log file and scan anyways
 */
function validate_ignore_lock($request)
{
	if(isset($request['ignore_lock']) && ($request['ignore_lock'] == 'true' || $request['ignore_lock'] == true))
		return true;
	else
		return false;
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return setting(cleanup_threashold) by default, any number between zero and setting(cleanup_threashold) is valid, this will be incremented and saved in the state
 */
function validate_clean_count($request)
{
	if(isset($request['clean_count']) && is_numeric($request['clean_count']) && $request['clean_count'] > 0 && $request['clean_count'] < setting('cleanup_threashold'))
		return $request['clean_count'];
	else
		return setting('cleanup_threashold');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return false by default, when set to true the file scanning will be skipped, only the directory scanning will be done
 */
function validate_scan_skip($request)
{
	if(isset($request['scan_skip']) && ($request['scan_skip'] == 'true' || $request['scan_skip'] == true))
		return true;
	else
		return false;
}

/**
 * Recursively scans directories for changed files
 * @param request the request containing extra options for the scan performance
 */
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
			$current_dir = add_admin_watch('^' . $request['scan_dir']);
		
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
				$GLOBALS['database']->query(array('UPDATE' => 'admin_watch', 'VALUES' => array('Lastwatch' => date("Y-m-d h:i:s")), 'WHERE' => 'id = ' . $GLOBALS['watched'][$request['scan_entry']]['id']), false);
				
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

/**
 * Pull out directories from the watch_list and scan for new and removed files
 */
function read_files()
{
	global $should_clean;
	
	PEAR::raiseError("Phase 2: Checking modified directories for modified files", E_DEBUG);
	
	do
	{
		// get 1 folder from the database to search the files for
		$db_dirs = get_updates(array('limit' => 1, 'order_by' => 'id', 'direction' => 'ASC'), $count);
		
		if(count($db_dirs) > 0)
		{
			$dir = $db_dirs[0]['Filepath'];
			if(setting('admin_alias_enable') == true)
				$dir = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $dir);
			$status = scan_dir($dir);
			
			// do not call self::remove because we want to leave the folders inside of the current one so they will be scanned also
			// delete the selected folder from the database
			$GLOBALS['database']->query(array('DELETE' => 'updates', 'WHERE' => 'Filepath = "' . addslashes($dir) . '"'), false);
		}
	
		// don't put too much load on the system
		usleep(1);
	
		// check if execution time is too long
		$secs_total = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];
		
		if($secs_total > setting('file_seek_time'))
			PEAR::raiseError("Ran out of Time: Changed directories still in database", E_DEBUG);
		
	// if the connection is lost complete current directory then quit
	} while( $secs_total < setting('file_seek_time') && count($db_dirs) > 0 && connection_status()==0 );
	
	PEAR::raiseError("Phase 2: Complete!", E_DEBUG);

	if(connection_status()!=0)
	{
		@fclose($log_fp);
		exit;
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_cron($request)
{
	global $log_fp, $should_clean;
	
	// set new error callback to cron one so errors are printed out imediately
	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'cron_error_callback');
	
	header('Content-type: text/html; charset=UTF-8');
	
	session_write_close();
	
	//------------- DON'T CHANGE THIS - USE /include/settings.php TO MODIFY THESE VALUES ---------//
	// add 30 seconds becase the cleanup shouldn't take any longer then that
	set_time_limit(setting('dir_seek_time') + setting('file_seek_time') + setting('cleanup_buffer_time'));
	
	// ignore user abort because the script will handle it
	ignore_user_abort(1);
	
	// start output buffer so we can save in tmp file
	$log_fp = @fopen(setting('tmp_dir') . 'mediaserver.log', 'wb');
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
	if(setting('database_enable') == false || count($GLOBALS['watched']) == 0)
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
	if( file_exists(setting('local_root') . 'state_dirs.txt') )
		$state = unserialize(implode("", @file(setting('local_root') . "state_dirs.txt")));
	elseif( file_exists(setting('tmp_dir') . 'state_dirs.txt') )
		$state = unserialize(implode("", @file(setting('tmp_dir') . "state_dirs.txt")));
	
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
	if($request['clean_count'] >= setting('cleanup_threashold'))
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
		cleanup('updates');
	
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
	
	// clean up each database
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		// only clean up the modules that have databases of their own
		if(is_wrapper($handler) == false)
			cleanup($handler);
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
				if(!setting('admin_alias_enable') || in_array($curr_dir, $GLOBALS['paths']) !== false)
				{
					// this allows for us to make sure that at least the beginning 
					//   of the path is an aliased path
					$between = true;
					
					// don't add twice
					if(!in_array($curr_dir, $directories))
					{
						$directories[] = $curr_dir;
						// if the setting('admin_alias_enable') is true this will only add the folder
						//    if it is in the list of aliases
						handle_file($curr_dir);
					}
				}
				// but make an exception for folders between an alias and the watch path
				elseif(setting('admin_alias_enable') && $between && !in_array($curr_dir, $directories))
				{
					$directories[] = $curr_dir;
					
					handle_file($curr_dir);
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
	// do not print notes
	print $error->message . '<br />';
	flush();
	ob_flush();
}

// run the script if it is being executed from the command line
if(php_sapi_name() == 'cli')
{
	$_REQUEST['module'] = 'cron';
	include_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'index.php';
}
