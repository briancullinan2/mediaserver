<?php
require_once("config.php");

echo "<HTML><BODY><PRE>";
$db = @mysql_connect($dbhost, $dbuser, $dbpass) or die("\n\nCan't connect to database: ".mysql_error());
@mysql_select_db($database) or die("Can't select database: ".mysql_error());

$result = mysql_query('ALTER TABLE namemap CHANGE `hash` info_hash char(40) not null default""');
if (!$result)
	echo "Namemap change not done (already applied?)";
else
	echo "Namemap change complete.\n\n";

mysql_query('DROP TABLE IF EXSITS timestamps');
$result = mysql_query('CREATE TABLE IF NOT EXISTS timestamps (info_hash char(40) not null, sequence int unsigned not null auto_increment, bytes bigint unsigned  not null, delta smallint unsigned not null, primary key(sequence), key sorting (info_hash, sequence))');
if ($result)
	echo "Speed management table creation successful (new in 1.4).\n";

$result = mysql_query('ALTER TABLE summary ADD COLUMN lastSpeedCycle bigint not null default "-1"');
if ($result)
	echo "Successfully added speed management column in summary table (new in 1.4).\n";


if (!isset($GLOBALS["countbytes"]))
	echo "<FONT COLOR=\"red\" SIZE=\"+3\">\n\n*** Warning</FONT>: Suggest adding the following line to config.php:\n\n\$GLOBALS[\"countbytes\"] = true;\n\n\n See the config-sample.php file in the latest archive for an example of this line, and an explanation.\n\n";
if (!isset($GLOBALS["peercaching"]))
	echo "<FONT COLOR=\"red\" SIZE=\"+3\">\n\n*** Warning</FONT>: Suggest adding the following line to config.php:\n\n\$GLOBALS[\"peercaching\"] = true;\n\n\n See the config-sample.php file in the latest archive for an example of this line, and an explanation.\n\n";

?>

</PRE>
I'm done. See above for results.


</BODY></HTML>