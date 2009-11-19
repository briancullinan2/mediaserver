<?php
set_time_limit(0);
ignore_user_abort(1);

$fp = fopen('/tmp/test.txt', 'a');
fwrite($fp, var_export($_SERVER, true));
fclose($fp);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';
session_cache_limiter("nocache");

// if none of the following is defined, tokenize and search
if(!isset($_REQUEST['id']) && !isset($_REQUEST['item']) && !isset($_REQUEST['on']) && !isset($_REQUEST['file']) && !isset($_REQUEST['search']))
{
	$request_tokens = tokenize(join('&', $_REQUEST));
	$_REQUEST['search'] = join(' ', $request_tokens['All']);
}

// check for file extension if this encode variable is not set
if(!isset($_REQUEST['encode']))
{
	// use client type prediction
	if(preg_match('/.*(windows-media-player|NSplayer|WMPCE).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		$_REQUEST['encode'] = 'WMV';
	}
	elseif(preg_match('/.*(iTunes).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		$_REQUEST['encode'] = 'MPG';
	}
	elseif(preg_match('/.*(mobile).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		$_REQUEST['encode'] = 'MPG';
	}
	elseif(preg_match('/.*(vlc).*/i', $_SERVER['HTTP_USER_AGENT'], $matches) !== 0)
	{
		$_REQUEST['encode'] = 'MP4';
	}
	elseif(isset($_REQUEST['filename']))
	{
		$_REQUEST['encode'] = strtoupper(getExt($_REQUEST['filename']));
	}
}

// check the id and stuff
if(isset($_REQUEST))
{
	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($_REQUEST, &$count, &$error));
	
	if(count($files) > 0)
	{
		// the ids module will do the replacement of the ids
		$files = db_ids::get(array('cat' => $_REQUEST['cat']), $tmp_count, $tmp_error, $files);
		
		$tmp_request = array();
		$tmp_request['file'] = $files[0]['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($files[0], getIDKeys()), $tmp_request);
	
		// get all the information incase we need to use it
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if($module != $_REQUEST['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count, &$tmp_error));
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
			
		// fix the encode type
		if(db_audio::handles($files[0]['Filepath']))
		{
			if($_REQUEST['encode'] == 'MP4') $_REQUEST['encode'] = 'MP4A';
			elseif($_REQUEST['encode'] == 'MPG') $_REQUEST['encode'] = 'MP3';
			elseif($_REQUEST['encode'] == 'WMV') $_REQUEST['encode'] = 'WMA';
		}
		elseif(db_video::handles($files[0]['Filepath']))
		{
			if($_REQUEST['encode'] == 'MP4A') $_REQUEST['encode'] = 'MP4';
			elseif($_REQUEST['encode'] == 'MP3') $_REQUEST['encode'] = 'MPG';
			elseif($_REQUEST['encode'] == 'WMA') $_REQUEST['encode'] = 'WMV';
		}
		
		// set the file variable
		$_REQUEST['%IF'] = $files[0]['Filepath'];
	}
	else
	{
		header('Content-Type: text/html');
		print 'File does not exist!';
		exit;
	}
	
}
else
{
	header('Content-Type: text/html');
	print 'Must specify a file!';
	exit;
}

// close session so the client can continue browsing the site
if(isset($_SESSION)) session_write_close();

// set the headers
switch(strtoupper($_REQUEST['encode']))
{
	case 'MP4':
		header('Content-Type: video/mp4');
		break;
	case 'MPG':
		header('Content-Type: video/mpg');
		break;
	case 'WMV':
		header('Content-Type: video/x-ms-wmv');
		break;
	case 'MP4A':
		header('Content-Type: audio/mp4');
		break;
	case 'MP3':
		header('Content-Type: audio/mpeg');
		break;
	case 'WMA':
		header('Content-Type: audio/x-ms-wma');
		break;
	default:
		header('Content-Type: audio/mpeg');
		$_REQUEST['encode'] = 'MP3';
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']) || constant($_REQUEST['cat'] . '::INTERNAL') == true)
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

if(!isset($_REQUEST['%IF']) && isset($_REQUEST['id']))
	$_REQUEST['%IF'] = $_REQUEST['id'];

// do the validation for all the input options!
// first filter out all the unwanted request vars
foreach($_REQUEST as $key => $value)
{
	if(!in_array($key, array('encode', 'cat', '%IF', '%VC', '%AC', '%VB', '%AB', '%SR', '%CH', '%MX', '%TO', '%SC', '%FS')))
		unset($_REQUEST[$key]);
}

// here is some presets:
switch(strtoupper($_REQUEST['encode']))
{
	case 'MP4':
		$_REQUEST['%VC'] = 'mp4v';
		$_REQUEST['%AC'] = 'mp3';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'asf';
		$_REQUEST['%SC'] = .5;
		$_REQUEST['%FS'] = 15;
		break;
	case 'MPG':
		$_REQUEST['%VC'] = 'mpgv';
		$_REQUEST['%AC'] = 'mpga';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'ts';
		$_REQUEST['%SC'] = .5;
		$_REQUEST['%FS'] = 0;
		break;
	case 'WMV':
		$_REQUEST['%VC'] = 'WMV2';
		$_REQUEST['%AC'] = 'mp3';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'asf';
		$_REQUEST['%SC'] = .5;
		$_REQUEST['%FS'] = 15;
		break;
	case 'MP4A':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'mp4a';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'ts';
		$_REQUEST['%SC'] = 1;
		break;
	case 'MP3':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'mp3';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'dummy';
		$_REQUEST['%SC'] = 1;
		break;
	case 'WMA':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'wma2';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'asf';
		$_REQUEST['%SC'] = 1;
		break;
}

// validate each individually, these also go to default if there is invalid input! (in this case it uses mpg1 settings)
if(!isset($_REQUEST['%VC']) || !in_array($_REQUEST['%VC'], array('mp4v', 'mp1v', 'WMV2', 'DIV3','dummy')))
	$_REQUEST['%VC'] = 'mp1v';
	
if(!isset($_REQUEST['%AC']) || !in_array($_REQUEST['%AC'], array('mp4a', 'mpga', 'mp3', 'wma2', 'dummy')))
	$_REQUEST['%AC'] = 'mpga';

if(!isset($_REQUEST['%VB']) || !is_numeric($_REQUEST['%VB']))
	$_REQUEST['%VB'] = 512;
	
if(!isset($_REQUEST['%AB']) || !is_numeric($_REQUEST['%AB']))
	$_REQUEST['%AB'] = 64;

if(!isset($_REQUEST['%SR']) || !is_numeric($_REQUEST['%SR']))
	$_REQUEST['%SR'] = 44100;

if(!isset($_REQUEST['%SC']) || !is_numeric($_REQUEST['%SC']))
	$_REQUEST['%SC'] = 1;
	
if(!isset($_REQUEST['%CH']) || !is_numeric($_REQUEST['%CH']))
	$_REQUEST['%CH'] = 2;

if(!isset($_REQUEST['%MX']) || !in_array($_REQUEST['%MX'], array('ts', 'ps', 'mpeg1', 'asf', 'mp4', 'ogg', 'dummy')))
	$_REQUEST['%MX'] = 'mpeg1';
	
if(!isset($_REQUEST['%TO']) || !is_numeric($_REQUEST['%TO']))
	$_REQUEST['%TO'] = 0;
	
if(!isset($_REQUEST['%FS']) || !is_numeric($_REQUEST['%FS']))
	$_REQUEST['%FS'] = 0;

// make up some header to takes the length of the media into consideration
if(isset($files[0]['Length']))
{
	$files[0]['Filesize'] = ($files[0]['Length'] * ($_REQUEST['%VB'] * 128 + $_REQUEST['%AB'] * 128)) + 1024;
}
if(isset($files[0]['Filesize']))
{				
	// check for range request
	if(isset($_SERVER['HTTP_RANGE']))
	{
		list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

		if ($size_unit == 'bytes')
		{
			// multiple ranges could be specified at the same time, but for simplicity only serve the first range
			// http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
			if(strpos($range_orig, ',') !== false)
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
			else
				$range = $range_orig;
		}
		else
		{
			$range = '-';
		}
	}
	else
	{
		$range = '-';
	}
	
	// figure out download piece from range (if set)
	list($seek_start, $seek_end) = explode('-', $range, 2);

	// set start and end based on range (if set), else set defaults
	// also check for invalid ranges.
	$seek_end = (empty($seek_end)) ? ($files[0]['Filesize'] - 1) : min(abs(intval($seek_end)),($files[0]['Filesize'] - 1));
	//$seek_end = $file['Filesize'] - 1;
	$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
	
	// Only send partial content header if downloading a piece of the file (IE workaround)
	if ($seek_start > 0 || $seek_end < ($files[0]['Filesize'] - 1))
	{
		//header('HTTP/1.1 206 Partial Content');
	}

	//header('Accept-Ranges: bytes');
	//header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $files[0]['Filesize']);

	//headers for IE Bugs (is this necessary?)
	//header("Cache-Control: no-cache, must-revalidate");  
	//header("Pragma: public");

	//header('Content-Length: ' . ($seek_end - $seek_start + 1));
}

// replace the argument string with the contents of $_REQUEST
//  without validation this is VERY DANGEROUS!
$cmd = basename(ENCODE) . ' ' . str_replace(array_keys($_REQUEST), array_values($_REQUEST), ENCODE_ARGS);

$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
);

// start process
$process = proc_open($cmd, $descriptorspec, $pipes, dirname(ENCODE), NULL); //array('binary_pipes' => true, 'bypass_shell' => true));

stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);

$fp = call_user_func_array($_REQUEST['cat'] . '::out', array($_REQUEST['%IF']));
//$fp = fopen($_REQUEST['%IF'], 'rb');
$php_out = fopen('php://output', 'wb');

// if %IF is not in the arguments, it is reading from stdin so use pipe
// output file
if(is_resource($process) && is_resource($fp))
{
	// don't use the file at all if the %IF field exists in the ARGS sections
	//   the reason for this is because it won't be reading from STDIN
	$file_closed = (strpos(ENCODE_ARGS, '%IF') !== false);
	$read_count = 0;
	$write_count = 0;
	$in_buffer = '';
	$out_buffer = '';
	while(true)
	{
		// if the connection was interrupted then stop looping
		if(connection_status()!=0)
		{
			fclose($fp);
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($php_out);
			proc_terminate($process);
			break;
		}
		
		// if the file is not already closed, then close the file when it hits eof
		if(!$file_closed && feof($fp))
		{
			$file_closed = true;
			fclose($fp);
			fclose($pipes[0]);
		}
		
		// if the pipe is eof then we are finished
		if(feof($pipes[1]))
		{
			// write out what is left
			if(isset($length))
			{
				if($read_count < $length)
					fwrite($php_out, sprintf('[%0' . ($length - $read_count) . 's]', 0));
			}
			fclose($pipes[1]);
			fclose($php_out);
			break;
		}
		
		// set up the pipes to be checked
		$read = array($pipes[1]);
		$write = (!$file_closed)?array($pipes[0]):array();
		$except = NULL;

		// select the pipes that are available for reading and writing
		stream_select($read, $write, $except, NULL);
		
		// if we can read then read more and send it out to php
		if(in_array($pipes[1], $read))
		{
			$count = fwrite($php_out, fread($pipes[1], BUFFER_SIZE));
			$read_count += $count;
		}
		
		// if we can write then write more from the input stream
		if(!$file_closed && in_array($pipes[0], $write))
		{
			if(strlen($in_buffer) == 0)
			{
				$in_buffer = fread($fp, BUFFER_SIZE);
			}
			$count = fwrite($pipes[0], $in_buffer);
			$in_buffer = substr($in_buffer, $count);
			$write_count += $count;
		}
	}
	
	$status = proc_get_status($process);
	kill9('vlc', $status['pid']);
	
	$return_value = proc_close($process);
}

?>