<?php
set_time_limit(0);
ignore_user_abort(1);

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
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// add category and validate!
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
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
	
		switch($_REQUEST['convert'])
		{
			case 'JPG':
				$cmd = basename(CONVERT) . ' "' . $files[0]['Filepath'] . '" jpeg:-';
				break;
			case 'GIF':
				$cmd = basename(CONVERT) . ' "' . $files[0]['Filepath'] . '" gif:-';
				break;
			case 'PNG':
				$cmd = basename(CONVERT) . ' "' . $files[0]['Filepath'] . '" png:-';
				break;
		}

		$descriptorspec = array(
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		);

		$process = proc_open($cmd, $descriptorspec, $pipes, dirname(CONVERT), NULL, array('binary_pipes' => true, 'bypass_shell' => "1"));
		
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
	}
}

?>