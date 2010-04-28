<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_statistics()
{
	$tools = array(
		'name' => 'Statistics',
		'description' => 'View important site information and status.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Log Parser',
				'description' => 'Parse log files and view useful information about recent cron.php script executions.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Site Information',
				'description' => 'General information about the site, and statistics on loaded files, such as, disk usage, file counts, etc.',
				'privilage' => 10,
				'path' => __FILE__
			)
		)
	);

	return $tools;
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return an array containing temp mediaserver.log or a list of log files that exist
 */
function validate_tools_logs($request)
{
	if(!isset($request['logs']))
		return array(setting('tmp_dir') . 'mediaserver.log');
	else
	{
		$logs = preg_split('/[\n\r;]/i', $request['logs']);
		
		// load list of logs even from a directory
		foreach($logs as $i => $file)
		{
			if(!file_exists($file))
			{
				unset($logs[$i]);
				continue;
			}
			
			if(is_dir($file))
			{
				if($file[strlen($file)-1] != '/' && $file[strlen($file)-1] != '\\') $file .= '/';
				if ($dh = @opendir($file))
				{
					while (($subfile = readdir($dh)) !== false)
					{
						if ($subfile != '.' && $subfile != '..' && is_file($file . $subfile))
						{
							$request['tools_logs'][] = $file . $subfile;
						}
					}
					closedir($dh);
				}
			}
		}
		
		return array_values($logs);
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_statistics($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if($request['subtool'] == 0)
	{
		$request['tools_logs'] = validate_tools_logs($request);
		
		// open each file and look for log information
		// start the parsing when "Cron Script" is found
		$last_time = 0;
		$last_version = '0.0.0';
		$in_most_recent = false;
		$avg_add = 0;
		$avg_add_count = 0;
		$avg_look = 0;
		$avg_look_count = 0;
		$line_count = 0;
		foreach($request['tools_logs'] as $i => $file)
		{
			$fh = @fopen($file, 'rb');
			if($fh !== false)
			{
				// reset line count for each file
				$line_count = 0;
				
				// loop through file and get lines
				while(!feof($fh))
				{
					$line = fgets($fh, setting('buffer_size'));
					$line_count++;
				
					// try and regexp parse the line
					if(preg_match('/^(\[[0-9]{2}\/[0-9]{2}\/[0-9]{4}\:[0-9]{2}\:[0-9]{2}\:[0-9]{2} (\+|-)[0-9]{4}\])\s*([^\:]*)\:\s*(.*)\s*(\<br ?\/?\>)[\n\r\s]*$/i', $line, $matches) != 0)
					{
						// get time
						preg_match('/^\[([0-9]{2})\/([0-9]{2})\/([0-9]{4})\:([0-9]{2})\:([0-9]{2})\:([0-9]{2}) ((\+|-)([0-9]{4}))\]$/i', $matches[1], $time_arr);
						$time = mktime($time_arr[4], $time_arr[5], $time_arr[6], $time_arr[1], $time_arr[2], $time_arr[3]);
						
						if($matches[3] == 'Cron Script')
						{
							// show the most recent time the script has run
							if($time > $last_time)
								$infos[] = array(
									'label' => 'Last Run Time',
									'text' => date('m/d/Y:H:i:s O', $time)
								);
								
							// do version compare and make suggestion
							$version = split('_', $matches[4]);
							if(version_compare($last_version, $version[0]) == -1)
							{
								$in_most_recent = true;
								$last_version = $version[0];
								$infos[] = array(
									'label' => 'Last Run Time',
									'text' => $last_version . ' (' . $version[1] . ')'
								);
							}
							else
							{
								$in_most_recent = false;
							}
							
							if(version_compare($last_version, VERSION) == -1)
							{
								$infos[] = array(
									'label' => 'Upgrade',
									'text' => array(
										'The last version of the logs are before your current running version.',
										'Possible causes of this include:',
										'You are running a remote script that needs to be upgraded!',
									),
									'type' => 'warning'
								);
							}
							
							// do some other stuff for each run
							if(isset($look_count))
							{
								$avg_look += $look_count;
								$avg_look_count++;
							}
						}
						elseif($matches[3] == 'Ignored')
						{
							if($in_most_recent)
							{
								$new_info = array(
									'label' => 'Ignore List',
									'text' => array(
										'Here is the ignore list for the most recent cron run:',
									),
								);
								$ignored = @unserialize($matches[4]);
								if($ignored === false || !is_array($ignored))
								{
									$new_info['text'][] = array(
										'label' => 'Ignore List',
										'text' => 'Error loading list!',
										'type' => 'error',
									);
								}
								else
								{
									foreach($ignored as $i => $ignore)
									{
										$new_info['text'][] = htmlspecialchars($ignore['Filepath']);
									}
								}
								$infos[] = $new_info;
							}
						}
						elseif($matches[3] == 'Watched')
						{
							if($in_most_recent)
							{
								$new_info = array(
									'label' => 'Watch List',
									'text' => array(
										'Here is the watch list for the most recent cron run:',
									),
								);
								$watched = @unserialize($matches[4]);
								if($watched === false || !is_array($watched))
								{
									$new_info['text'][] = array(
										'label' => 'Watch List',
										'text' => 'Error loading list!',
										'type' => 'error',
									);
								}
								else
								{
									foreach($watched as $i => $watch)
									{
										$new_info['text'][] = htmlspecialchars($watch['Filepath']);
									}
								}
								$infos[] = $new_info;
							}
						}
						elseif($matches[3] == 'State')
						{
							if($in_most_recent)
							{
								$new_info = array(
									'label' => 'State List',
									'text' => array(
										'Here is the most recent state:',
									),
								);
								?><info label="State List"></info><?php
								$state = @unserialize($matches[4]);
								if($state === false || !is_array($state))
								{
									$new_info['text'][] = array(
										'label' => 'State List',
										'text' => 'Error loading state!',
										'type' => 'error',
									);
								}
								else
								{
									foreach($state as $i => $value)
									{
										$new_info['text'][] = htmlspecialchars(@$value['file']);
									}
								}
								$infos[] = $new_info;
							}
						}
						elseif($matches[3] == 'Clean Count')
						{
							if($in_most_recent)
							{
								$infos[] = array(
									'label' => 'Cleanup Count',
									'text' => 'The most recent cleanup count is ' . $matches[4],
								);
							}
						}
						elseif(substr($matches[3], 0, 3) == 'Add')
						{
							if(!isset($tmp_time)) $tmp_time = $time;
							if(!isset($file_count)) $file_count = 0;
							if($tmp_time == $time) $file_count++;
							elseif($file_count > 0)
							{
								$tmp_time = $time;
								$avg_add += $file_count;
								$avg_add_count++;
								$file_count = 1;
							}
						}
						elseif(substr($matches[3], 0, 4) == 'Look')
						{
							if(!isset($look_count)) $look_count = 0;
							$look_count++;
						}
					}
					else
					{
						if(strpos($line, 'Fatal') !== false)
						{
							$infos[] = array(
								'label' => 'Fatal Error',
								'text' => array(
									'A fatal error was detected in the logs! Line ' . $line_count . ' in file ' . $file,
									array(
										'text' => 'Reinstalling the script is recommended.',
										'type' => 'warning'
									),
								),
								'type' => 'error',
							);
						}
						elseif(strpos($line, 'Warning') !== false)
						{
							$infos[] = array(
								'label' => 'Warning Detected',
								'text' => array(
									'A warning was detected in the logs! Line ' . $line_count . ' in file ' . $file,
									array(
										'text' => array(
											'Possible causes include:',
											'Files are inaccessible by the script',
											'Permission problems',
											'Configuration problems',
										),
									),
								),
								'type' => 'warning',
							);
						}
					}
				}
				fclose($fh);
			}
		}
		
		if($avg_add_count > 0)
		{
			$infos[] = array(
				'label' => 'Average Add Speed',
				'text' => round(($avg_add / $avg_add_count), 2) . ' Files per Second',
			);
		}
		
		if(isset($look_count))
		{
			$avg_look += $look_count;
			$avg_look_count++;
		}
		
		if($avg_look_count > 0)
		{
			$infos[] = array(
				'label' => 'Average Looks',
				'text' => round(($avg_look / $avg_look_count), 2) . ' Directories were searched for changes per run',
			);
		}
	
		$infos[] = array(
			'label' => 'Logs',
			'text' => array(
				'<form action="" method="post">',
				'Enter directories or files that contain logs, put each entry on a new line or seperate with a semi-colon.',
				'<textarea>' . implode("\n", $request['tools_logs']) . '</textarea>',
				'<input type="submit" value="Submit" />',
				'</form>',
			),
		);
	}

	if($request['subtool'] == 1)
	{
		// show some information about current setup
		$infos[] = array(
			'label' => 'Current Version',
			'text' => VERSION . ' (' . VERSION_NAME . ')',
		);
		
		// get database counts
		$section_info = array(
			'label' => 'Database Counts',
			'text' => array(
				'Here is a list of counts for the databases:',
			),
			'type' => 'section'
		);
		foreach($GLOBALS['tables'] as $i => $db)
		{
			$result = $GLOBALS['database']->query(array('SELECT' => $db, 'COLUMNS' => 'count(*)'), false);
			if(count($result) > 0)
			{
				$section_info['text'][] = array(
					'label' => $db,
					'text' => 'has ' . $result[0]['count(*)'] . ' entries',
				);
				if($db == db_watch_list::DATABASE)
				{
					$new_info = array(
						'label' => 'Watch List',
						'text' => array(
							'The watch list contains directories that have to be searched for files',
							'There are ' . $result[0]['count(*)'] . ' directories in the watch list database.',
						),
					);
					if($result[0]['count(*)'] == 0)
					{
						$new_info['text'][] = array(
							'text' => 'All files are added and up to date!',
							'type' => 'note'
						);
					}
					$section_info['text'][] = $new_info;
				}
			}
		}
		$infos[] = $section_info;
	}

	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

