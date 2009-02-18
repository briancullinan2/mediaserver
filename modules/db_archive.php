<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// set up id3 reader incase any files need it
$GLOBALS['getID3'] = new getID3();

// music handler
class db_archive extends db_file
{
	const DATABASE = 'archive';
	
	const NAME = 'Archives from Database';

	static function columns()
	{
		return array('id', 'Filename', 'Filemime', 'Filesize', 'Compressed', 'Filedate', 'Filetype', 'Filepath');
	}

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
			if(file_exists($last_path . $tmp_file) || $last_path == '')
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
			case 'gz':
			case 'szip':
			case 'tar':
				return true;
			default:
				return false;
		}
		
		return false;

	}

	static function handle($mysql, $file)
	{
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		$inside_path = substr($file, strlen($last_path));
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
		
		$file = $last_path;

		if(db_archive::handles($file))
		{
			// check to see if it is in the database
			$db_archive = $mysql->get(array(
					'TABLE' => db_archive::DATABASE,
					'SELECT' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			
			// try to get music information
			if( count($db_archive) == 0 )
			{
				$fileid = db_archive::add($mysql, $file);
			}
			else
			{
				// check to see if the file was changed
				$db_file = $mysql->get(array(
						'TABLE' => db_file::DATABASE,
						'SELECT' => 'Filedate',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"'
					)
				);
				
				// update audio if modified date has changed
				if( date("Y-m-d h:i:s", filemtime($file)) != $db_file[0]['Filedate'] )
				{
					$id = db_archive::add($mysql, $file, $db_archive[0]['id']);
				}
				
			}

		}
		
	}

	static function add($mysql, $file, $archive_id = NULL)
	{
		// do a little cleanup here
		// if the archive changes remove all it's inside files from the database
		if( $archive_id != NULL )
		{
			print 'Removing archive: ' . $file . "\n";
			$mysql->set('archive', NULL, 'WHERE Filepath REGEXP "^' . addslashes(addslashes($file)) . '"');
		}

		// pull information from $info
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		$inside_path = substr($file, strlen($last_path));
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
		
		$info = $GLOBALS['getID3']->analyze($last_path);
		
		if(isset($info['zip']) && isset($info['zip']['central_directory']))
		{
			$directories = array();
			foreach($info['zip']['central_directory'] as $i => $file)
			{
				if(!in_array($file['filename'], $directories))
				{
					$directories[] = $file['filename'];
					$fileinfo = array();
					$fileinfo['Filepath'] = $last_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['filename']);
					$fileinfo['Filename'] = basename($file['filename']);
					if($file['filename'][strlen($file['filename'])-1] == '/')
					{
						$fileinfo['Filetype'] = 'FOLDER';
						$fileinfo['Filesize'] = 0;
						$fileinfo['Compressed'] = 0;
					}
					else
					{
						$fileinfo['Filetype'] = getExt($file['filename']);
						$fileinfo['Filesize'] = $file['uncompressed_size'];
						$fileinfo['Compressed'] = $file['compressed_size'];
					}
					if($fileinfo['Filetype'] === false)
						$fileinfo['Filetype'] = 'FILE';
					$fileinfo['Filemime'] = getMime($file['filename']);
					$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
					
					print 'Adding file in archive: ' . $fileinfo['Filepath'] . "\n";
					$id = $mysql->set('archive', $fileinfo);
				}
				// get folders leading up to files
				$paths = split('/', $file['filename']);
				unset($paths[count($paths)-1]); // remove last item either a file name or empty
				$current = '';
				foreach($paths as $i => $path)
				{
					$current .= $path . DIRECTORY_SEPARATOR;
					if(!in_array($current, $directories))
					{
						$directories[] = $current;
						$fileinfo = array();
						$fileinfo['Filepath'] = $last_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $current);
						$fileinfo['Filename'] = basename($current);
						$fileinfo['Filetype'] = 'FOLDER';
						$fileinfo['Filesize'] = 0;
						$fileinfo['Compressed'] = 0;
						$fileinfo['Filemime'] = getMime($file['filename']);
						$fileinfo['Filedate'] = date("Y-m-d h:i:s", $file['last_modified_timestamp']);
						
						print 'Adding directory in archive: ' . $fileinfo['Filepath'] . "\n";
						$id = $mysql->set('archive', $fileinfo);
					}
				}
			}
		}
		
		// get entire archive information
		$fileinfo = array();
		$fileinfo['Filepath'] = $last_path;
		$fileinfo['Filename'] = basename($last_path);
		$fileinfo['Compressed'] = $info['filesize'];
		$fileinfo['Filetype'] = getFileType($last_path);
		if(isset($info['zip']) && isset($info['zip']['uncompressed_size']))
			$fileinfo['Filesize'] = $info['zip']['uncompressed_size'];
		else
			$fileinfo['Filesize'] = 0;
		$fileinfo['Filemime'] = getMime($last_path);
		$fileinfo['Filedate'] = date("Y-m-d h:i:s", filemtime($last_path));

		// print status
		if( $archive_id != NULL )
		{
			print 'Modifying archive: ' . $fileinfo['Filepath'] . "\n";
			
			// update database
			$id = $mysql->set('archive', $fileinfo, array('id' => $archive_id));
		
			return $audio_id;
		}
		else
		{
			print 'Adding archive: ' . $fileinfo['Filepath'] . "\n";
			
			// add to database
			$id = $mysql->set('archive', $fileinfo);
			
			return $id;
		}
		
		flush();
		
	}

	static function out($mysql, $file, $stream)
	{
		$paths = split('\\' . DIRECTORY_SEPARATOR, $file);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		$inside_path = substr($file, strlen($last_path));
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if(is_file($last_path))
		{
			$files = $mysql->get(array('TABLE' => db_archive::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($file) . '"'));
			if(count($file) > 0)
			{				
				header('Content-Transfer-Encoding: binary');
				header('Content-Type: ' .  getMime($last_path));
				header('Content-Length: ' . filesize($last_path));
				header('Content-Disposition: attachment; filename="' . basename($last_path) . '"');
				
				if(is_string($stream))
					$op = fopen($stream, 'wb');
				else
					$op = $stream;
				
				if($op !== false)
				{
					if($fp = fopen($last_path, 'rb'))
					{
						while (!feof($fp)) {
							fwrite($op, fread($fp, BUFFER_SIZE));
						}				
						fclose($fp);
						fclose($op);
						return true;
					}
				}
			} else { $error = 'File not found!'; }
		}


		return false;
	}
	
	static function get($mysql, $request, &$count, &$error)
	{
		$files = array();
		
		if(USE_DATABASE)
		{
			// do validation! for the fields we use
			if( !isset($request['start']) || !is_numeric($request['start']) || $request['start'] < 0 )
				$request['start'] = 0;
			if( !isset($request['limit']) || !is_numeric($request['limit']) || $request['limit'] < 0 )
				$request['limit'] = 15;
			if( !isset($request['order_by']) || !in_array($request['order_by'], db_archive::columns()) )
				$request['order_by'] = 'Filepath';
			if( !isset($request['direction']) || ($request['direction'] != 'ASC' && $request['direction'] != 'DESC') )
				$request['direction'] = 'ASC';
			if( isset($request['id']) )
				$request['item'] = $request['id'];
			getIDsFromRequest($request, $request['selected']);

			$props = array();
			$props['ORDER'] = $request['order_by'] . ' ' . $request['direction'];
			$props['LIMIT'] = $request['start'] . ',' . $request['limit'];
			
			// select an array of ids!
			if(isset($request['selected']) && count($request['selected']) > 0 )
			{
				$props['WHERE'] = '';
				foreach($request['selected'] as $i => $id)
				{
					if(is_numeric($id)) {
						$props['WHERE'] .= ' id=' . $id . ' OR';
					} else {
						$props['WHERE'] .= ' Filepath="' . addslashes(pack('H*', $id)) . '" OR';
					}
				}
				$props['WHERE'] = substr($props['WHERE'], 0, strlen($props['WHERE'])-2);
				unset($props['LIMIT']);
				unset($request);
			}
			
			// add where includes
			if(isset($request['includes']) && $request['includes'] != '')
			{
				$props['WHERE'] = '';
				
				// incase an aliased path is being searched for replace it here too!
				if(USE_ALIAS == true) $request['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['includes']);
				$regexp = addslashes(addslashes($request['includes']));
				
				$columns = db_archive::columns();
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
				if(USE_ALIAS == true) $request['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);

				$paths = split('\\' . DIRECTORY_SEPARATOR, $request['dir']);
				$last_path = '';
				foreach($paths as $i => $tmp_file)
				{
					if(file_exists($last_path . $tmp_file) || $last_path == '')
					{
						$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
					} else {
						if(file_exists($last_path))
							break;
					}
				}
				$inside_path = substr($request['dir'], strlen($last_path));
				if(strlen($inside_path) == 0 || $inside_path[0] != '/') $inside_path = DIRECTORY_SEPARATOR . $inside_path;
				if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);
				$request['dir'] = $last_path . $inside_path;

				if(is_file($last_path))
				{
					$dirs = $mysql->get(array('TABLE' => db_archive::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['dir']) . '"'));
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
					} else { $error = 'Archive does not exist!'; }
				} else { $error = 'Archive does not exist!'; }
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
				} else { $error = 'File does not exist!11'; }
			}
		
			if($error == '')
			{
				$props['SELECT'] = db_archive::columns();
				$props['TABLE'] = db_archive::DATABASE;
	
				// get directory from database
				$files = $mysql->get($props);
				
				// this is how we get the count of all the items
				unset($props['LIMIT']);
				$props = array('FROM' => '(' . $mysql->statement_builder($props) . ') AS db_audio');
				$props['SELECT'] = 'count(*)';
				
				$result = $mysql->get($props);
				

				$count = intval($result[0]['count(*)']);
			}
			else
			{
				$count = 0;
				$files = array();
			}
		}
			
		return $files;
	}

	static function cleanup_remove($row, $args)
	{
		$paths = split('\\' . DIRECTORY_SEPARATOR, $row['Filepath']);
		$last_path = '';
		foreach($paths as $i => $tmp_file)
		{
			if(file_exists($last_path . $tmp_file) || $last_path == '')
			{
				$last_path = $last_path . $tmp_file . DIRECTORY_SEPARATOR;
			} else {
				if(file_exists($last_path))
					break;
			}
		}
		$inside_path = substr($row['Filepath'], strlen($last_path));
		if($last_path[strlen($last_path)-1] == DIRECTORY_SEPARATOR) $last_path = substr($last_path, 0, strlen($last_path)-1);

		if( !file_exists($last_path) )
		{
			$args['SQL']->set($args['DB'], NULL, array('Filepath' => addslashes($row['Filepath'])));
			
			print 'Removing ' . $args['DB'] . ': ' . $row['Filepath'] . "\n";
		}
	}
	
	// cleanup the non-existant files
	static function cleanup($mysql, $watched, $ignored)
	{
		// first clear all the items that are no longer in the watch list
		// since the watch is resolved all the items in watch have to start with the watched path
		$where_str = '';
		foreach($watched as $i => $watch)
		{
			// add the files that begin with a path from a watch directory
			$where_str .= ' Filepath REGEXP "^' . addslashes(addslashes($watch['Filepath'])) . '" OR';
		}
		// but keep the ones leading up to watched directories
		// ----------THIS IS THE SAME FUNCTIONALITY FROM THE CRON.PHP SCRIPT
		for($i = 0; $i < count($watched); $i++)
		{
			$folders = split(addslashes(DIRECTORY_SEPARATOR), $watched[$i]['Filepath']);
			$curr_dir = (realpath('/') == '/')?'/':'';
			// don't add the watch directory here because it is already added by the previous loop!
			$length = count($folders);
			unset($folders[$length-1]); // remove the blank at the end
			unset($folders[$length-2]); // remove the last folder which is the watch
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
						// if the USE_ALIAS is true this will only add the folder
						//    if it is in the list of aliases
						$where_str .= ' Filepath = "' . addslashes($curr_dir) . '" OR';
					}
					// but make an exception for folders between an alias and the watch path
					elseif(USE_ALIAS && $between)
					{
						$where_str .= ' Filepath = "' . addslashes($curr_dir) . '" OR';
					}
				}
			}
		}
		// remove last OR
		$where_str = substr($where_str, 0, strlen($where_str)-2);
		$where_str = ' !(' . $where_str . ')';
		// clean up items that are in the ignore list
		foreach($ignored as $i => $ignore)
		{
			$where_str = 'Filepath REGEXP "^' . addslashes(addslashes($ignore)) . '" OR ' . $where_str;
		}
		
		// remove items
		$mysql->set($db, NULL, $where_str);

		// since all the ones not apart of a watched directory is removed, now just check is every file still in the database exists on disk
		$mysql->query('SELECT Filepath FROM ' . $mysql->table_prefix . $db);
		
		$mysql->result_callback('db_archive::cleanup_remove', array('SQL' => new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME), 'DB' => $db));
				
		// remove any duplicates
		$files = $mysql->get($db,
			array(
				'SELECT' => array('MIN(id) as id', 'Filepath', 'COUNT(*) as num'),
				'OTHER' => 'GROUP BY Filepath HAVING num > 1'
			)
		);
		
		// remove first item from all duplicates
		foreach($files as $i => $file)
		{
			$mysql->set($db, NULL, array('id' => $file['id']));
			
			print 'Removing ' . db_archive::DATABASE . ': ' . $file['Filepath'] . "\n";
		}
		
		print "Cleanup for archives complete.\n";
		flush();
		
	}

}

?>
