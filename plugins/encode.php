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
		
		switch($_REQUEST['encode'])
		{
			case 'MP4':
				$cmd = basename(ENCODE) . ' -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
				break;
			case 'MPG':
				$cmd = basename(ENCODE) . ' -I dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
				break;
			case 'WMV':
				$cmd = basename(ENCODE) . ' -v "' . $files[0]['Filepath'] . '" :sout=#transcode{vcodec=WMV2,acodec=mp3,vb=512,ab=64,samplerate=44100,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-} vlc://quit';
				break;
			case 'MP4A':
				$cmd = basename(ENCODE) . ' -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=mp4a,ab=160,samplerate=44100,channels=2}:std{mux=mp4,access=file,dst=-}\' vlc://quit';
				break;
			case 'MP3':
				$cmd = basename(ENCODE) . ' -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=mp3,ab=160,samplerate=44100,channels=2}:std{mux=dummy,access=file,dst=-}\' vlc://quit';
				break;
			case 'WMA':
				$cmd = basename(ENCODE) . ' -I dummy -v --no-video "' . $files[0]['Filepath'] . '" :sout=\'#transcode{acodec=wma2,ab=160,samplerate=44100,channels=2}:std{mux=asf,access=file,dst=-}\' vlc://quit';
				break;
		}

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
	}
}

?>