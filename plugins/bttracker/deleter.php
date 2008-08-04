<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<title>Torrent deletion script</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<h1>Login</h1>
<form method="post" action="deleter.php">
<table style="width:30%;margin-right:auto; margin-left:auto;">
	<tr>
		<td class="center">Username:</td>
		<td class="center">Password:</td>
	</tr>
	<tr>
		<td><input type="text" name="username" /></td>
		<td><input type="password" name="password" /></td>
	</tr>
</table>
<?php
require_once("config.php");
require_once("funcsv2.php");

// Gotta login first.
if (isset($_POST["username"]) && isset($_POST["password"]))
{
	$db = mysql_connect($dbhost, $_POST["username"], $_POST["password"]) or die("Cannot connect to database. Check your username and password.");
	mysql_select_db($database) or die("Error selecting database.");

	foreach ($_POST as $left => $right)
	{
		if (strlen($left) == 41 && $left[0] == 'x')
		{
			if (!stristr($right,'y') || !verifyHash(substr($left, 1)))
				continue;
			$hash = substr($left, 1);
			@mysql_query("DELETE FROM summary WHERE info_hash=\"$hash\"");
			@mysql_query("DELETE FROM namemap WHERE info_hash=\"$hash\""); 
			@mysql_query("DELETE FROM timestamps WHERE info_hash=\"$hash\"");
			@mysql_query("DROP TABLE y$hash");
			@mysql_query("DROP TABLE x$hash");
		}
	}
}
else
{
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die("Tracker error: can't connect to database - ".mysql_error());
	mysql_select_db($database) or die("Tracker error: can't open database $database - ".mysql_error());
	$GLOBALS["maydelete"] = false;
}

?>
<h1>Torrents</h1>
<table class="torrentlist" cellspacing="1">
<tr>
	<th>Name/Info Hash</th>
	<th>Seeders</th>
	<th>Leechers</th>
	<th>Completed D/Ls</th>
	<th>Bytes Transfered</th>
	<th>Delete?</th>
</tr>
<tr>
	<td style="background-color: #ffffff" colspan="6"></td>
</tr>
<?php

$results = mysql_query("SELECT summary.info_hash, summary.seeds, summary.leechers, format(summary.finished,0), format(summary.dlbytes/1073741824,3),namemap.filename FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash ORDER BY namemap.filename")
or die(mysql_error());

$i = 0;

while ($data = mysql_fetch_row($results)) {
	$writeout = "row" . $i % 2;
	$hash = $data[0];
	if (is_null($data[5]))
		$data[5] = $data[0];
	if (strlen($data[5]) == 0)
		$data[5] = $data[0];
		
	echo "<tr class=\"$writeout\">\n";
	echo "\t<td>".$data[5]."</td>\n";
	for ($j=1; $j < 4; $j++)
		echo "\t<td style=\"text-align: center\">$data[$j]</td>\n";
	echo "\t<td>$data[4] GiB</td>\n";
	
	echo "\t<td class=\"center\"><input type=\"checkbox\" name=\"x$hash\" value=\"y\" /></td>\n";
	echo "</tr>\n";
	$i++;
}

?>
</table>
<p class="error">Warning: there is no confirmation for deleting files. Clicking this button is final.</p>
<p class="center"><input type="submit" value="Delete" /></p>
</form>
<p><a href="mystats.php">Return to Statistics Page</a></p>


</body></html>