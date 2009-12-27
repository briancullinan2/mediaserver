<?php
set_time_limit(0);
ignore_user_abort(1);
define('ZIP_PRIV', 				1);

// create zip of selected files in list

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if( $_SESSION['privilage'] < ZIP_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_ROOT . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . ZIP_PRIV);
	
	exit();
}

// if none of the following is defined, tokenize and search
if(!isset($_REQUEST['id']) && !isset($_REQUEST['item']) && !isset($_REQUEST['on']) && !isset($_REQUEST['file']) && !isset($_REQUEST['search']))
{
	$request_tokens = tokenize(join('&', $_REQUEST));
	$_REQUEST['search'] = join(' ', $request_tokens['All']);
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']) || constant($_REQUEST['cat'] . '::INTERNAL') == true)
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

$files = array();

$count = 0;
$error = '';

// make select call
$files = call_user_func_array($_REQUEST['cat'] . '::get', array($_REQUEST, &$count, &$error));
$files_length = count($files);

// the ids module will do the replacement of the ids
if(count($files) > 0)
	$files = db_ids::get(array('cat' => $_REQUEST['cat']), $tmp_count, $tmp_error, $files);

// get all the other information from other modules
for($index = 0; $index < $files_length; $index++)
{
	$file = $files[$index];
	$tmp_request = array();
	$tmp_request['file'] = $file['Filepath'];

	// merge with tmp_request to look up more information
	$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
	
	// replace id with centralized id
	if(count(array_intersect_key($file, getIDKeys())) == 0)
	{
		// use the module_id column to look up keys
		$ids = db_ids::get(array('file' => $file['Filepath'], constant($_REQUEST['cat'] . '::DATABASE') . '_id' => $file['id']), $tmp_count, $tmp_error);
		if(count($ids) > 0)
		{
			$files[$index] = array_merge($ids[0], $files[$index]);
			// also set id to centralize id
			$files[$index]['id'] = $ids[0]['id'];
		}
	}
	
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($file['Filepath'])))
		{
			$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count, &$tmp_error));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}
	
	// for the second pass go through and get all the files inside of folders
	if(isset($file['Filetype']) && $file['Filetype'] == 'FOLDER')
	{
		// get all files in directory
		$props = array('dir' => $file['Filepath']);
		$sub_files = call_user_func_array((USE_DATABASE?'db_':'fs_') . 'file::get', array($props, &$tmp_count, &$tmp_error));
		
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

// close session so the client can continue browsing the site
if(isset($_SESSION)) session_write_close();

if(count($files) > 0)
{
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: application/zip');
	header('Content-Disposition: filename="' . HTML_NAME . '-' . time() . '.zip"'); 
	
	// loop through files and generate and expected file length
	$length = 22;
	foreach($files as $index => $file) {
        $name    = substr($file['Filepath'], strpos($file['Filepath'], '/') + 1);
		$length += 30 + strlen($name);
		$length += $file['Filesize'] + 12;
		$length += 46 + strlen($name);
	}
	header('Content-Length: ' . $length);
	
	$ctrl_dir = array();
	$old_offset = 0;
	$eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	
	foreach($files as $index => $file)
	{
		$fr_offset = 0;
        $name     = substr($file['Filepath'], strpos($file['Filepath'], '/') + 1);

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
        $unc_len = $file['Filesize'];
        //$crc     = 0; //crc32_file($file['Filepath']); generated below!!!!!!
        $c_len   = $file['Filesize'];
		
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
		$fp = call_user_func_array($_REQUEST['cat'] . '::out', array($file['Filepath']));
		$old_crc=false;
		$buffer = '';
		while (!feof($fp)) {
			if(connection_status()!=0)
			{
				break;
			}
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
			flush();
		}				
		fclose($fp);
		$fr_offset += $file['Filesize'];
		if(connection_status()!=0)
		{
			break;
		}
		

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