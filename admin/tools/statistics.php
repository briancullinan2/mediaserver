<?php

function register_statistics()
{
	$tools = array();
	$tools[0] = array(
		'name' => 'Log Parser',
		'description' => 'Parse log files and view useful information about recent cron.php script executions.',
		'privilage' => 10,
		'path' => __FILE__
	);
	$tools[1] = array(
		'name' => 'Site Information',
		'description' => 'General information about the site, and statistics on loaded files, such as, disk usage, file counts, etc.',
		'privilage' => 10,
		'path' => __FILE__
	);
	return $tools;
}

function validate_tools_logs($request)
{
		if(!isset($request['logs']))
			return TMP_DIR . 'mediaserver.log';
		else
			return preg_split('/[\n\r;]/i', $request['logs']);
}

function output_statistics($request)
{
	$request['plugin'] = validate_plugin($request);
	
	if($request['plugin'] == 'admin/tools/statistics/0')
	{
		$request['tools_logs'] = validate_tools_logs($request);
		
		// load list of logs even from a directory
		foreach($request['tools_logs'] as $i => $file)
		{
			if(is_dir($file))
			{
				if($file[strlen($file)-1] != '/' && $file[strlen($file)-1] != '\\') $file .= '/';
				if ($dh = @opendir(LOCAL_ROOT . 'templates'))
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
					$line = fgets($fh, BUFFER_SIZE);
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
								?><info label="Last Run Time"><?php echo date('m/d/Y:H:i:s O', $time);?></info><?php
								
							// do version compare and make suggestion
							$version = split('_', $matches[4]);
							if(version_compare($last_version, $version[0]) == -1)
							{
								$in_most_recent = true;
								$last_version = $version[0];
								?><info label="Last Version"><?php echo $last_version . ' (' . $version[1] . ')';?></info><?php
							}
							else
							{
								$in_most_recent = false;
							}
							
							if(version_compare($last_version, VERSION) == -1)
							{
								?><warning label="Upgrade">The last version of the logs are before your current running version.<br />
								Possible causes of this include:<br />
								You are running a remote script that needs to be upgraded!</warning><?php
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
								?><info label="Ignore List">Here is the ignore list for the most recent cron run:</info><?php
								$ignored = @unserialize($matches[4]);
								if($ignored === false || !is_array($ignored))
								{
									?><error label="Ignore List">Error loading list!</error><?php
								}
								else
								{
									foreach($ignored as $i => $ignore)
									{
										?><text><?php echo htmlspecialchars($ignore['Filepath']); ?></text><?php
									}
								}
							}
						}
						elseif($matches[3] == 'Watched')
						{
							if($in_most_recent)
							{
								?><info label="Watch List">Here is the watch list for the most recent cron run:</info><?php
								$watched = @unserialize($matches[4]);
								if($watched === false || !is_array($watched))
								{
									?><error label="Ignore List">Error loading list!</error><?php
								}
								else
								{
									foreach($watched as $i => $watch)
									{
										?><text><?php echo htmlspecialchars($watch['Filepath']); ?></text><?php
									}
								}
							}
						}
						elseif($matches[3] == 'State')
						{
							if($in_most_recent)
							{
								?><info label="State List">Here is the most recent state:</info><?php
								$state = @unserialize($matches[4]);
								if($state === false || !is_array($state))
								{
									?><error label="State List">Error loading state!</error><?php
								}
								else
								{
									foreach($state as $i => $value)
									{
										?><text><?php echo htmlspecialchars(@$value['file']); ?></text><?php
									}
								}
							}
						}
						elseif($matches[3] == 'Clean Count')
						{
							if($in_most_recent)
							{
								?><info label="Cleanup Count">The most recent cleanup count is <?php echo $matches[4]; ?></info><?php
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
							?><error label="Fatal Error">A fatal error was detected in the logs! Line <?php echo $line_count; ?> in file <?php echo $file; ?><warning>Reinstalling the script is recommended</warning></error><?php
						}
						elseif(strpos($line, 'Warning') !== false)
						{
							?><warning label="Warning Detected">A warning was detected in the logs! Line <?php echo $line_count; ?> in file <?php echo $file; ?><info>Possible causes include:<text>Files are inaccessible by the script</text><text>Permission problems</text><text>Configuration problems</text></info></warning><?php
						}
					}
				}
				fclose($fh);
			}
		}
		
		if($avg_add_count > 0)
		{
			?><info label="Average Add Speed"><?php echo round(($avg_add / $avg_add_count), 2); ?> Files per Second</info><?php
		}
		
		if(isset($look_count))
		{
			$avg_look += $look_count;
			$avg_look_count++;
		}
		
		if($avg_look_count > 0)
		{
			?><info label="Average Looks"><?php echo round(($avg_look / $avg_look_count), 2); ?> Directories were searched for changes per run</info><?php
		}
	
		?><info label="Logs"><form action="" method="post">
			<text>Enter directories or files that contain logs, put each entry on a new line or seperate with a semi-colon.</text>
			<textarea><?php echo (isset($request['tools_logs'])?$request['tools_logs']:''); ?></textarea>
			<input type="submit" value="Submit" />
			</form>
			</info>
		<?php
		
		$tools['Log Parser'] = ob_get_contents();
		ob_clean();
	}

	if($request['plugin'] == 'admin/tools/statistics/1')
	{
		// show some information about current setup
		?><info label="Current Version"><?php echo VERSION . ' (' . VERSION_NAME . ')'; ?></info><?php
		
		// get database counts
		?><section label="Database Counts">Here is a list of counts for the databases:<?php
		foreach($GLOBALS['tables'] as $i => $db)
		{
			$result = $GLOBALS['database']->query(array('SELECT' => $db, 'COLUMNS' => 'count(*)'), false);
			if(count($result) > 0)
			{
				?><info label="<?php echo $db; ?>">has <?php echo $result[0]['count(*)']; ?> entries</info><?php
				if($db == db_watch_list::DATABASE)
				{
					?><info label="Watch List">The watch list contains directories that have to be searched for files<text>There are <?php echo $result[0]['count(*)']; ?> directories in the watch list database.</text></info><?php
					if($result[0]['count(*)'] == 0)
					{
						?><note>All files are added and up to date!</note><?php
					}
				}
			}
		}
		?></section><?php
		
		$tools['Site Information'] = ob_get_contents();
		ob_clean();
	}
}

