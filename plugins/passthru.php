<?php
set_time_limit(0);
ignore_user_abort(1);

switch($_REQUEST['encode'])
{
	case 'MP4':
		header('Content-Type: video/mp4');
		break;
	case 'MPG':
		header('Content-Type: video/mpg');
		break;
	case 'WMV':
		//header('Content-Type: video/x-ms-wmv');
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
		
		switch($_REQUEST['encode'])
		{
			case 'MP4':
				$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
				break;
			case 'MPG':
				$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
				break;
			case 'WMV':
				$cmd = '/usr/bin/vlc -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=WMV2,acodec=mp3,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-}\' vlc://quit';
				break;
			case 'MP4A':
				$cmd = '/usr/bin/vlc -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=mp4a,ab=160,samplerate=44100,channels=2}:std{mux=mp4,access=file,dst=-}\' vlc://quit';
				break;
			case 'MP3':
				$cmd = '/usr/bin/vlc -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=mp3,ab=160,samplerate=44100,channels=2}:std{mux=dummy,access=file,dst=-}\' vlc://quit';
				break;
			case 'WMA':
				$cmd = '/usr/bin/vlc -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=wma2,ab=160,samplerate=44100,channels=2}:std{mux=asf,access=file,dst=-}\' vlc://quit';
				break;
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
		$process = popen($cmd, 'rb'); //$descriptorspec, $pipes, TMP_DIR, NULL);

		if(is_resource($process)) {
			
			while(!feof($process) && (connection_aborted() == 0))
			{
				//print fread($pipes[1], 2048);
			}
			//fclose($pipes[1]);
			//proc_terminate($process);
			
			$return_value = pclose($process);
		}
	}
}


?>