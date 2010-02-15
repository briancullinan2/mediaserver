<?php
set_time_limit(0);
ignore_user_abort(1);

define('CONVERT_PRIV', 				1);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < CONVERT_PRIV )
{
	// redirect to login page
	header('Location: ' . HTML_ROOT . 'plugins/login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . CONVERT_PRIV);
	
	exit();
}

// if none of the following is defined, tokenize and search
if(!isset($_REQUEST['id']) && !isset($_REQUEST['item']) && !isset($_REQUEST['on']) && !isset($_REQUEST['file']) && !isset($_REQUEST['search']))
{
	$request_tokens = tokenize(join('&', $_REQUEST));
	$_REQUEST['search'] = join(' ', $request_tokens['All']);
}

// set the header first thing so browser doesn't stall or get tired of waiting for the process to start
switch(strtoupper($_REQUEST['convert']))
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
	default:
		header('Content-Type: image/jpg');
		$_REQUEST['convert'] = 'header';
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']) || constant($_REQUEST['cat'] . '::INTERNAL') == true)
	$_REQUEST['cat'] = USE_DATABASE?'db_image':'fs_image';

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
			
		if(!isset($_REQUEST['%TH']) && isset($files[0]['Height']))
			$_REQUEST['%TH'] = $files[0]['Height'];
		
		if(!isset($_REQUEST['%TW']) && isset($files[0]['Width']))
			$_REQUEST['%TW'] = $files[0]['Width'];
		
		$_REQUEST['%IF'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $files[0]['Filepath']);
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

// do the validation for all the input options!
// first filter out all the unwanted request vars
foreach($_REQUEST as $key => $value)
{
	if(!in_array($key, array('convert', '%IF', '%FM', '%TW', '%TH')))
		unset($_REQUEST[$key]);
}

// here is some presets:
switch(strtoupper($_REQUEST['convert']))
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