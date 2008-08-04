<?include "inc_security.php";?>
<?php
function start($torrent){
		include "inc_config.php";
		$stat = escapeshellarg($download_path . $torrent .".stat");
		$pid = escapeshellarg($download_path . $torrent .".pid");
		$torrentfull = escapeshellarg($download_path . $torrent .".torrent");
		$command = $ctorrent_path
			." -E " . $ratio
			." -C " . $cache_size
			." -p " . $maxport
			." -D " . $bandwidth_down
			." -U " . $bandwidth_up
			
			." " . $torrentfull
			." > " . $stat
			;

       if(isset($priority)&&$priority!="0")
           shell_exec("cd $download_path; nice -n $priority $command & echo $! > " . $pid);
       else
           shell_exec("cd $download_path; $command & echo $! > " . $pid);

		$endresult =  "Starting torrent (cd $download_path; $command & echo $! > " . $pid . ")" . preg_replace( $fancy_filter, " ", $torrent);

	return $endresult;
}

function delete($torrent){
	include "inc_config.php";
	unlink($download_path."/".$torrent.".torrent");	
	@unlink($download_path."/". $torrent .".stat");
	$endresult =  "Deleting Torrent ".preg_replace( $fancy_filter, " ", $torrent);

	return $endresult;
}


function stop($torrent) {
	include "inc_config.php";
	$stop = "kill `cat ". escapeshellarg( $download_path."/". $torrent .".pid" ) ."`";
	system ($stop);
	unlink($download_path."/". $torrent .".pid");
	$endresult =  "Stopping torrent ". preg_replace( $fancy_filter, " ", $torrent);
	return $endresult;
}

function isRunning($torrent){
	include "inc_config.php";
	$cmd = "cat ". escapeshellarg( $download_path."/". $torrent .".pid" );
        exec("ps", $ProcessState);
	exec($cmd, $PID);
	
	foreach($ProcessState as $Process) {
                if(strstr($Process, $PID[0]) && strstr($Process, "ctorrent")) {
			return true;
		}
	}
	return false;	
}
?>