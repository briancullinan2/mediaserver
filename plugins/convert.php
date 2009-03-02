<?php
set_time_limit(0);
ignore_user_abort(1);

// set the header first thing so browser doesn't stall or get tired of waiting for the process to start
switch($_REQUEST['convert'])
{
	case 'JPG':
		header('Content-Type: image/jpg');
		break;
	case 'GIF':
		header('Content-Type: image/gif');
		break;
	case 'PNG':
		header('Content-Type: image/png');
		break;
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
if(USE_DATABASE) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $database = NULL;

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = USE_DATABASE?'db_image':'fs_image';

if(!isset($_REQUEST['%IF']) && isset($_REQUEST['id']))
	$_REQUEST['%IF'] = $_REQUEST['id'];

// check the id and stuff
if(isset($_REQUEST['%IF']))
{
	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($database, array('id' => $_REQUEST['id']), &$count, &$error));
	
	if(count($files) > 0)
	{
		// get all the information incase we need to use it
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if($module != $_REQUEST['cat'] && call_user_func_array($module . '::handles', array($files[0]['Filepath'])))
			{
				$return = call_user_func_array($module . '::get', array($database, array('file' => $files[0]['Filepath']), &$tmp_count, &$tmp_error));
				if(isset($return[0])) $files[0] = array_merge($return[0], $files[0]);
			}
		}
		
		if(!isset($_REQUEST['%TH']) && isset($files[0]['Height']))
			$_REQUEST['%TH'] = $files[0]['Height'];
		
		if(!isset($_REQUEST['%TW']) && isset($files[0]['Width']))
			$_REQUEST['%TW'] = $files[0]['Width']
		
		$_REQUEST['%IF'] = $files[0]['Filepath'];
	}
}
else
{
	exit;
}

// do the validation for all the input options!
// first filter out all the unwanted request vars
foreach($_REQUEST as $key => $value)
{
	if(!in_array($key, array('convert', '%IF', '%FM')))
		unset($_REQUEST[$key]);
}

// here is some presets:
switch($_REQUEST['convert'])
{
	case 'JPG':
		$_REQUEST['%FM'] = 'jpeg';
		break;
	case 'GIF':
		$_REQUEST['%FM'] = 'gif';
		break;
	case 'PNG':
		$_REQUEST['%FM'] = 'png';
		break;
}

// validate each individually, these also go to default if there is invalid input! (in this case it uses jpeg settings)
if(!in_array($_REQUEST['%FM'], array('jpeg', 'gif', 'png')))
	$_REQUEST['%FM'] = 'jpeg';
	
if(!isset($_REQUEST['%TH']) || !is_numeric($_REQUEST['%TH']))
	$_REQUEST['%TH'] = 512;
	
if(!isset($_REQUEST['%TW']) || !is_numeric($_REQUEST['%TW']))
	$_REQUEST['%TW'] = 512;
	

// replace the argument string with the contents of $_REQUEST
//  without validation this is VERY DANGEROUS!
$cmd = basename(CONVERT) . ' ' . str_replace(array_keys($_REQUEST), array_values($_REQUEST), CONVERT_ARGS);

// run process and output binary from pipe
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
);

$process = proc_open($cmd, $descriptorspec, $pipes, dirname(CONVERT), NULL, array('binary_pipes' => true));

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
	kill9('convert', $status['pid']);
	
	$return_value = proc_close($process);
}

?>