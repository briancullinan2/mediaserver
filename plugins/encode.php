<?php
  //header('Content-Type: video/x-ms-wmv');
  
  $descriptors = array(1 => array("pipe", "w"));
  
  $vlc = proc_open('/bin/bash /var/www/mediaserver/plugins/encode "/home/share/Videos/test.wmv"', $descriptors, $pipes);
  
  while (TRUE)
  {
    $buf = fread($pipes[1], 1024);
    if ($buf == FALSE) break;
    //print $buf;
    if (connection_aborted())
    {
      proc_terminate($vlc);
      break;
    }
  }
  
  fclose($pipes[1]);
$return_value = proc_close($vlc);
?>