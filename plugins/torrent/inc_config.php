<?
// 1
// 2
// 3
$username = "giveme"; //Username for the interface
$password = "torrent"; //Password for the interface
$fancy_filter = "/(-o_|_o-|^o-|^o_|-O_|_O-|Demonoid.com|.TPB$|^_PT_|-|_)/"; //regex for filtering crap from the name /(a|b|c)/
$download_path = "/tmp/"; //Where to download the files, with trailing slash
$ctorrent_path = "/usr/local/bin/ctorrent"; //full path and command for ctorrent
$ratio = "1.2"; //Seed until this ratio has been reached
$cache_size = "1"; //Cache size to use, in Mb
$bandwidth_down = "200"; //Download bandwidth in KB/s
$bandwidth_up = "10"; //Upload bandwidth in KB/s
$maxport = "2706"; //Default 2706 -> 2106. See help for more info.
$priority = "0"; //Priority for the nice command. range: 1-19, 0 disables nice 
$refresh = "20"; //How often to refresh the status, in seconds
?>
