<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<title>PHPBTTracker sanity checker</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<h1>Sanity checker</h1>
<?php


error_reporting(E_ALL);
//header("Content-Type: text/plain");

require_once("config.php");
require_once("funcsv2.php");

$summaryupdate = array();

// Non-persistant: we lock tables!
$db = mysql_connect($dbhost, $dbuser, $dbpass) or die("<p class=\"error\">Tracker error: can't connect to database - ".mysql_error() . "</p>");
mysql_select_db($database) or die("<p class=\"error\">Tracker error: can't open database $database - ".mysql_error() . "</p>");

if (isset($_GET["nolock"]))
	$locking = false;
else
	$locking = true;

// Assumes success
if ($locking)
	quickQuery("LOCK TABLES summary WRITE, namemap READ");

?>
<table class="torrentlist" cellspacing="1">
<!-- Column Headers -->
<tr>
	<th>Name/Info Hash</th>
	<th>Leechers</th>
	<th>Seeders</th>
	<th>Bytes Transfered</th>
	<th>Stale clients</th>
	<th>Peer Cache</th>
</tr>
<tr>
	<td colspan="5" class="nodata"></td>
</tr>
<?php

$results = mysql_query("SELECT summary.info_hash, seeds, leechers, dlbytes, namemap.filename FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash");

$i = 0;

while ($row = mysql_fetch_row($results))
{
	$writeout = "row" . $i % 2;
	list($hash, $seeders, $leechers, $bytes, $filename) = $row;
	if ($locking)
	{
		if ($GLOBALS["peercaching"])
			quickQuery("LOCK TABLES x$hash WRITE, y$hash WRITE, summary WRITE");
		else
			quickQuery("LOCK TABLES x$hash WRITE, summary WRITE");
	}
	$results2 = mysql_query("SELECT status, COUNT(status) from x$hash GROUP BY status");
	echo "<tr class=\"$writeout\"><td>";
	if (!is_null($filename))
		echo $filename;
	else
		echo $hash;
	echo "</td>";
	if (!$results2)
	{
		echo "<td colspan=\"4\">Unable to process: ".mysql_error()."</td></tr>";
		continue;
	}

	$counts = array();
	while ($row = mysql_fetch_row($results2))
		$counts[$row[0]] = $row[1];	
	if (!isset($counts["leecher"]))
		$counts["leecher"] = 0;
	if (!isset($counts["seeder"]))
		$counts["seeder"] = 0;

	if ($counts["leecher"] != $leechers)
	{
		quickQuery("UPDATE summary SET leechers=".$counts["leecher"]." WHERE info_hash=\"$hash\"");
		echo "<td>$leechers -> ".$counts["leecher"]."</td>";
	}
	else
		echo "<td>$leechers</td>";

	if ($counts["seeder"] != $seeders)
	{
		quickQuery("UPDATE summary SET seeds=".$counts["seeder"]." WHERE info_hash=\"$hash\"");
		echo "<td>$seeders -> ".$counts["seeder"]."</td>";
	}
	else
		echo "<td>$seeders</td>";
//	echo "<td>$finished</td>";
	if ($bytes < 0)
	{
		quickQuery("UPDATE summary SET dlbytes=0 WHERE info_hash=\"$hash\"");
		echo "<td>$bytes -> Zero</td>";
	}
	else
		echo "<td>". round($bytes/1048576/1024,3) ." GB</td>";

	myTrashCollector($hash, $report_interval, time(), $writeout);
	echo "</td><td>";
	

	if ($GLOBALS["peercaching"])
	{

		$result = mysql_query("SELECT x$hash.sequence FROM x$hash LEFT JOIN y$hash ON x$hash.sequence=y$hash.sequence WHERE y$hash.sequence IS NULL") or die(mysql_error());
		if (mysql_num_rows($result) > 0)
		{
			echo "Added ", mysql_num_rows($result);
			$row = array();
			
			while ($data = mysql_fetch_row($result))
					$row[] = "sequence=\"${data[0]}\"";
			$where = implode(" OR ", $row);
			$query = mysql_query("SELECT * FROM x$hash WHERE $where");
			
			while ($row = mysql_fetch_assoc($query))
			{
				$compact = mysql_escape_string(pack('Nn', ip2long($row["ip"]), $row["port"]));
					$peerid = mysql_escape_string('2:ip' . strlen($row["ip"]) . ':' . $row["ip"] . '7:peer id20:' . hex2bin($row["peer_id"]) . "4:porti{$row["port"]}e");
				$no_peerid = mysql_escape_string('2:ip' . strlen($row["ip"]) . ':' . $row["ip"] . "4:porti{$row["port"]}e");
				mysql_query("INSERT INTO y$hash SET sequence=\"{$row["sequence"]}\", compact=\"$compact\", with_peerid=\"$peerid\", without_peerid=\"$no_peerid\"");
			}
		}	
		else
			echo "Added none";
	
		$result = mysql_query("SELECT y$hash.sequence FROM y$hash LEFT JOIN x$hash ON y$hash.sequence=x$hash.sequence WHERE x$hash.sequence IS NULL");
		if (mysql_num_rows($result) > 0)
		{
			echo ", Deleted ",mysql_num_rows($result);
	
			$row = array();
			
			while ($data = mysql_fetch_row($result))
				$row[] = "sequence=\"${data[0]}\"";
			$where = implode(" OR ", $row);
			$query = mysql_query("DELETE FROM y$hash WHERE $where");
		}
		else
			echo ", Deleted none";
	}
	else
		echo "N/A";
	echo "</td>";
	
	echo "</tr>\n";
	$i ++;

//	Disabled because it's kinda not that important.
//	quickQuery("OPTIMIZE TABLE x$hash");

	if ($locking)
		quickQuery("UNLOCK TABLES");

	// Finally, it's time to do stuff to the summary table.
	if (!empty($summaryupdate))
	{
		$stuff = "";
		foreach ($summaryupdate as $column => $value)
		{
			$stuff .= ', '.$column. ($value[1] ? "=" : "=$column+") . $value[0];
		}
		mysql_query("UPDATE summary SET ".substr($stuff, 1)." WHERE info_hash=\"$hash\"");
		$summaryupdate = array();
	}


}

function myTrashCollector($hash, $timeout, $now, $writeout)
{
//	error_log("Trash collector working on $hash");
 	
 	$peers = loadLostPeers($hash, $timeout);
 	for ($i=0; $i < $peers["size"]; $i++)
	        killPeer($peers[$i]["peer_id"], $hash, $peers[$i]["bytes"], $peers[$i]);
	if ($i != 0)
		echo "<td>Removed $i</td>";
	else
		echo "<td>Removed 0</td>";
 	quickQuery("UPDATE summary SET lastcycle='$now' WHERE info_hash='$hash'");
}




?>
</table>
<p><a href="mystats.php">Return to Statistics Page</a></p>
</body>
</html>