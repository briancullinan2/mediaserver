<?php




/************************************
 *
 * Peer cache table creator.
 *
 * This program will partially hang the tracker during
 * its execution as it tears through each torrent.
 * It shouldn't take long, but might cause the system to jolt.
 *
 * This program only needs to be executed as part of the upgrade
 * process for an earlier version of the tracker. Running it later
 * will do nothing, so delete it when finished.
 *
 ************************************/
header("Content-Type: text/plain");
require_once("config.php");
require_once("funcsv2.php");

if (!isset($GLOBALS["peercaching"]))
	die("Peer caching configuration directive not found. Not proceeding.\nSee config-sample.php included in the distribution zip for the setting.\n\nShort answer:\n\$GLOBALS[\"peercaching\"] = true;");
//	die();
if (!$GLOBALS["peercaching"])
	die("Peer caching is disabled in the config file.\nNot proceeding.\n");


$db = @mysql_connect($dbhost, $dbuser, $dbpass) or die("\n\nCan't connect to database: ".mysql_error());
@mysql_select_db($database) or die("Can't select database: ".mysql_error());



$summary = mysql_query("SELECT info_hash FROM summary");

while ($hash = mysql_fetch_row($summary))
{
	$info_hash = $hash[0];
	
	echo $info_hash . " --- ";
	
	$query = "CREATE TABLE y$info_hash (sequence int unsigned NOT NULL default 0, with_peerid char(101) NOT NULL default '', without_peerid char(40) NOT NULL default '', compact char(6) NOT NULL DEFAULT '', unique k (sequence)) DELAY_KEY_WRITE=1 CHECKSUM=0";
	$res = mysql_query($query);
	if (!$res)
	{
		echo mysql_error()."\n";
		continue;
	}

	mysql_query("LOCK TABLES x$info_hash READ, y$info_hash WRITE");

	$result = mysql_query("SELECT ip, port, peer_id, sequence FROM x$info_hash");

	$counter = 0;
	$cmd = "";	
	while ($row = mysql_fetch_assoc($result))
	{
		$compact = mysql_escape_string(pack('Nn', ip2long($row["ip"]), $row["port"]));
		$peerid = mysql_escape_string('2:ip' . strlen($row["ip"]) . ':' .
$row["ip"] . '7:peer id20:' . hex2bin($row["peer_id"]) . "4:porti{$row["port"]}e");
		$no_peerid = mysql_escape_string('2:ip' . strlen($row["ip"]) . ':' .
$row["ip"] . "4:porti{$row["port"]}e");

		$cmd .= ", (${row["sequence"]}, \"$compact\", \"$peerid\", \"$no_peerid\")";
		$counter++;
		if ($counter >= 10)
		{
			mysql_query("INSERT INTO y$info_hash (sequence, compact, with_peerid, without_peerid) VALUES " . substr($cmd, 1));
			$cmd = "";
			$counter = 0;
		}
	}
	if ($counter > 0)
		mysql_query("INSERT INTO y$info_hash (sequence, compact, with_peerid, without_peerid) VALUES " . substr($cmd, 1));

	mysql_query("UNLOCK TABLES");
	echo "OK\n";
}
