<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?

require_once LOCAL_ROOT . 'modules/db_file.php';

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class fs_archive extends db_file
{
	const DATABASE = 'archives';
	
	const NAME = 'Archives';

	static function handles($file)
	{
		// parse through the file path and try to find a zip
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		$last_ext = '';
		foreach($paths as $i => $tmp_file)
		{
			// this will continue until either the end of the requested file (a .zip extension for example)
			// or if the entire path exists then it must be an actual folder on disk with a .zip in the name
			if(file_exists($last_path . $tmp_file))
			{
				$last_ext = getExt($last_path . $tmp_file);
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			}
			else
			{
				// if the last path exists and the last $ext is an archive then we know the path is inside an archive
				if(file_exists($last_path))
				{
					// we can break
					break;
				}
			}
		}
		
		switch($last_ext)
		{
			case 'zip':
			case 'rar':
			case 'gzip':
			case 'szip':
			case 'tar':
				return true;
			default:
				return false;
		}
		
		return false;

	}

	// this function determines if the file qualifies for this type and handles it according
	static function handle($mysql, $file)
	{
		// files always qualify, we are going to log every single one!
		
		/*if(db_file::handles($file))
		{
			
			// check if it is in the database
			$fs_archive = $mysql->get('files', 
				array(
					'SELECT' => array('id', 'Filedate'),
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			if( count($db_file) == 0 )
			{
				// always add to file database
				$id = fs_archive::add($mysql, $file);
			}
			else
			{
				$filename = $file;

				// update file if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($filename)) != $fs_archive[0]['Filedate'] )
				{
					$id = fs_archive::add($mysql, $file, $fs_archive[0]['id']);
				}
				else
				{
					print 'Skipping file: ' . $file . "\n";
				}
				
			}
			
		}*/
		
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		// do validation! for the fields we use
		if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
			$request['start'] = 0;
		if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
			$request['limit'] = 15;
		if( !isset($request['order_by']) || !in_array($request['order_by'], fs_archive::columns()) )
			$request['order_by'] = 'Title';
		if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
			$request['direction'] = 'ASC';
		if( isset($request['id']) )
			$request['item'] = $request['id'];
		getIDsFromRequest($request, $request['selected']);

		$files = array();
		
		if($mysql == NULL)
		{
			if(isset($request['file']))
			{
				if(is_file($request['file']))
				{
					if(fs_archive::handles($request['file']))
					{
						return array(0 => fs_archive::getInfo($request['file']));
					}
					else{ $error = 'Invalid ' . fs_archive::NAME . ' file!'; }
				}
				else{ $error = 'File does not exist!'; }
			}
			else
			{
				if(!isset($request['dir']))
					$request['dir'] = realpath('/');
				if (is_dir($request['dir']))
				{
					$tmp_files = scandir($request['dir']);
					$count = count($tmp_files);
					for($j = 0; $j < $count; $j++)
						if(!fs_archive::handles($request['dir'] . $tmp_files[$j])) unset($tmp_files[$j]);
					$tmp_files = array_values($tmp_files);
					$count = count($tmp_files);
					for($i = $request['start']; $i < min($request['start']+$request['limit'], $count); $i++)
					{
						$info = fs_archive::getInfo($request['dir'] . $tmp_files[$i]);
						$info['Filepath'] = stripslashes($info['Filepath']);
						if($info['Filetype'] == 'FOLDER') $info['Filepath'] .= DIRECTORY_SEPARATOR;
						$files[] = $info;
					}
					return $files;
				}
				// maybe they are trying to access a zip file as if it were a folder
				// this is perfectly acceptable so lets check to see if this module handles it
				if(fs_archive::handles($request['dir']))
				{
					$ext = getExt($request['dir']);
					if(strpos($ext, DIRECTORY_SEPARATOR) !== false) $archive_dir = substr($ext, strpos($ext, DIRECTORY_SEPARATOR));
					else $archive_dir = '';
					$request['dir'] = substr($request['dir'], 0, strlen($request['dir']) - strlen($archive_dir));
					if(strlen($archive_dir) > 0 && $archive_dir[0] == DIRECTORY_SEPARATOR) $archive_dir = substr($archive_dir, strlen(DIRECTORY_SEPARATOR));
					$archive_dir = str_replace(DIRECTORY_SEPARATOR, '\/', $archive_dir);
					
					// make sure the file they are trying is access is actually a file
					if(is_file($request['dir']))
					{
						// analyze the file and output the files it contains
						$info = $GLOBALS['getID3']->analyze($request['dir']);
						
						// loop through central directory and list files with information
						foreach($info['zip']['central_directory'] as $i => $file)
						{
							if(preg_match('/^' . $archive_dir . '[^\/]+\/?$/i', $file['filename']) !== 0)
							{
								$fileinfo = array();
								$fileinfo['Filepath'] = $request['dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
								$fileinfo['Filename'] = basename($file['filename']);
								if($file['filename'][strlen($file['filename'])-1] == '/')
									$fileinfo['Filetype'] = 'FOLDER';
								//$fileinfo['Filesize'] = filesize($file);
								//$fileinfo['Filemime'] = getMime($file);
								//$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($file));
								$files[] = $fileinfo;
								
							}
						}
					}
					else
					{
						$error = 'File does not exist!';
					}
				}
				else{ $error = 'Directory does not exist!'; }
			}
		}
		else
		{
			$props = array();
			
			$props['OTHER'] = ' ORDER BY ' . $request['order_by'] . ' ' . $request['direction'] . ' LIMIT ' . $request['start'] . ',' . $request['limit'];
			
			// select an array of ids!
			if( isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = 'id=' . join(' OR id=', $selected);
				unset($props['OTHER']);
			}
			
			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				// add a regular expression matching for each column in the table being searched
				$props['WHERE'] .= '(';
				foreach($columns as $i => $column)
				{
					$columns[$i] .= ' REGEXP "' . $regexp . '"';
				}
				$props['WHERE'] .= join(' OR ', $columns) . ')';
			}
			
			// add dir filter to where
			if(isset($request['dir']))
			{
				if($request['dir'] == '') $request['dir'] = DIRECTORY_SEPARATOR;
				if($request['dir'][0] == '/' || $request['dir'][0] == '\\') $request['dir'] = realpath('/') . substr($request['dir'], 1);
				if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
				if(realpath($request['dir']) !== false && is_dir(realpath($request['dir'])))
				{
					$dirs = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
					if($request['dir'] == realpath('/') || count($dirs) > 0)
					{
						if(!isset($props['WHERE'])) $props['WHERE'] = '';
						elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
						if(!isset($request['includes']))
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '?$"';
						}
						else
						{
							if(isset($request['dirs_only']))
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '([^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ')*$"';
							else
								$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($request['dir'])) . '"';
						}
					} else { $error = 'Directory does not exist!'; }
				} else { $error = 'Directory does not exist!'; }
			}
			
			// add file filter to where
			if(isset($request['file']))
			{
				if($request['file'][0] == '/' || $request['file'][0] == '\\') $request['file'] = realpath('/') . substr($request['file'], 1);
				if(USE_ALIAS == true) $request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
				if(realpath($request['file']) !== false && is_file(realpath($request['file'])))
				{
					if(!isset($props['WHERE'])) $props['WHERE'] = '';
					elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
					$props['WHERE'] .= ' Filepath = "' . addslashes($request['file']) . '"';
				} else { $error = 'File does not exist!'; }
			}
		
			$props['SELECT'] = fs_archive::columns();

			// get directory from database
			$files = $mysql->get(fs_archive::DATABASE, $props);
			
			// this is how we get the count of all the items
			unset($props['OTHER']);
			$props['SELECT'] = 'count(*)';
			
			$result = $mysql->get(fs_archive::DATABASE, $props);
			
			$count = intval($result[0]['count(*)']);
		}
			
		return $files;
	}


	static function cleanup($mysql, $watched)
	{
	}
}

?>