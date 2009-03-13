<?php

// check for obvious information first

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// how many directories still to search
// how many files added in the last 24 hours
// load multiple log files
// graph the log files
/*
The report is structured as follows
$reports[section][order][type-title] = message
order 0 and type 0 is the title of the section and the short description
A decimal number can be used in the ordering when the message is directely related to the integer message
A section less then 0 is always displayed at the top of the page, this can be used for error reporting
Use the bit mask to describe the type of entry and how to display it
*/
// use to indicate the section is the header
define('TYPE_HEADING', 		128);

// applies the appearance to the entire entry (0 is just the title)
define('TYPE_ENTIRE', 		16);

// puts bold around the entry
define('TYPE_BOLD', 		8);

// makes the entry red, usually used to indicate an error, can be combined with TYPE_G to inidicate a warning
define('TYPE_R', 		4);
// makes the entry green, used to indicate a success
define('TYPE_G', 		2);
// makes the entry blue, used to indicate some notice
define('TYPE_B', 		1);

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

$reports = array();

$reports[0][0][(TYPE_HEADING).'-Log Parser'] = 'Parse log files and view useful information.';

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
							$reports[0][1][(TYPE_BOLD).'-Last Run Time'] = date('m/d/Y:H:i:s O', $time);
							
						// do version compare and make suggestion
						$version = split('_', $matches[4]);
						if(version_compare($last_version, $version[0]) == -1)
						{
							$in_most_recent = true;
							$last_version = $version[0];
							$reports[0][2][(TYPE_BOLD).'-Last Version'] = $last_version . ' (' . $version[1] . ')';
						}
						else
						{
							$in_most_recent = false;
						}
						
						if(version_compare($last_version, VERSION) == -1)
						{
							$reports[0][2.1][(TYPE_BOLD|TYPE_R).'-Upgrade'] = 'The last version of the logs are before your current running version.<br />Possible causes of this include:<br />You are running a remove script that needs to be upgraded!';
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
							$reports[0][3][(TYPE_BOLD).'-Ignore List'] = 'Here is the ignore list for the most recent cron run:';
							$ignored = @unserialize($matches[4]);
							if($ignored === false || !is_array($ignored))
							{
								$reports[0][3.1][(TYPE_BOLD|TYPE_R).'-Ignore List'] = 'Error loading list!';
							}
							else
							{
								foreach($ignored as $i => $ignore)
								{
									$reports[0][3][(TYPE_BOLD).'-Ignore List'] .= '<br />' . htmlspecialchars($ignore['Filepath']); 
								}
							}
						}
					}
					elseif($matches[3] == 'Watched')
					{
						if($in_most_recent)
						{
							$reports[0][4][(TYPE_BOLD).'-Watch List'] = 'Here is the watch list for the most recent cron run:';
							$watched = @unserialize($matches[4]);
							if($watched === false || !is_array($watched))
							{
								$reports[0][4.1][(TYPE_BOLD|TYPE_R).'-Watch List'] = 'Error loading list!';
							}
							else
							{
								foreach($watched as $i => $watch)
								{
									$reports[0][4][(TYPE_BOLD).'-Watch List'] .= '<br />' . htmlspecialchars($watch['Filepath']); 
								}
							}
						}
					}
					elseif($matches[3] == 'State')
					{
						if($in_most_recent)
						{
							$reports[0][5][(TYPE_BOLD).'-State List'] = 'Here is the most recent state:';
							$state = @unserialize($matches[4]);
							if($state === false || !is_array($state))
							{
								$reports[0][5.1][(TYPE_BOLD|TYPE_R).'-State List'] = 'Error loading state!';
							}
							else
							{
								foreach($state as $i => $value)
								{
									$reports[0][5][(TYPE_BOLD).'-State List'] .= '<br />' . htmlspecialchars(@$value['file']); 
								}
							}
						}
					}
					elseif($matches[3] == 'Clean Count')
					{
						if($in_most_recent)
						{
							$reports[0][6][(TYPE_BOLD).'-Cleanup Count'] = 'The most recent cleanup count is ' . $matches[4];
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
						$reports[0][-1000][(TYPE_BOLD|TYPE_R).'-Fatal Error'] = 'A fatal error was detected in the logs! Line ' . $line_count . ' in file ' . $file . '<br />Reinstalling the script is recommended';
					}
					elseif(strpos($line, 'Warning') !== false)
					{
						$reports[0][-1000][(TYPE_BOLD|TYPE_R).'-Warning Detected'] = 'A warning was detected in the logs! Line ' . $line_count . ' in file ' . $file . '<br />Possible causes include:<br />Files are inaccessible by the script<br />Permission problems<br />Configuration problems';
					}
				}
			}
			fclose($fh);
		}
	}
	
	$reports[0][7][(TYPE_BOLD).'-Average Add Speed'] = round(($avg_add / $avg_add_count), 2) . ' Files per Second';
	
	if(isset($look_count))
	{
		$avg_look += $look_count;
		$avg_look_count++;
	}
	
	$reports[0][8][(TYPE_BOLD).'-Average Looks'] = round(($avg_look / $avg_look_count), 2) . ' Directories were searched for changes per run';
}
elseif(file_exists(TMP_DIR . 'mediaserver.log'))
{
	$_REQUEST['logs'] = TMP_DIR . 'mediaserver.log';
}
// the log field
$reports[0][0.1][(TYPE_BOLD).'-Logs'] = '	<form action="" method="post">
		Enter directories or files that contain logs, put each entry on a new line or seperate with a semi-colon.<br />
		<textarea name="logs" rows="6" cols="40">' . (isset($_REQUEST['logs'])?$_REQUEST['logs']:'') . '</textarea>
		<br />
		<input type="submit" value="Submit" />
	</form>
';

// more information
$reports[1][0][(TYPE_HEADING).'-Site Information'] = 'General information about the site.';

$database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// show some information about current setup
$reports[-1][1][(TYPE_BOLD).'-Current Version'] = VERSION . ' (' . VERSION_NAME . ')';

// get database counts
$reports[1][1][(TYPE_BOLD).'-Database Counts'] = 'Here is a list of counts for the databases:<br />';
foreach($GLOBALS['databases'] as $i => $db)
{
	$result = $database->query(array('SELECT' => $db, 'COLUMNS' => 'count(*)'));
	if(count($result) > 0)
	{
		$reports[1][1][(TYPE_BOLD).'-Database Counts'] .= $db . ' database has ' . $result[0]['count(*)'] . ' entries' . (($i != count($GLOBALS['databases'])-1)?'<br />':'');
		if($db == db_watch_list::DATABASE)
		{
			$reports[1][2][(TYPE_BOLD).'-Watch List'] = 'The watch list contains directories that have to be searched for files<br />There are ' . $result[0]['count(*)'] . ' directories in the watch list database';
			if($result[0]['count(*)'] == 0)
				$reports[1][2.1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_G).'-Note'] = 'All files are added and up to date!';
		}
	}
}

// renaming file section
$reports[2][0][(TYPE_HEADING).'-Funny File Names'] = 'List of files that have strange names';

if(isset($_REQUEST['show2']) && $_REQUEST['show2'] == true)
{
	// get some non-standard filenames, ones that contain non ascii characters
	$results = $database->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\'))', 'LIMIT' => '0,15', 'ORDER' => 'Filepath'));
	
	if(count($results) > 0)
	{
		$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] = 'Consider revising the names of these files, here are just a few highlighted in red:';
		
		foreach($results as $i => $file)
		{
			$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] .= '<br />' . preg_replace('/([^\\x20-\\x7E])/i', '<span style="color:rgb(255,0,0);font-weight:bold;">$1</span>', htmlspecialchars($file['Filepath']));
			
			// get total
			$count = $database->query(array('SELECT' => db_file::DATABASE, 'COLUMNS' => 'count(*)', 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\'))'));
			
			if(count($count) > 0)
			{
				$reports[2][1.1][(TYPE_BOLD|TYPE_R).'-Non-Ascii Files Count'] = '<br />There is a total of ' . $count[0]['count(*)'] . ' file(s) with non-ascii characters';
			}
			else
			{
				$reports[2][1.1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_R).'-Non-Ascii Files Count'] = '<br />There was an error getting the total count.';
			}
		}
	}
	else
	{
		$reports[2][1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_G).'-Non-Ascii Files'] = 'There are no non-standard character file names';
	}
	
	// downloaded files commonly have _ underscores as spaces, find excessive underscores and recommend change
	
}

ksort($reports, SORT_NUMERIC);
foreach($reports as $section => $report)
{
	ksort($reports[$section], SORT_NUMERIC);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo HTML_NAME?>: Reports</title>
</head>
<body>
	View different types of reports by selecting the link and following the instructions.<br />
	<?php
	foreach($reports as $section => $report)
	{
		foreach($report as $order => $lines)
		{
			foreach($lines as $key => $line)
			{
				$type = intval(substr($key, 0, strpos($key, '-')));
				$title = substr($key, strpos($key, '-')+1);
				if(!isset($previous_section)) $previous_section = $section;
				if((isset($_REQUEST['show'.$section]) && $_REQUEST['show'.$section] == true) || ($type & TYPE_HEADING) > 0 || $section < 0)
				{
					if(($type & TYPE_HEADING) > 0)
					{
						print '<br /><span style="font-weight:bold;' . 'color:rgb(' . ((($type&TYPE_R)>0)?'255,':'0,') . ((($type&TYPE_G)>0)?'255,':'0,') . ((($type&TYPE_B)>0)?'255':'0') . ');"><a href="?show' . $section . '=' . (isset($_REQUEST['show'.$section])?!$_REQUEST['show'.$section]:true) . '">' . $title . '</a>: ' . ((($type&TYPE_ENTIRE)==0)?'</span>':'') . $line . ((($type&TYPE_ENTIRE)>0)?'</span>':'') . "<br />\n";
					}
					else
					{
						print '<span style="' . ((($type&TYPE_BOLD)>0)?'font-weight:bold;':'') . 'color:rgb(' . ((($type&TYPE_R)>0)?'255,':'0,') . ((($type&TYPE_G)>0)?'255,':'0,') . ((($type&TYPE_B)>0)?'255':'0') . ');">' . $title . ': ' . ((($type&TYPE_ENTIRE)==0)?'</span>':'') . $line . ((($type&TYPE_ENTIRE)>0)?'</span>':'') . "<br />\n";
					}
				}
			}
		}
	}
	?>
</body>
</html>
