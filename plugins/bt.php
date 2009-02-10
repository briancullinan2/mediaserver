<?php

// handle bit torrenting of selected files

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'BEncode.php';
require_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'config.php';
require_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'bttracker' . DIRECTORY_SEPARATOR . 'funcsv2.php';

// load mysql to query the database
if(USE_DATABASE) $mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $mysql = NULL;

// add category and validate it!
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

$files = array();

$count = 0;
$error = '';
// make select call
$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $_REQUEST, $count, $error);

$files_length = count($files);
// get all the other information from other modules
for($index = 0; $index < $files_length; $index++)
{
	$file = $files[$index];

	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && call_user_func(array($module, 'handles'), $file['Filepath']))
		{
			$tmp_count = 0;
			$return = call_user_func(array($module, 'get'), $mysql, array('file' => $file['Filepath']), $tmp_count, $error);
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
		$props['WHERE'] = 'Filepath REGEXP "^' . addslashes(addslashes($file['Filepath'])) . '" AND Filetype != "FOLDER"';
		$sub_files = array();
		$sub_files = db_file::get($mysql, $props);
		// put these files on the end of the array so they also get processed
		$files = array_merge($files, $sub_files);
		$files_length = count($files);
	}
	
	// do alias replacement on every file path
	$files[$index]['Filepath_alias'] = $files[$index]['Filepath'];
	if(USE_ALIAS == true) $files[$index]['Filepath_alias'] = preg_replace($GLOBALS['paths_regexp'], $GLOBALS['alias'], $files[$index]['Filepath']);
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
		$file_info['path'] = split(DIRECTORY_SEPARATOR, substr($file['Filepath_alias'], 1));
		$file_into['md5sum'] = md5_file($file['Filepath_alias']);
		$torrent['info']['files'][] = $file_info;
		$total_size += $file['Filesize'];
	}
	$torrent['info']['private'] = 0;
	// use a . as the name so that it can use the root of the file system
	$torrent['info']['name'] = 'Files from ' . HTML_NAME;
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
	
	$torrent['announce'] = HTML_DOMAIN . HTML_ROOT . HTML_PLUGINS . 'bttracker/announce.php';
	$torrent['creation date'] = time();
	$torrent['comment'] = HTML_NAME;
	$torrent['created by'] = HTML_NAME;
	
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
	header('Content-Disposition: filename="' . HTML_NAME . '-' . time() . '.torrent"'); 
	
	print BEncode($torrent);
	
}

?>