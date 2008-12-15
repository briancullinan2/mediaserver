<?php

// handle bit torrenting of selected files

require_once dirname(__FILE__) . '/../include/common.php';

define('SOCKET_PATH', '/home/bjcullinan/.config/transmission/daemon/socket'); // path to socket that transmission is started using

require_once SITE_LOCALROOT . 'plugins/bttracker/BEncode.php';
require_once SITE_LOCALROOT . 'plugins/bttracker/config.php';
require_once SITE_LOCALROOT . 'plugins/bttracker/funcsv2.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get listed items from database
if(isset($_REQUEST['btorrent']))
{
	$selected = array();
	
	if(isset($_REQUEST['item']))
	{
		if(is_string($_REQUEST['item']))
		{
			$selected = split(',', $_REQUEST['item']);
		}
		elseif(is_array($_REQUEST['item']))
		{
			foreach($_REQUEST['item'] as $id => $value)
			{
				if(($value == 'on' || $_REQUEST['select'] == 'All') && !in_array($id, $selected))
				{
					$selected[] = $id;
				}
				elseif(($value == 'off' || $_REQUEST['select'] == 'None') && ($key = array_search($id, $selected)) !== false)
				{
					unset($selected[$key]);
				}
			}
		}
	}
	
	if(isset($_REQUEST['on']))
	{
		$_REQUEST['on'] = split(',', $_REQUEST['on']);
		foreach($_REQUEST['on'] as $i => $id)
		{
			if(!in_array($id, $selected) && $id != '')
			{
				$selected[] = $id;
			}
		}
	}
	
	if(isset($_REQUEST['off']))
	{
		$_REQUEST['off'] = split(',', $_REQUEST['off']);
		foreach($_REQUEST['off'] as $i => $id)
		{
			if(($key = array_search($id, $selected)) !== false)
			{
				unset($selected[$key]);
			}
		}
	}
	
	$selected = array_values($selected);	
	if(count($selected) == 0) unset($selected);

}

// initialize properties for select statement
$props = array();

// add category
if(!isset($_REQUEST['cat']))
{
	$_REQUEST['cat'] = 'db_file';
}

$files = array();

// add where includes
if( isset($selected) && count($selected) > 0 )
{
	$props['WHERE'] = 'id=' . join(' OR id=', $selected);
	unset($props['OTHER']);

	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);
}

// get all the other information from other modules
foreach($files as $index => $file)
{
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && call_user_func(array($module, 'handles'), $file['Filepath']))
		{
			$return = call_user_func(array($module, 'get'), $mysql, array('WHERE' => 'Filepath = "' . $file['Filepath'] . '"'));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}

	// for the second pass go through and get all the files inside of folders
	if($file['Filetype'] == 'FOLDER')
	{
		// remove folder from list since it can't be apart of the .torrent
		unset($files[$index]);
		// get all files in directory
		$props = array();
		$props['WHERE'] = 'Filepath REGEXP "^' . $file['Filepath'] . '" AND Filetype != "FOLDER"';
		$sub_files = array();
		$sub_files = db_file::get($mysql, $props);
		// put these files on the end of the array so they also get processed
		$files = array_merge($files, $sub_files);
	}
}


if(count($files) > 0)
{
	
	$total_size = 0;
	
	$torrent = array();
	$torrent['info'] = array();
	$torrent['info']['files'] = array();
	foreach($files as $index => $file)
	{
		$file_info = array();
		$file_info['length'] = intval($file['Filesize']);
		$file_info['path'] = split('/', substr($file['Filepath'], 1));
		$file_into['md5sum'] = md5_file($file['Filepath']);
		$torrent['info']['files'][] = $file_info;
		$total_size += $file['Filesize'];
	}
	$torrent['info']['private'] = 0;
	// use a . as the name so that it can use the root of the file system
	$torrent['info']['name'] = 'Files from ' . SITE_NAME;
	$torrent['info']['piece length'] = ($total_size < 1024*1024*10)?pow(2,18):(($total_size < 1024*1024*1024*10)?pow(2,19):pow(2,20));
	$torrent['info']['pieces'] = '';
	
	$current_file = 0;
	$contents = '';
	$read_amount = 0;
	$fp = fopen($files[$current_file]['Filepath'], 'r');
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
			$fp = fopen($files[$current_file]['Filepath'], 'r');
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
	
	$torrent['announce'] = 'http://192.168.1.101/' . SITE_PLUGINS . 'bttracker/announce.php';
	$torrent['creation date'] = time();
	$torrent['comment'] = SITE_NAME;
	$torrent['created by'] = SITE_NAME;
	
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
	$stop = "kill `cat ". escapeshellarg(TMP_DIR . $info_hash . '.pid') . "`";
	system ($stop);
	if(file_exists(TMP_DIR . $info_hash . '.pid'))
		unlink(TMP_DIR . $info_hash . '.pid');

	
	// restart ctorrent
	$filename = 'temp-' . time();
	
	if($fp = fopen(TMP_DIR . $filename . '.torrent', 'w'))
	{
		fwrite($fp, BEncode($torrent));
		fclose($fp);
	}
	$pid = escapeshellarg(TMP_DIR . $info_hash . '.pid');
	$file = escapeshellarg(TMP_DIR . $filename . '.torrent');
	$stat = escapeshellarg(TMP_DIR . $filename . '.stat');
	$command = "cd /tmp/; /usr/local/bin/ctorrent -s / '" . $file . "' > '" . $stat . "' & echo $! > '" . $pid . "'";
	shell_exec($command);
	
	header('Content-Type: application/x-bittorrent');
	header('Content-Disposition: filename="' . SITE_NAME . '-' . time() . '.torrent"'); 
	
	print BEncode($torrent);
	
}

?>