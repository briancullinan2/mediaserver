<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_filetools()
{
	$tools = array(
		'name' => 'File Tools',
		'description' => 'Tools for reorganizing files and folders easily and quickly.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Ascii File Names',
				'description' => 'List of files in the database that have strange named and the option to fix them.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Excessive Underscores and Periods',
				'description' => 'Options to find and rename files that have many underscores and periods, such as files downloaded from the internet.',
				'privilage' => 10,
				'path' => __FILE__
			)
		)
	);
	
	return $tools;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_filetools($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
		
	// renaming file section
	$reports[2][0][(TYPE_HEADING).'-Ascii File Names'] = 'List of files that have ascii characters in the filename.';

	if(isset($_REQUEST['show2']) && $_REQUEST['show2'] == true)
	{
		// get some non-standard filenames, ones that contain non ascii characters
		$results = $GLOBALS['database']->query(array('SELECT' => database('db_file'), 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\')) AND (LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video")', 'LIMIT' => '0,15', 'ORDER' => 'Filepath'));
		
		if(count($results) > 0)
		{
			$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] = 'Consider revising the names of these files, here are just a few highlighted in red:';
			
			foreach($results as $i => $file)
			{
				$reports[2][1][(TYPE_BOLD).'-Non-Ascii Files'] .= '<br />' . preg_replace('/([^\\x20-\\x7E])/i', '<span style="color:rgb(255,0,0);font-weight:bold;">$1</span>', htmlspecialchars($file['Filepath']));
			}
			
			// get total
			$count = $GLOBALS['database']->query(array('SELECT' => database('db_file'), 'COLUMNS' => 'count(*)', 'WHERE' => 'Filepath REGEXP(CONCAT(\'[^\',CHAR(32),\'-\',CHAR(126),\']\'))'));
			
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
			$files = $GLOBALS['database']->query(array('SELECT' => database('db_file'), 'WHERE' => 'id=' . join(' OR id=', array_keys($_POST['File']))));
	
			if(count($files) > 0)
			{
				$files = get_db_ids(array('cat' => 'db_file'), $tmp_count, $files);
				
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
						// replace filename in each handler
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
					$rm_files = get_fs_file(array('dir' => dirname($file['Filepath']) . '/', 'limit' => 32000), 'filesystem', $count);
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
		$results = $GLOBALS['database']->query(array('SELECT' => database('db_file'), 'WHERE' => 'Filename REGEXP "^[^ ]*(_.*_.*_|\\\.[^\.]+\\\.[^\.]+\\\.).*$" AND Filename NOT REGEXP BINARY "[^\.]*[A-Z]\\\.[^\.]*[A-Z]\\\.[^\.]*[A-Z]\\\.[^\.]*" AND (LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video")', 'LIMIT' => '0,15', 'ORDER' => 'Filepath'));
		
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
			$count = $GLOBALS['database']->query(array('SELECT' => database('db_file'), 'COLUMNS' => 'count(*)', 'WHERE' => 'Filename REGEXP "(.*_.*_.*_.*|.*\\\..*\\\..*\\\..*)"'));
			
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
}

