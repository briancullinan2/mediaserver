<?php
set_time_limit(0);
ignore_user_abort(1);

// set the header first thing so browser doesn't stall or get tired of waiting for the process to start
switch($_REQUEST['encode'])
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
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
if(USE_DATABASE) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $database = NULL;

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

if(!isset($_REQUEST['%IF']) && isset($_REQUEST['id']))
	$_REQUEST['%IF'] = $_REQUEST['id'];

// check the id and stuff
if(isset($_REQUEST['%IF']))
{
	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($database, array('id' => $_REQUEST['id']), &$count, &$error));
	
	if(count($files) > 0)
	{
		// set the file variable
		$_REQUEST['%IF'] = $files[0]['Filepath'];
		
		
		// make sure the length of the file is known
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if($module != $_REQUEST['cat'] && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($module . '::get', array($database, array('file' => $files[0]['Filepath']), &$tmp_count, &$tmp_error));
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
		
		ob_start();
		
		if(isset($_SESSION)) session_write_close();
		
		// set up some general headers
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: ' . $files[0]['Filemime']);
		header('Content-Disposition: attachment; filename="' . $files[0]['Filename'] . '"');
		
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
		//$seek_end = $files[0]['Filesize'] - 1;
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
		
		// Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($files[0]['Filesize'] - 1))
		{
			header('HTTP/1.1 206 Partial Content');
		}

		header('Accept-Ranges: bytes');
		header('Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $files[0]['Filesize']);
	
		//headers for IE Bugs (is this necessary?)
		//header("Cache-Control: cache, must-revalidate");  
		//header("Pragma: public");
	
		header('Content-Length: ' . ($seek_end - $seek_start + 1));
		
		// get the time offset but only if the time variable exists
		if(isset($files[0]['Length']))
		{
			// calculate where to start
			$_REQUEST['%TO'] = $files[0]['Length'] * ($seek_start / $files[0]['Filesize']);
		}
	}
	else
	{
		exit;
	}
	print_r($_SERVER);
	
	$buffer = ob_get_contents();
	ob_end_clean();
	
	$tp = fopen('/tmp/test.txt', 'a');
	fwrite($tp, $seek_start . ' - ' . $seek_end . (isset($_SERVER['HTTP_RANGE'])?(' - ' . $_SERVER['HTTP_RANGE']):'') . "\n");
	fwrite($tp, $buffer);
	fclose($tp);
	
}
else
{
	exit;
}

// do the validation for all the input options!
// first filter out all the unwanted request vars
foreach($_REQUEST as $key => $value)
{
	if(!in_array($key, array('encode', '%IF', '%VC', '%AC', '%VB', '%AB', '%SR', '%CH', '%MX', '%TO')))
		unset($_REQUEST[$key]);
}

// here is some presets:
switch($_REQUEST['encode'])
{
	case 'MP4':
		$_REQUEST['%VC'] = 'mp4v';
		$_REQUEST['%AC'] = 'mp4a';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 64;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'ts';
		break;
	case 'MPG':
		$_REQUEST['%VC'] = 'mp1v';
		$_REQUEST['%AC'] = 'mpga';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 64;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'mpeg1';
		break;
	case 'WMV':
		$_REQUEST['%VC'] = 'WMV2';
		$_REQUEST['%AC'] = 'mp3';
		$_REQUEST['%VB'] = 512;
		$_REQUEST['%AB'] = 64;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'asf';
		break;
	case 'MP4A':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'mp4a';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'ts';
		break;
	case 'MP3':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'mp3';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'dummy';
		break;
	case 'WMA':
		$_REQUEST['%VC'] = 'dummy';
		$_REQUEST['%AC'] = 'wma2';
		$_REQUEST['%VB'] = 0;
		$_REQUEST['%AB'] = 160;
		$_REQUEST['%SR'] = 44100;
		$_REQUEST['%CH'] = 2;
		$_REQUEST['%MX'] = 'asf';
		break;
}

// validate each individually, these also go to default if there is invalid input! (in this case it uses mpg1 settings)
if(!in_array($_REQUEST['%VC'], array('mp4v', 'mp1v', 'WMV2', 'dummy')))
	$_REQUEST['%VC'] = 'mp1v';
	
if(!in_array($_REQUEST['%AC'], array('mp4a', 'mpga', 'mp3', 'wma2', 'dummy')))
	$_REQUEST['%AC'] = 'mpga';

if(!is_numeric($_REQUEST['%VB']))
	$_REQUEST['%VB'] = 512;
	
if(!is_numeric($_REQUEST['%AB']))
	$_REQUEST['%AB'] = 64;

if(!is_numeric($_REQUEST['%SR']))
	$_REQUEST['%SR'] = 44100;
	
if(!is_numeric($_REQUEST['%CH']))
	$_REQUEST['%CH'] = 2;

if(!in_array($_REQUEST['%MX'], array('ts', 'ps', 'mpeg1', 'asf', 'mp4', 'dummy')))
	$_REQUEST['%MX'] = 'mpeg1';
	
if(!isset($_REQUEST['%TO']))
	$_REQUEST['%TO'] = 0;

// replace the argument string with the contents of $_REQUEST
//  without validation this is VERY DANGEROUS!
$cmd = basename(ENCODE) . ' ' . str_replace(array_keys($_REQUEST), array_values($_REQUEST), ENCODE_ARGS);

// run process and output binary from pipe
$descriptorspec = array(
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
);

$process = proc_open($cmd, $descriptorspec, $pipes, dirname(ENCODE), NULL); //array('binary_pipes' => true, 'bypass_shell' => true));

// output file
if(is_resource($process))
{
	while(!feof($pipes[1]))
	{
		if(connection_status()!=0)
		{
			proc_terminate($process);
			break;
		}
		print fread($pipes[1], BUFFER_SIZE);
		flush();
	}
	fclose($pipes[1]);
	
	$status = proc_get_status($process);
	kill9('vlc', $status['pid']);
	
	$return_value = proc_close($process);
}

?>