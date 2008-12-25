<?php

// create zip of selected files in list


require_once dirname(__FILE__) . '/../include/common.php';

define('SOCKET_PATH', '/home/bjcullinan/.config/transmission/daemon/socket'); // path to socket that transmission is started using

require_once SITE_LOCALROOT . 'plugins/bttracker/BEncode.php';
require_once SITE_LOCALROOT . 'plugins/bttracker/config.php';
require_once SITE_LOCALROOT . 'plugins/bttracker/funcsv2.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// get listed items from database
if(isset($_REQUEST['zip']))
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
foreach($files as $index => &$file)
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
	
	// do alias replacement on every file path
	$files[$index]['Filepath_alias'] = $files[$index]['Filepath'];
	if(USE_ALIAS == true) $files[$index]['Filepath_alias'] = preg_replace($GLOBALS['paths_regexp'], $GLOBALS['alias'], $file['Filepath']);
	$files[$index]['Filepath_alias'] = str_replace('\\', '/', ($file['Filepath_alias'][0] == '/')?substr($file['Filepath_alias'], 1, strlen($file['Filepath_alias'])-1):$file['Filepath_alias']);

}


if(count($files) > 0)
{
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: application/zip');
	header('Content-Disposition: filename="' . SITE_NAME . '-' . time() . '.zip"'); 
	// loop through files and generate and expected file length
	$length = 22;
	foreach($files as $index => $file) {
        $name     = $file['Filepath_alias'];
		$length += 30 + strlen($name);
		$length += filesize($file['Filepath']) + 12;
		$length += 46 + strlen($name);
	}
	header('Content-Length: ' . $length);
	
	$ctrl_dir = array();
	$old_offset = 0;
	$eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	
	foreach($files as $index => $file)
	{
		$fr_offset = 0;
        $name     = $file['Filepath_alias'];

        $dtime    = dechex(unix2DosTime(0));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
		// 0b00001000 // bit 3 is turned on for using the data descriptor at the bottom of the file
		// this allows us to stream to file and only read it in once, as opposed to reading it in
		// to generate the checksum, then reading it again to output it, we can also output immediately.
		// Also, little-endian is used so these 2 bytes are swapped
        $fr   .= "\x08\x00";            // gen purpose bit flag
        $fr   .= "\x00\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = filesize($file['Filepath']);
        //$crc     = 0; //crc32_file($file['Filepath']); generated below!!!!!!
        $c_len   = filesize($file['Filepath']);
		
        $fr      .= pack('V', 0);             // crc32
        $fr      .= pack('V', 0);           // compressed filesize
        $fr      .= pack('V', 0);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

		print $fr;
		$fr_offset += strlen($fr);
		
		// generate crc32 from stream
        // "file data" segment
		$fp = fopen($file['Filepath'], 'rb');
		$old_crc=false;
		while (!feof($fp)) {
			$buffer=fread($fp, BUFFER_SIZE);
            $len=strlen($buffer);      
            $t=crc32($buffer);   
       
            if ($old_crc) {
                $crc32=crc32_combine($old_crc, $t, $len);
                $old_crc=$crc32;
            } else {
                $crc32=$old_crc=$t;
            }
			
			print $buffer;
		}				
		fclose($fp);
		$fr_offset += filesize($file['Filepath']);
		

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr = pack('V', $crc32);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize
		print $fr;
		$fr_offset += strlen($fr);

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x08\x00";                // gen purpose bit flag
        $cdrec .= "\x00\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc32);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $old_offset); // relative offset of local header
        $old_offset += $fr_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $ctrl_dir[] = $cdrec;
	}

	$ctrldir = implode('', $ctrl_dir);
	print $ctrldir .
	$eof_ctrl_dir .
	pack('v', sizeof($ctrl_dir)) .  // total # of entries "on this disk"
	pack('v', sizeof($ctrl_dir)) .  // total # of entries overall
	pack('V', strlen($ctrldir)) .           // size of central dir
	pack('V', $old_offset) .              // offset to start of central dir
	"\x00\x00";                             // .zip file comment length
	

}

?>