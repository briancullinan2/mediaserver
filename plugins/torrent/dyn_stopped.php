<?include "inc_security.php";?>
<?include "inc_config.php";?>
<?php
$pos=-500;
$fh = opendir($download_path."/"); //Verzeichnis
$torrents = array();
$stats = array();
$pids = array();
$notload = array();

while (true == ($file = readdir($fh)))
{				
        if (substr(strtolower($file), -3)=="pid")
        {    
            $pids[] = $file;
        }    

        if (substr(strtolower($file), -4)=="stat.tail")
        {    
            $stats[] = $file;
        }    
        
        if (substr(strtolower($file), -7)=="torrent")
        {        
            $torrents[] = $file;
        }    
}

?>
<table>
<tr><th>Torrent</th><th style="text-align:right;">Completed</th><th>&nbsp;</th></tr>
     <?php
     for($j=0;$j<count($torrents);$j++)    
	{					
		$count=0;
     		for($i=0;$i<count($pids);$i++)    
			{ 						
				if (substr($torrents[$j],0,strlen($torrents[$j])-8) == substr($pids[$i],0,strlen($pids[$i])-4)) 
					{							
						$count=$count +1;
					}							
			}					
			if ($count == 0) 
			{	
				$torrentName = substr($torrents[$j],0,strlen($torrents[$j])-8);
 				$fancyTorrentName = preg_replace( $fancy_filter, " ",  $torrentName);
				$completed = "0";
	     		for($y=0;$y<count($stats);$y++)    
				{ 						
					if (substr($torrents[$j],0,strlen($torrents[$j])-8) == substr($stats[$y],0,strlen($stats[$y])-5)) 
					{
				  		$handle = fopen ($download_path."/".$stats[$y], "r");
  						fseek($handle, $pos, SEEK_END);  
				  		$text=@fread($handle,filesize($download_path."/".$stats[$y]));  
						if (strrchr($text, 13)) {
					  		$text = substr (strrchr ($text, 13), 1 );
						}

 							      // /.*([0-9]+)\/([0-9]+)/\([0-9]+) \[([0-9]+)\/([0-9]+)\/([0-9]+)\] (.+?),(.+?)\| (.+?),(.+?) \|.*/i
				  		@preg_match_all("/.*([0-9]+)\/([0-9]+)\/([0-9]+) \[([0-9]+)\/([0-9]+)\/([0-9]+)\] (.+?),(.+?) \| (.+?),(.+?) \|/i", $text, $arrrr, PREG_PATTERN_ORDER);
						$completed=@round($arrrr[4][0]*100/$arrrr[5][0]);
					}							
				}					
				echo "<tr><td><div style=\"width:400px;\">". $fancyTorrentName .
				"</div></td><td style=\"text-align:right;\">". $completed . "%</td><td><a title=\"Start\" href=\"dyn_process.php?start=".$torrentName."\" onclick=\"return getHtml('dyn_process.php?start=$torrentName&dyn=true','message');\"><img src=\"images/start.png\" onclick=\"this.src='images/wait.gif'\" /></a>
				<a title=\"Löschen\" href=\"dyn_process.php?delete=".$torrentName."\" onclick=\"if( confirm('Delete?') ){return getHtml('dyn_process.php?delete=$torrentName&dyn=true','message'); } else {return false;}\"><img src=\"images/delete.png\"  onclick=\"this.src='images/wait.gif'\" /></a></td></tr>";
			}
    	}
    	?>
</table>

