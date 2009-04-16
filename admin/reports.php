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
	
	if($avg_add_count > 0)
		$reports[0][7][(TYPE_BOLD).'-Average Add Speed'] = round(($avg_add / $avg_add_count), 2) . ' Files per Second';
	
	if(isset($look_count))
	{
		$avg_look += $look_count;
		$avg_look_count++;
	}
	
	if($avg_look_count > 0)
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

// show some information about current setup
$reports[-1][1][(TYPE_BOLD).'-Current Version'] = VERSION . ' (' . VERSION_NAME . ')';

// get database counts
$reports[1][1][(TYPE_BOLD).'-Database Counts'] = 'Here is a list of counts for the databases:<br />';
foreach($GLOBALS['tables'] as $i => $db)
{
	$result = $GLOBALS['database']->query(array('SELECT' => $db, 'COLUMNS' => 'count(*)'));
	if(count($result) > 0)
	{
		$reports[1][1][(TYPE_BOLD).'-Database Counts'] .= $db . ' database has ' . $result[0]['count(*)'] . ' entries' . (($i != count($GLOBALS['tables'])-1)?'<br />':'');
		if($db == db_watch_list::DATABASE)
		{
			$reports[1][2][(TYPE_BOLD).'-Watch List'] = 'The watch list contains directories that have to be searched for files<br />There are ' . $result[0]['count(*)'] . ' directories in the watch list database';
			if($result[0]['count(*)'] == 0)
				$reports[1][2.1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_G).'-Note'] = 'All files are added and up to date!';
		}
	}
}

// renaming file section
$reports[2][0][(TYPE_HEADING).'-Ascii File Names'] = 'List of files that have ascii characters in the filename.';

if(isset($_REQUEST['show2']) && $_REQUEST['show2'] == true)
{
	// get some non-standard filenames, ones that contain non ascii characters
	$results = $GLOBALS['database']->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\')) AND (LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video")', 'LIMIT' => '0,15', 'ORDER' => 'Filepath'));
	
	if(count($results) > 0)
	{
		$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] = 'Consider revising the names of these files, here are just a few highlighted in red:';
		
		foreach($results as $i => $file)
		{
			$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] .= '<br />' . preg_replace('/([^\\x20-\\x7E])/i', '<span style="color:rgb(255,0,0);font-weight:bold;">$1</span>', htmlspecialchars($file['Filepath']));
		}
		
		// get total
		$count = $GLOBALS['database']->query(array('SELECT' => db_file::DATABASE, 'COLUMNS' => 'count(*)', 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\'))'));
		
		if(count($count) > 0)
		{
			$reports[2][1.1][(TYPE_BOLD|TYPE_R).'-Non-Ascii Files Count'] = '<br />There is a total of ' . $count[0]['count(*)'] . ' file(s) with non-ascii characters';
		}
		else
		{
			$reports[2][1.1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_R).'-Non-Ascii Files Count'] = '<br />There was an error getting the total count.';
		}
	}
	else
	{
		$reports[2][1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_G).'-Non-Ascii Files'] = 'There are no non-standard character file names';
	}
}

// downloaded files commonly have _ underscores as spaces, find excessive underscores and recommend change
$reports[3][0][(TYPE_HEADING).'-Excessive Underscores and Periods'] = 'List of files that have underscores and periods in the filename.';

if(isset($_REQUEST['show3']) && $_REQUEST['show3'] == true)
{
	// check request for replacements
	if(isset($_POST) && count($_POST) > 0 && isset($_POST['rename']))
	{
		$files = $GLOBALS['database']->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'id=' . join(' OR id=', array_keys($_POST['File']))));

		if(count($files) > 0)
		{
			$files = db_ids::get(array('cat' => 'db_file'), $tmp_count, $tmp_error, $files);
			
			$directories = array();
			$mv_files = array();
			$win_commands = '';
			$nix_commands = '';
			
			// do the renaming
			foreach($files as $index => $file)
			{
				if(dirname($_POST['File'][$file['files_id']]) != dirname($file['Filepath']))
					$different_dir = true;
				else
					$different_dir = false;
					
				// make sure all the folders leading up to it exists
				if($different_dir)
				{
					$dirs = split('/', dirname($_POST['File'][$file['files_id']]));
					$tmpdir = realpath('/');
					foreach($dirs as $i => $dir)
					{
						if($dir != '')
						{
							$tmpdir = $tmpdir . $dir . '/';
							if(!is_file($tmpdir) && !is_dir($tmpdir) && !in_array($tmpdir, $directories))
							{
								$directories[] = $tmpdir;
								$result = @mkdir($tmpdir);
								
								if($result == false)
								{
									$win_commands .= 'md "' . $tmpdir . '"<br />';
									$nix_commands .= 'mkdir "' . $tmpdir . '"<br />';
									break;
								}
							}
						}
					}
				}
				
				// move file
				$result = @rename($file['Filename'], $_POST['File'][$file['files_id']]);
				if($result == false)
				{
					$mv_files[] = $file['Filepath'];
					$win_commands .= 'move "' . $file['Filepath'] . '" "' . $_POST['File'][$file['files_id']] . '"<br />';
					$nix_commands .= 'mv "' . $file['Filepath'] . '" "' . $_POST['File'][$file['files_id']] . '"<br />';
				}
				else
				{
					// replace filename in each module
					foreach($GLOBALS['tables'] as $i => $table)
					{
						if(isset($file[$table . '_id']) && $file[$table . '_id'] != 0)
						{
							$result = $GLOBALS['database']->query(array('UPDATE' => $table, 'VALUES' => array('Filepath' => addslashes($_POST['File'][$file['files_id']])), 'WHERE' => 'id = ' . $file[$table . '_id']));
						}
					}
					
					// update file time, since it is just a move it hasn't really changed
				}
				
				// delete directory if it is empty
				$rm_files = fs_file::get(array('dir' => dirname($file['Filepath']) . '/', 'limit' => 32000), $count, $error, true);
				if($rm_files !== false && isset($_POST['remove_empty']) && $_POST['remove_empty'] == true)
				{
					if(count($rm_files) == 0)
					{
						$result = @rmdir(dirname($file['Filepath']));
						if($result == false)
						{
							$win_commands .= 'rmdir "' . dirname($file['Filepath']) . '"<br />';
							$nix_commands .= 'rmdir "' . dirname($file['Filepath']) . '"<br />';
						}
					}
					// it's possible some command failed that would have been listed
					//  in which case we should list the rmdir command
					elseif($different_dir)
					{
						// check to see if all the files have been moved out of the directory
						$rm_files_cmp = array();
						foreach($rm_files as $j => $file)
						{
							$rm_files_cmp[] = $file['Filepath'];
						}
						
						if(count(array_diff($mv_files, $rm_files_cmp)) == 0)
						{
							$win_commands .= 'rmdir "' . dirname($file['Filepath']) . '"<br />';
							$nix_commands .= 'rmdir "' . dirname($file['Filepath']) . '"<br />';
						}
					}
				}
				else
				{
					print_r(dirname($file['Filepath']));
					print '<br />';
				}
			}
		
			// generate output
			if($win_commands != '')
			{
				$reports[3][1.999][(TYPE_BOLD|TYPE_R).'-Permission Error'] = 'You must temporarily give the webserver write permission or run these commands:<table><tr><td>Windows</td><td>*Nix</td></tr><tr><td>' .
					$win_commands . '</td><td>' . $nix_commands . '</td></tr></table>';
			}
		}
	}
	
	// query some files
	$results = $GLOBALS['database']->query(array('SELECT' => db_file::DATABASE, 'WHERE' => 'Filename REGEXP "^[^ ]*(_.*_.*_|\\\.[^\.]+\\\.[^\.]+\\\.).*$" AND Filename NOT REGEXP BINARY "[^\.]*[A-Z]\\\.[^\.]*[A-Z]\\\.[^\.]*[A-Z]\\\.[^\.]*" AND (LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video")', 'LIMIT' => '0,15', 'ORDER' => 'Filepath'));
	
	if(count($results) > 0)
	{
		$reports[3][1][(TYPE_BOLD).'-Excessive Periods and Underscores'] = 'These files could be better organized, consider revising:<form action="" method="post">';
		
		foreach($results as $i => $file)
		{
			$reports[3][1][(TYPE_BOLD).'-Excessive Periods and Underscores'] .= '<br />' . preg_replace('/(_|\.)/i', '<span style="color:rgb(255,0,0);font-weight:bold;">$1</span>', htmlspecialchars($file['Filepath']));
		
			// figure out what the file should be named
			//  get extension
			$ext = getExt($file['Filename']);
			$file['Filename'] = substr($file['Filename'], 0, strlen($file['Filename']) - strlen($ext) - 1);
			
			//  first replace all the periods or underscores with spaces except for the last period of course
			$file['Filename'] = preg_replace('/[_\.]/', ' ', $file['Filename']);
			
			// split everything up in to pieces
			//  move some key words that describe the rip into parens
			$match_count = preg_match_all('/ (hdtv|xvid|aaf|dsr)-?/i', $file['Filename'], $matches);
			$rip_type = '';
			if(isset($matches[1]) && count($matches[1]) > 0)
			{
				$rip_type = join(' ', $matches[1]);
			}
			
			// remove type
			$file['Filename'] = preg_replace('/ ?(hdtv|xvid|aaf|dsr)/i', '', $file['Filename']);
			
			//  remove the distributers logo
			$file['Filename'] = preg_replace('/ ?(-lol|-notv|-0tv|-xor|-sys|-2hd|-rns)/i', '', $file['Filename']);
			
			$parts = array();
			$parts = array_merge($parts, preg_split('/ - /', $file['Filename']));
			$parent = preg_split('/ - /', preg_replace('/[_\.]/', ' ', basename(dirname($file['Filepath']))));
			if(count($parent) > 1)
				$parts = array_merge($parts, $parent);
			
			foreach($parts as $i => $part)
			{
				// get season - episode if it is in the name
				$parts[$i] = preg_replace('/([0-9][0-9]?)x([0-9][0-9]?)-([0-9][0-9]?)/i', 'season \1 - episode \2 & \3', $parts[$i]);
				$parts[$i] = preg_replace('/([0-9][0-9]?)x([0-9][0-9]?)/i', 'season \1 - episode \2', $parts[$i]);
				$parts[$i] = preg_replace('/s([0-9][0-9]?)e([0-9][0-9]?)e([0-9][0-9]?)/i', 'season \1 - episode \2 & \3', $parts[$i]);
				$parts[$i] = preg_replace('/s([0-9][0-9]?)e([0-9][0-9]?)/i', 'season \1 - episode \2', $parts[$i]);
				$parts[$i] = preg_replace('/(^|[^0-9])([1-9])([0-9]{2})($|[^0-9])/i', '\1season \2 - episode \3\4', $parts[$i]);
				$parts[$i] = preg_replace('/(^|[^0-9])(0[0-9])([0-9])($|[^0-9])/i', '\1season \2 - episode \3\4', $parts[$i]);
				
				// replace all single digits with preceding zeros
				$parts[$i] = preg_replace('/([^0-9])([1-9])([^0-9]|$)/', '${1}0\2\3', $parts[$i]);
				
				if(strlen($parts[$i]) > 0)
				{
					// remove some extra mess
					if($parts[$i][0] == '-' || $parts[$i][0] == ' ') $parts[$i] = substr($parts[$i], 1);
					if($parts[$i][strlen($parts[$i])-1] == ' ') $parts[$i] = substr($parts[$i], 0, strlen($parts[$i]) - 1);
					$parts[$i] = str_replace('  ', ' ', $parts[$i]);
					
					// CAPITALIZE
					$parts[$i] = ucwords($parts[$i]);
					
					// split where the replacement was done
					if(strlen($parts[$i]) > 22)
					{
						$tmp_parts = preg_split('/season [0-9]{2} - episode [0-9]{2}( & [0-9]{2})?/i', $parts[$i]);
						$tmp_str = $parts[$i];
						$index = $i;
						$pos = 0;
						for($i = 0; $i < count($tmp_parts) - 1; $i++)
						{
							$parts['' . $index] = $tmp_parts[$i];
							$pos += strlen($tmp_parts[$i]);
							$index += 1.0 / (count($tmp_parts) + (count($tmp_parts) - 1));
							if(preg_match('/season [0-9]{2} - episode [0-9]{2} & [0-9]{2}/i', substr($tmp_str, $pos, 27)) == 1)
							{
								$parts['' . $index] = substr($tmp_str, $pos, 27);
								$pos += 27;
							}
							else
							{
								$parts['' . $index] = substr($tmp_str, $pos, 22);
								$pos += 22;
							}
							$index += 1.0 / (count($tmp_parts) + (count($tmp_parts) - 1));
						}
						$parts['' . $index] = $tmp_parts[$i];
					}
				}
				
				// remove if empty
				if($parts[$i] == '')
					unset($parts[$i]);
			}
			
			foreach($parts as $i => $part)
			{
				if(strlen($parts[$i]) > 0)
				{
					// remove some extra mess
					if($parts[$i][0] == '-' || $parts[$i][0] == ' ') $parts[$i] = substr($parts[$i], 1);
					if($parts[$i][strlen($parts[$i])-1] == ' ') $parts[$i] = substr($parts[$i], 0, strlen($parts[$i]) - 1);
					$parts[$i] = str_replace('  ', ' ', $parts[$i]);
					$parts[$i] = preg_replace('/^Season ?[0-9]?[0-9]?$/i', '', $parts[$i]);
					$parts[$i] = preg_replace('/^Episode ?[0-9]?[0-9]?$/i', '', $parts[$i]);
					
					// CAPITALIZE
					$parts[$i] = ucwords($parts[$i]);
				}
				
				// remove if empty
				if($parts[$i] == '')
					unset($parts[$i]);
			}
			
			ksort($parts);
			$parts = array_unique(array_values($parts));
			
			print_r($parts);
			
			// create filename
			$file['Filename'] = join(' - ', $parts) . (($rip_type != '')?(' (' . $rip_type . ')'):'') . '.' . $ext;
			
			$file['Filepath'] = dirname($file['Filepath']) . '/' . $file['Filename'];
			
			$reports[3][1][(TYPE_BOLD).'-Excessive Periods and Underscores'] .= ' -> <input type="text" size="150" name="File[' . $file['id'] . ']" value="' . $file['Filepath'] . '" />';
		}
		
		$reports[3][1][(TYPE_BOLD).'-Excessive Periods and Underscores'] .= '<br /><input type="submit" name="rename" value="Rename!" /><input type="checkbox" name="remove_empty" value="true" />Remove empty directories.</form>';
		
		// get total
		$count = $GLOBALS['database']->query(array('SELECT' => db_file::DATABASE, 'COLUMNS' => 'count(*)', 'WHERE' => 'Filename REGEXP "(.*_.*_.*_.*|.*\\\..*\\\..*\\\..*)"'));
		
		if(count($count) > 0)
		{
			$reports[3][1.1][(TYPE_BOLD|TYPE_R).'-Excessive Periods and Underscores Count'] = '<br />There is a total of ' . $count[0]['count(*)'] . ' file(s) with excessive underscores and periods characters';
		}
		else
		{
			$reports[3][1.1][(TYPE_BOLD|TYPE_ENTIRE|TYPE_R).'-Excessive Periods and Underscores Count'] = '<br />There was an error getting the total count.';
		}
	}
}
	
	// organize files with common parts in the name

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
