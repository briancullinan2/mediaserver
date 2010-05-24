<?php
// handle bit torrenting of selected files

/**
 * Implementation of register
 * @ingroup register
 */
function register_bt()
{
	return array(
		'name' => lang('bt title', 'BitTorrent Creator'),
		'description' => lang('bt description', 'Bittorrent module that creates torrents for files or directories so users can share downloads with each other.'),
		'privilage' => 1,
		'path' => __FILE__,
		'template' => false,
		'depends on' => array('bttracker_installed', 'seeder'),
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return announce if 'bt' is set in the request, otherwise returns 'bencode'
 */
function validate_bt($request)
{
	if(isset($request['bt']))
		return 'announce';
	else
		return 'bencode';
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return The entire request for use with the bttracker script
 */
function validate_bt_request($request)
{
	return $request['bt_request'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_bt($request)
{
	$request['bt'] = validate_bt($request);
	if($request['bt'] == 'announce')
	{
		global $admin_user, $admin_pass, $dbhost, $dbuser, $dbpass, $database;
		
		$_REQUEST = $request['bt_request'];
		
		// get administrator
		$admin = $GLOBALS['database']->query(array(
				'SELECT' => 'users',
				'WHERE' => 'id = -1',
				'LIMIT' => 1
			)
		, false);
	
		include_once setting('local_root') . 'modules' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'config.php';
		$admin_user = $admin[0]['Username'];
		$admin_pass = $admin[0]['Password'];
		
		$parsed_dsn = parseDSN(DB_CONNECT);
		$dbhost = $parsed_dsn['hostspec'];
		$dbuser = $parsed_dsn['username'];
		$dbpass = $parsed_dsn['password'];
		$database = $parsed_dsn['database'];
	
		include setting('local_root') . 'modules' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'announce.php';
		
	}
	else
	{
		include_once setting('local_root') . 'modules' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'BEncode.php';
		include_once setting('local_root') . 'modules' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'funcsv2.php';
	
		$request['cat'] = validate_cat($request);
		
		// make select call
		$files = call_user_func_array('get_' . $request['cat'], array($request, &$count));
		
		$files_length = count($files);
		
		// the ids handler will do the replacement of the ids
		if(count($files) > 0)
			$files = get_db_ids(array('cat' => $request['cat']), $tmp_count, $files);
		
		// get all the other information from other handlers
		for($index = 0; $index < $files_length; $index++)
		{
			$file = $files[$index];
			$tmp_request = array();
			$tmp_request['file'] = $file['Filepath'];
		
			// merge with tmp_request to look up more information
			$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
				
			// merge all the other information to each file
			foreach($GLOBALS['handlers'] as $i => $handler)
			{
				if($handler != $request['cat'] && is_internal($handler) == false && handles($file['Filepath'], $handler))
				{
					$return = call_user_func_array('get_' . $handler, array($tmp_request, &$tmp_count));
					if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
				}
			}
		
			// for the second pass go through and get all the files inside of folders
			if(isset($file['Filetype']) && $file['Filetype'] == 'FOLDER')
			{
				// get all files in directory
				$props = array('dir' => $file['Filepath']);
				$sub_files = call_user_func_array('get_' . $request['cat'], array($props, &$tmp_count));
				
				// put these files on the end of the array so they also get processed
				$files = array_merge($files, $sub_files);
				$files_length = count($files);
			}
		}
		
		// remove folders so we don't have to worry about them in the series of loops below
		foreach($files as $i => $file)
			if(isset($file['Filetype']) && $file['Filetype'] == 'FOLDER')
				unset($files[$i]);
		$files = array_values($files);
	
		
		if(count($files) > 0)
		{
			
			$total_size = 0;
			
			$torrent = array();
			$torrent['info'] = array();
			$torrent['info']['files'] = array();
			foreach($files as $index => &$file)
			{
				if(setting('admin_alias_enable') == true) $files[$index]['Filepath'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file['Filepath']);
				if(!file_exists($files[$index]['Filepath']))
					continue;
				$file_info = array();
				$file_info['length'] = intval($file['Filesize']);
				$file_info['path'] = split('/', substr($file['Filepath'], strpos($file['Filepath'], '/') + 1));
				$file_into['md5sum'] = md5_file($file['Filepath']);
				$torrent['info']['files'][] = $file_info;
				$total_size += $file['Filesize'];
			}
			$torrent['info']['private'] = 0;
			// use a . as the name so that it can use the root of the file system
			$torrent['info']['name'] = 'Files from ' . setting('html_name');
			$torrent['info']['piece length'] = ($total_size < 1024*1024*10)?pow(2,18):(($total_size < 1024*1024*1024*10)?pow(2,19):pow(2,20));
			$torrent['info']['pieces'] = '';
			
			$current_file = 0;
			$contents = '';
			$read_amount = 0;
			$fp = output_handler($files[$current_file]['Filepath'], $request['cat']);
			while($current_file <= count($files))
			{
				$read_amount = $torrent['info']['piece length'] - strlen($contents);
			
				$contents .= fread($fp, $read_amount);
					
				//print $read_amount . '-' . strlen($contents) . '<br />';
				
				if(feof($fp))
					$current_file++;
				
				// append next file if centents are short
				if(feof($fp) && $current_file < count($files))
				{
					//print $files[$current_file]['Filepath'] . '<br />';
					$fp = output_handler($files[$current_file]['Filepath'], $request['cat']);
				}
				
				// should keep reading files if middle file is shorter then piece length also
				if(strlen($contents) == $torrent['info']['piece length'])
				{
					//print 'new<br/>';
					// sha and append to pieces
					$torrent['info']['pieces'] .= sha1($contents);
					$contents = '';
				}
			}
			if($contents != '')
				$torrent['info']['pieces'] .= sha1($contents);
			
			//print ceil($total_size / $torrent['info']['piece length']) * 20;
			
			$output = '';
			for($i = 0; $i < strlen($torrent['info']['pieces']); $i+=2)
			{
				$output .= chr(hexdec(substr($torrent['info']['pieces'], $i, 2)));
			}
			$torrent['info']['pieces'] = $output;
			
			$torrent['announce'] = url('bt=announce&module=bt', true, true);
			$torrent['creation date'] = time();
			$torrent['comment'] = setting('html_name');
			$torrent['created by'] = setting('html_name');
			
			// encode info to sha1 it and get hash value
			$info_hash = sha1(BEncode($torrent['info']));
			
			// collect tracker info
			$properties = array(
				'info_hash' => $info_hash,
				'filename' => $torrent['info']['name']
			);
			$properties['info'] = $torrent['info']['piece length'] / 1024 * (strlen($torrent['info']['pieces']) / 20) /1024;
			$properties['info'] = round($properties['info'], 2) . " MB";
			if (isset($torrent['comment']))
				$properties['info'] .= " - " . $torrent["comment"];
		
			// insert torrent into tracker
			$query = 'INSERT INTO namemap (info_hash, filename, url, info) VALUES ("' . $properties['info_hash'] . '", "' . $properties['filename'] . '", "", "' . $properties['info'] . '")';
			makeTorrent($info_hash, true);
			quickQuery($query);
			
			// kill previous ctorrent
			$stop = "kill `cat ". escapeshellarg(setting('tmp_dir') . $info_hash . '.pid') . "`";
			system ($stop);
			if(file_exists(setting('tmp_dir') . $info_hash . '.pid'))
				unlink(setting('tmp_dir') . $info_hash . '.pid');
		
			
			// restart ctorrent
			$filename = 'temp-' . time();
			
			if($fp = @fopen(setting('tmp_dir') . $filename . '.torrent', 'w'))
			{
				fwrite($fp, BEncode($torrent));
				fclose($fp);
			}
			$pid = escapeshellarg(setting('tmp_dir') . $info_hash . '.pid');
			$file = escapeshellarg(setting('tmp_dir') . $filename . '.torrent');
			$stat = escapeshellarg(setting('tmp_dir') . $filename . '.stat');
			$command = "cd /tmp/; /usr/local/bin/ctorrent -s / '" . $file . "' > '" . $stat . "' & echo $! > '" . $pid . "'";
			shell_exec($command);
			
			header('Content-Type: application/x-bittorrent');
			header('Content-Disposition: filename="' . setting('html_name') . '-' . time() . '.torrent"'); 
			
			print BEncode($torrent);
		}
	}
}

