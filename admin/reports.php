<?php

// check for obvious information first

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// how many directories still to search
// how many files added in the last 24 hours
// load multiple log files
// graph the log files

// make sure user in logged in
if( loggedIn() )
{
}
else
{
	// redirect to login page
	header('Location: login.php?return=' . $_SERVER['REQUEST_URI']);
	
	exit();
}

$report = array();

// parse log files
if(isset($_REQUEST['logs']))
{
	$files = preg_split('/[\n\r;]/i', $_REQUEST['logs']);
	
	foreach($files as $i => $file)
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
						$files[] = $file . $subfile;
					}
				}
			}
		}
	}
	
	$report['0:0:Log Parser'] = 'This section parses log files';
	// open each file and look for log information
	// start the parsing when "Cron Script" is found
	$last_time = 0;
	$last_version = '0.0.0';
	$in_most_recent = false;
	$avg_add = 0;
	$avg_look = 0;
	$line_count = 0;
	foreach($files as $i => $file)
	{
		$fh = @fopen($file, 'rb');
		if($fh !== false)
		{
			// reset line count for each file
			$line_count = 0;
			
			// loop through file and get lines
			while(!feof($fh))
			{
				$line = fgets($fh);
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
							$report['1:1:Last Run Time'] = date('m/d/Y:H:i:s O', $time);
							
						// do version compare and make suggestion
						$version = split('_', $matches[4]);
						if(version_compare($last_version, $version[0]) == -1)
						{
							$in_most_recent = true;
							$last_version = $version[0];
							$report['2:1:Last Version'] = $last_version . ' (' . $version[1] . ')';
						}
						else
						{
							$in_most_recent = false;
						}
						
						if(version_compare($last_version, VERSION) == -1)
						{
							$report['2.1:2:Upgrade'] = 'The last version of the logs are before your current running version.<br />Possible causes of this include:<br />You are running a remove script that needs to be upgraded!';
						}
						
						// do some other stuff for each run
						if(isset($look_count))
						{
							$avg_look = ($avg_look !== 0)?(($avg_look + $look_count) / 2):$look_count;
						}
					}
					elseif($matches[3] == 'Ignored')
					{
						if($in_most_recent)
						{
							$report['3:1:Ignore List'] = 'Here is the ignore list for the most recent cron run:<br />';
							$ignored = @unserialize($matches[4]);
							if($ignored === false || !is_array($ignored))
							{
								$report['3.1:1:Ignore List'] = 'Error loading list!';
							}
							else
							{
								foreach($ignored as $i => $ignore)
								{
									$report['3:1:Ignore List'] .= htmlspecialchars($ignore['Filepath']) . (($i != count($ignored)-1)?'<br />':''); 
								}
							}
						}
					}
					elseif($matches[3] == 'Watched')
					{
						if($in_most_recent)
						{
							$report['4:1:Watch List'] = 'Here is the watch list for the most recent cron run:<br />';
							$watched = @unserialize($matches[4]);
							if($watched === false || !is_array($watched))
							{
								$report['4.1:1:Watch List'] = 'Error loading list!';
							}
							else
							{
								foreach($watched as $i => $watch)
								{
									$report['4:1:Watch List'] .= htmlspecialchars($watch['Filepath']) . (($i != count($watched)-1)?'<br />':''); 
								}
							}
						}
					}
					elseif($matches[3] == 'State')
					{
						if($in_most_recent)
						{
							$report['5:1:State List'] = 'Here is the most recent state:<br />';
							$state = @unserialize($matches[4]);
							if($state === false || !is_array($state))
							{
								$report['5.1:1:State List'] = 'Error loading state!';
							}
							else
							{
								foreach($state as $i => $value)
								{
									$report['5:1:State List'] .= htmlspecialchars(@$value['file']) . (($i != count($state)-1)?'<br />':''); 
								}
							}
						}
					}
					elseif($matches[3] == 'Clean Count')
					{
						if($in_most_recent)
						{
							$report['6:1:Cleanup Count'] = 'The most recent cleanup count is ' . $matches[4];
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
							$avg_add = ($avg_add !== 0)?(($avg_add + $file_count) / 2):$file_count;
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
						$report['-1000:2:Fatal Error'] = 'A fatal error was detected in the logs! Line ' . $line_count . ' in file ' . $file . '<br />Reinstalling the script is recommended';
					}
					elseif(strpos($line, 'Warning') !== false)
					{
						$report['-1000:2:Detected'] = 'A warning was detected in the logs! Line ' . $line_count . ' in file ' . $file . '<br />Possible causes include:<br />Files are inaccessible by the script<br />Permission problems<br />Configuration problems';
					}
				}
			}
			fclose($fh);
		}
	}
	
	$report['7:1:Average Add Speed'] = round($avg_add, 2) . ' Files per Second';
	
	if(isset($look_count))
	{
		$avg_look = ($avg_look !== 0)?(($avg_look + $look_count) / 2):$look_count;
	}
	
	$report['8:1:Average Looks'] = round($avg_look, 2) . ' Directories were searched for changed per run';
}
elseif(file_exists(TMP_DIR . 'mediaserver.log'))
{
	$_REQUEST['logs'] = TMP_DIR . 'mediaserver.log';
}

// more information
$report['100:0:Site Information'] = 'Here is some general information about the site';

$database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// show some information about current setup
$report['-101:1:Current Version'] = VERSION . ' (' . VERSION_NAME . ')';

// get database counts
$report['101:1:Database Counts'] = 'Here is a list of counts for the databases:<br />';
foreach($GLOBALS['databases'] as $i => $db)
{
	$result = $database->query(array('SELECT' => $db, 'COLUMNS' => 'count(*)'));
	if(count($result) > 0)
	{
		$report['101:1:Database Counts'] .= $db . ' database has ' . $result[0]['count(*)'] . ' entries' . (($i != count($GLOBALS['databases'])-1)?'<br />':'');
		if($db == db_watch_list::DATABASE)
		{
			$report['102:1:Watch List'] = 'The watch list contains directories that have to be searched for files<br />There are ' . $result[0]['count(*)'] . ' directories in the watch list database';
			if($result[0]['count(*)'] == 0)
				$report['102.1:5:Note'] = 'All files are added and up to date!';
		}
	}
}

ksort($report, SORT_NUMERIC);

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo HTML_NAME?>: Reports</title>
</head>
<body>
	<form action="" method="post">
		This page can help parse the log files from scripts like cron.php, <br />enter directories or files that contain logs, put each entry on a new line or seperate with a semi-colon.<br />
		<textarea name="logs" rows="6" cols="40"><?php echo isset($_REQUEST['logs'])?$_REQUEST['logs']:''; ?></textarea>
		<br />
		<input type="submit" value="Submit" />
	</form>
	Here is the report information for the listed files:<br />
	<?php
	foreach($report as $key => $line)
	{
		$key = split(':', $key);
		$section = floor($key[0] / 100);
		switch(intval($key[1]))
		{
			case 0:
				print '<br /><b>' . $key[2] . '</b>: ' . $line . "<br /><br />\n";
				break;
			case 1:
				print '<b>' . $key[2] . '</b>: ' . $line . "<br />\n";
				break;
			case 2:
				print '<span style="color:red"><b>Warning: ' . $key[2] . ': </b></span>' . $line . "<br />\n";
				break;
			case 5:
				print '<span style="color:green"><b>' . $key[2] . ': ' . $line . "</b></span><br />\n";
				break;
			default:
				print $key[2] . ': ' . $line . "<br />\n";
		}
	}
	?>
</body>
</html>
