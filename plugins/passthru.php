<?php

header('Content-Type: video/x-ms-wmv');
header('Content-Length: 0');
//header('Content-Disposition: attachment; filename="file.wmv');

$handle = popen('/usr/bin/vlc --intf dummy -v /home/share/Videos/Other/Funny/Restaurant_R_Full_Frame.mpeg :sout=\'#transcode{vcodec=WMV2,vb=64,acodec=mp3,ab=16,channels=2}:std{mux=asf,access=file,dst=-}\' vlc://quit', 'r');
while($read = fread($handle, 1024))
{
	echo $read;
}
pclose($handle);
//passthru($cmd);

?>