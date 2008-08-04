<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<?php
/*

Although I consider this whole tracker to be GPL code, I EXPECT you to
modify the script. If nothing else, you should re-theme it, maybe add links
to your own site... Whatever.

This script is yours to tinker with. There is no license no this file.
Integrate it into your web site. Re-theme. Dear god, re-theme it!

*/

function doCrash($msg)
{
	echo "</table></table><p class=\"error\">Script error: $msg</p></body></html>";
	exit(1);
}

require_once ("config.php");
require_once ("funcsv2.php");

$scriptname = $_SERVER["PHP_SELF"];
if (!isset($GLOBALS["countbytes"]))
	$GLOBALS["countbytes"] = true;
?>
<html>
<head>
	<title><?php if (isset($GLOABSL["title"])) echo $GLOBALS["title"]; else echo "PHPBT Tracker Statistics";?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	<meta name="Author" content="DeHackEd" />
	<!-- I usually idle in the official BitTorrent tech support/development IRC channel -->
</head>
<body>
<h1><?php if (isset($GLOABSL["title"])) echo $GLOBALS["title"]; else echo "PHPBT Tracker Statistics";?></h1>
<table>
<tr>
	<?php 
	if (!isset($_GET["activeonly"])) 
		echo "<td><a href=\"$scriptname?activeonly=yes\">Show only active torrents</a></td>\n";
	else echo "<td><a href=\"$scriptname\">Show all torrents</a></td>\n";
	if (!isset($_GET["seededonly"])) 
		echo "<td style=\"text-align: right;\"><a href=\"$scriptname?seededonly=yes\">Show only seeded torrents</a></td>\n";
	else echo "<td style=\"text-align: right;\"><a href=\"$scriptname\">Show all torrents</a></td>\n";
	?>
</tr>
<tr>
	<td colspan="2">
	<table class="torrentlist">

	<!-- Column Headers -->
	<tr>
		<th>Name/Info Hash</th><th>Seeders</th><th>Leechers</th><th>Completed D/Ls</th>
		<?php
		// Bytes mode off? Ignore the columns
		if ($GLOBALS["countbytes"])
			echo '<th>Bytes Transferred</th><th>Speed</th>';
		?>
	</tr>
	
<?php
if ($GLOBALS["persist"])
	$db = mysql_pconnect($dbhost, $dbuser, $dbpass) or doCrash("Tracker error: can't connect to database - ".mysql_error());
else
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or doCrash("Tracker error: can't connect to database - ".mysql_error());
mysql_select_db($database) or doCrash("Tracker error: can't open database $database - ".mysql_error());

/* Rewrite, part 1: encode "WHERE" statement only. */

if (isset($_GET["seededonly"]))
	$where = " WHERE seeds > 0";
else if (isset($_GET["activeonly"]))
	$where = " WHERE leechers+seeds > 0";
else
	$where = " ";

// Grab dummy column for dlbytes so we can skip doing format()
if ($GLOBALS["countbytes"])
	$bytes = 'format(summary.dlbytes/1073741824,3)';
else
	$bytes = '0';
$query = "SELECT summary.info_hash, summary.seeds, summary.leechers, format(summary.finished,0), $bytes, namemap.filename, namemap.url, namemap.info, summary.speed FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash $where ORDER BY namemap.filename";
$results = mysql_query($query) or doCrash("Can't do SQL query - ".mysql_error());
$i = 0;

while ($data = mysql_fetch_row($results)) {
	// NULLs are such a pain at times. isset($nullvar) == false
	if (is_null($data[5]))
		$data[5] = $data[0];
	if (is_null($data[6]))
		$data[6] = "";
	if (is_null($data[7]))
		$data[7]="";
	if (strlen($data[5]) == 0)
		$data[5]=$data[0];
	$myhash = $data[0];
	$writeout = "row" . $i % 2;
	echo "<tr class=\"$writeout\">\n";
	echo "\t<td>";
	if (strlen($data[6]) > 0)
		echo "<a href=\"${data[6]}\">${data[5]}</a>";
	else
		echo $data[5];
	if (strlen($data[7]) > 0)
		echo "<br/>(${data[7]})";
	echo "</td>\n";
	for ($j=1; $j < 4; $j++)
		echo "\t<td class=\"center\">$data[$j]</td>\n";

	if ($GLOBALS["countbytes"])
	{
		echo "\t<td class=\"center\">$data[4] GiB</td>\n";

		// The SPEED column calcultions.
		if ($data[8] <= 0)
			$speed = "Zero";
		else if ($data[8] > 2097152)
			$speed = round($data[8]/1048576,2) . " MB/sec";
		else
			$speed = round($data[8] / 1024, 2) . " KB/sec";
		echo "\t<td class=\"center\">$speed</td>\n";
	}
	echo "</tr>\n";
	$i++;
}

if ($i == 0)
	echo "<tr class=\"row0\"><td style=\"text-align: center;\" colspan=\"6\">No data</td></tr>";
?>
	</table></td></tr>
	<tr class="details">
		<td>PHPBTTracker Version 1.5rc3</td>
		<td class="right">Ki = 1024, Mi = 1024 Ki, Gi = 1024 Mi</td>
	</tr>
</table>
<h2>Notes</h2>
<?php
if ($GLOBALS["NAT"])
	echo "<ul><li>This tracker does NAT checking when users connect. If you receive a probe to port 6881, it's probably just me.</li></ul>";
else
	echo '<ul><li>NAT checking has been disabled on this tracker.</li></ul>';

?>
</body></html>