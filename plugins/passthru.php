<?php
set_time_limit(0);
ignore_user_abort(1);

if($_REQUEST['encode'] == 'MP4')
{
	header('Content-Type: video/mp4');
}
elseif($_REQUEST['encode'] == 'MPG')
{
	header('Content-Type: video/mpg');
}
elseif($_REQUEST['encode'] == 'WMV')
{
	//header('Content-Type: video/x-ms-wmv');
}

require_once dirname(__FILE__) . '/../include/common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// add category
if(!isset($_REQUEST['cat']))
{
	$_REQUEST['cat'] = 'db_file';
}

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
	
		$file = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . $files[0]['Filepath'] . '"'));
		if(count($file) > 0)
		{
			$files[0] = array_merge($file[0], $files[0]);
		}
		
		if($_REQUEST['encode'] == 'MP4')
		{
			$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=512,ab=64,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
		}
		elseif($_REQUEST['encode'] == 'MPG')
		{
			$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=512,ab=64,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
		}
		elseif($_REQUEST['encode'] == 'WMV')
		{
			$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=WMV2,acodec=mp3,vb=512,ab=64,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-}\' vlc://quit';
		}

		$descriptorspec = array(
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		);

		if(isset($_SESSION['play']) && $_SESSION['play']['resource'] && is_resource($_SESSION['play']['resource']))
		{
			//$process = $_SESSION['play']['resource'];
			//$pipes[1] = $_SESSION['play']['pipe'];
		}
		else
		{
			//$_SESSION['play']['resource'] = $process;
			//$_SESSION['play']['pipe'] = $pipes[1];
			//$_SESSION['play']['id'] = $files[0]['id'];
		}
		$process = proc_open($cmd, $descriptorspec, $pipes, TMP_DIR, NULL);

		if(is_resource($process)) {
			
			while(!feof($pipes[1]))
			{
				//print fread($pipes[1], 1024);
				if(connection_aborted())
				{
					proc_terminate($process);
					break;
				}
			}
			fclose($pipes[1]);
			
			$status = proc_get_status($process);
			$fp = fopen('/tmp/error_output.txt', 'w');
			fwrite($fp, $status['pid']);
			fclose($fp);
			shell_exec('kill ' . $status['pid']+1);
			
			$return_value = proc_close($process);
		}
	}
}


?>