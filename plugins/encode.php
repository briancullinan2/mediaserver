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
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

if(!isset($_REQUEST['%IF']) && isset($_REQUEST['id']))
	$_REQUEST['%IF'] = $_REQUEST['id'];

// check the id and stuff
if(isset($_REQUEST['%IF']) && is_numeric($_REQUEST['%IF']))
{
	// get the file path from the database
	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql,
		array(
			'WHERE' => 'id = ' . $_REQUEST['id'],
		)
	);
	
	if(count($files) > 0)
	{
	
		$file = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . addslashes($files[0]['Filepath']) . '"'));
		if(count($file) > 0)
		{
			$files[0] = array_merge($file[0], $files[0]);
		}
	}
	
	$_REQUEST['%IF'] = $files[0]['Filepath'];
}
else
{
	exit;
}

// do the validation for all the input options!
// first filter out all the unwanted request vars
foreach($_REQUEST as $key => $value)
{
	if(!in_array($key, array('encode', '%IF', '%VC', '%AC', '%VB', '%AB', '%SR', '%CH', '%MX')))
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
		$_REQUEST['%MX'] = 'mp4';
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

// replace the argument string with the contents of $_REQUEST
//  without validation this is VERY DANGEROUS!
$cmd = basename(ENCODE) . ' ' . str_replace(array_keys($_REQUEST), array_values($_REQUEST), ENCODE_ARGS);

// run process and output binary from pipe
$descriptorspec = array(
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
);

$process = proc_open($cmd, $descriptorspec, $pipes, dirname(ENCODE), NULL, array('binary_pipes' => true, 'bypass_shell' => true));

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