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
		'depends on' => array('bttracker_installed', 'bttracker_database_installed', 'seeder'),
		'settings' => array('seeder_path', 'seeder_args'),
	);
}

function rewrite_bt($request)
{
	// save the whole request to be used later
	$request['bt_request'] = $request;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The default install path for VLC on windows or linux based on validate_SYSTEM_TYPE
 */
function setting_seeder_path($settings)
{
	if(isset($settings['seeder_path']) && is_file($settings['seeder_path']))
		return $settings['seeder_path'];
	else
	{
		if(setting_system_type($settings) == 'win')
			return '';
		else
			return '/usr/local/bin/ctorrent';
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return The entire arg string for further validation by the configure() function
 */
function setting_seeder_args($settings)
{
	if(isset($settings['seeder_args']) && is_file($settings['seeder_args']))
		return $settings['seeder_args'];
	else
	{
		if(setting_system_type($settings) == 'win')
			return '';
		else
			return '-s / "%IF"';
	}
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_seeder($settings)
{
	$settings['seeder_path'] = setting_seeder_path($settings);
	return file_exists($settings['seeder_path']);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_bttracker_installed($settings)
{
	return (include_path('bttracker/config.php') !== false);
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
 * implementation of status
 * @ingroup status
 */
function status_bt($settings)
{
	$status = array();

	if(dependency('bttracker_installed'))
	{
		$status['bttracker_installed'] = array(
			'name' => 'PHP BTTracker+ Installed',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that bttracker is installed.',
					'Bttracker is an open source torrent tracker.',
				),
			),
			'type' => 'label',
			'value' => 'Bttracker detected',
		);
	}
	else
	{
		$status['bttracker_installed'] = array(
			'name' => 'PHP BTTracker+ Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that bttracker is NOT INSTALLED.',
					'The root of the bttracker must be placed in &lt;site root&gt;/include/bttracker/',
					'Bttracker is an open source torrent tracker.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://phpbttrkplus.sourceforge.net/',
					'text' => 'Get PHP BTTracker+',
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
function configure_bt($settings)
{
	$settings['seeder_path'] = setting_seeder_path($settings);
	
	$options = array();
	
	if(dependency('seeder'))
	{
		$options['seeder_path'] = array(
			'name' => lang('seeder title', 'Seeder'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('seeder description', 'This script requires that a seeder be installed.'),
				),
			),
			'type' => 'text',
			'value' => $settings['seeder_path'],
		);
	}
	else
	{
		$options['seeder_path'] = array(
			'name' => lang('seeder title', 'Seeder'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('seeder description fail', 'The seeder specified is not installed or cannot be run.'),
				),
			),
			'type' => 'text',
			'value' => $settings['seeder_path'],
		);
	}
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_bt($request)
{
	$request['bt'] = validate($request, 'bt');
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
	
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'config.php';
		$admin_user = $admin[0]['Username'];
		$admin_pass = $admin[0]['Password'];
		
		$parsed_dsn = parseDSN(DB_CONNECT);
		$dbhost = $parsed_dsn['hostspec'];
		$dbuser = $parsed_dsn['username'];
		$dbpass = $parsed_dsn['password'];
		$database = $parsed_dsn['database'];
	
		include setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'announce.php';
		
	}
	else
	{
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'BEncode.php';
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'funcsv2.php';
	
		$request['cat'] = validate($request, 'cat');
		
		// make select call
		$files = get_files($request, $count, $request['cat']);
		
		$files_length = count($files);
		
		// the ids handler will do the replacement of the ids
		if(count($files) > 0)
			$files = get_ids(array('cat' => $request['cat']), $tmp_count, $files);
		
		// get all the other information from other handlers
		for($index = 0; $index < $files_length; $index++)
		{
			$file = $files[$index];
			$tmp_request = array();
			$tmp_request['file'] = $file['Filepath'];
		
			// merge with tmp_request to look up more information
			$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
				
			// merge all the other information to each file
			foreach($GLOBALS['modules'] as $i => $handler)
			{
				if($handler != $request['cat'] && !is_internal($handler) && handles($file['Filepath'], $handler))
				{
					$return = get_files($tmp_request, $tmp_count, $handler);
					if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
				}
			}
		
			// for the second pass go through and get all the files inside of folders
			if(isset($file['Filetype']) && $file['Filetype'] == 'FOLDER')
			{
				// get all files in directory
				$props = array('dir' => $file['Filepath']);
				$sub_files = get_files($props, $tmp_count, $request['cat']);
				
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

