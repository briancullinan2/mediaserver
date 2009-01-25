<?php
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
	header('Content-Type: video/x-ms-wmv');
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
			$cmd = '/usr/bin/vlc --intf dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=300,ab=64,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
		}
		elseif($_REQUEST['encode'] == 'MPG')
		{
			$cmd = '/usr/bin/vlc --intf dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=300,ab=64,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
		}
		elseif($_REQUEST['encode'] == 'WMV')
		{
			$cmd = '/usr/bin/vlc --intf dummy -v "' . $files[0]['Filepath'] . '" :sout=\'#transcode{vcodec=WMV2,acodec=mp3,vb=300,ab=64,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-}\' vlc://quit';
		}

//header('Content-Length: 1824164');
//header('Content-Disposition: attachment; filename="file.wmv');

//$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=512,ab=96,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
//$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=512,ab=96,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
//$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=WMV2,acodec=mp3,vb=512,ab=96,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-}\' vlc://quit';

		//passthru($cmd);

		$handle = popen($cmd, 'r');
		while($read = fread($handle, 1024))
		{
			echo $read;
		}
		pclose($handle);
		
	}
}

?>