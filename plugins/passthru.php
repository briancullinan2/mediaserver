<?php

header('Content-Type: video/x-ms-wmv');
//header('Content-Length: 1824164');
//header('Content-Disposition: attachment; filename="file.wmv');

//$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=mp4v,acodec=mp4a,vb=1024,ab=256,channels=2,deinterlace,audio-sync}:std{mux=ts,access=file,dst=-}\' vlc://quit';
//$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=mp1v,acodec=mpga,vb=512,ab=64,channels=2,deinterlace,audio-sync}:std{mux=mpeg1,access=file,dst=-}\' vlc://quit';
$cmd = '/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/strip-poker.wmv :sout=\'#transcode{vcodec=WMV2,acodec=mp3,vb=1024,ab=256,channels=2,deinterlace,audio-sync}:std{mux=asf,access=file,dst=-}\' vlc://quit';

$handle = popen($cmd, 'r');
while($read = fread($handle, 1024))
{
	echo $read;
}
pclose($handle);
//passthru($cmd);

?>