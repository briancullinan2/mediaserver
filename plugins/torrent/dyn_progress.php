<?include "inc_security.php";?>
<?include "inc_config.php";?>
<?php
$pos=-500;
$fh = opendir($download_path."/");
$stats = array();
$notload = array();
include "inc_functions.php";
while (true == ($file = readdir($fh)))
{				
        if (substr(strtolower($file), -3)=="pid")
        {    

        	if( isRunning(substr($file,0,strlen($file)-4)) ){
        	    $filenames=array();
        	    $filenames[] = substr( $file,0,strlen($file)-3 );
	            foreach ($filenames as $val){
	            $fsize=exec("ls -s ".$download_path.$val."stat");
	            $fsize=explode(" ",$fsize);
	            if ($fsize[0]>2097152){
	            exec("echo >".$download_path.$val."stat");
	            sleep(8); // give time to ctorrent to re-populate .stat file
	            }
	            $cmdtail="/usr/bin/tail ". $download_path . $val . "stat > ". $download_path . $val ."stat.tail";
	            exec($cmdtail);
	            }
	            $stats[] = substr( $file,0,strlen($file)-3 ) ."stat.tail";
	        }
	        else {
	        	stop(substr($file,0,strlen($file)-4));
	        }
        }        }
?>
<table>
<tr><th>Torrent</th><th>Compl.</th><th>s/l/p</th><th>dl</th><th>ul</th><th>&nbsp;</th></tr>
<?
      	for($i=0;$i<count($stats);$i++)    
	{ 
  		$handle = fopen ($download_path."/".$stats[$i], "r");
  		fseek($handle, $pos, SEEK_END);  
  		$text=@fread($handle,filesize($download_path."/".$stats[$i]));  
		if (strrchr($text, 13)) {
	  		$text = substr (strrchr ($text, 13), 1 );
		}
//  		$last = substr (strrchr ($text, 13), 1 );
//		$file = $download_path."/".$stats[$i];
//		$last = `tail -1 $file`;  //faster than php shit

//		die($last);
		// \ 17/6/82 [181/255/255] 11MB,2MB | 35,0K/s | 25,0K E:0,1                       
		// /.*([0-9]+)\/([0-9]+)/\([0-9]+) \[([0-9]+)\/([0-9]+)\/([0-9]+)\] (.+?),(.+?)\| (.+?),(.+?) \|.*/i
  		@preg_match_all("/.* ([0-9]+)\/([0-9]+)\/([0-9]+) \[([0-9]+)\/([0-9]+)\/([0-9]+)\] (.+?),(.+?) \| (.+?),(.+?) \|.*/i", $text, $arrrr, PREG_PATTERN_ORDER);
//		print_r($arrrr);
		$torrentName=substr($stats[$i],0,strlen($stats[$i])-10);
		$fancyTorrentName = preg_replace( $fancy_filter, " ",  $torrentName);

  		echo "<tr><td><div>".$fancyTorrentName ."</div></td><td style=\"text-align:center;\">"
  		. @round($arrrr[4][0]*100/$arrrr[5][0]). "%</td><td>"
  		.$arrrr[1][0]."/". $arrrr[2][0] . "/" . $arrrr[3][0]."</td><td>". $arrrr[9][0]."K/s</td><td>".$arrrr[10][0] 
  		."</td><td><a title=\"Stop\" href=\"dyn_process.php?stop=$torrentName\" onclick=\"return getHtml('dyn_process.php?stop=$torrentName&dyn=true','message');\"><img src=\"images/stop.png\"  onclick=\"this.src='images/wait.gif'\" /></a></td></tr>";  	
	}
      ?>
</table>

