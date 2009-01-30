<?php
  //header('Content-Type: video/x-ms-wmv');
  
  //$descriptors = array(1 => array("pipe", "w"));
  
  $vlc = popen('/bin/bash /var/www/mediaserver/plugins/encode "/home/share/Videos/test.wmv"', 'rb'); //, $descriptors, $pipes);
  
  while (!feof($vlc) && (connection_aborted() == 0))
  {
    $buf = fread($vlc, 1024);
    if ($buf == FALSE) break;
    //print $buf;
    if (connection_aborted())
    {
      //proc_terminate($vlc);
      //break;
    }
  }
  
  //fclose($vlc);
@pclose($vlc);
?>