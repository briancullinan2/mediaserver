<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<title>Add Torrents</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<div class="center">
<?php
function clean($input)
{
	if (get_magic_quotes_gpc())
		return stripslashes($input);
	return $input;
}

//// Configuration

require_once ("config.php");
require_once ("funcsv2.php");
require_once ("BDecode.php");
require_once ("BEncode.php");

if (!isset($upload_username) || !isset($upload_password))
	die("<p class=\"error\">You must configure the username/password in the config file first.</p>");

if (clean($upload_username) == "username" && clean($upload_password) == "password")
	die("<p class=\"error\">You must configure the username/password in the config file first.</p>");

if (isset($_POST["username"]))
{
	if ($_POST["username"] != $upload_username || $_POST["password"] != $upload_password)
	{
		echo "<p class=\"error\">Incorrect username/password</p>";
		endOutput();
	}

	$hash = strtolower($_POST["hash"]);

	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die("<p class=\"error\">Couldn't connect to database. contact the administrator</p>");
	mysql_select_db($database) or die("<p class=\"error\">Can't open the database.</p>");


	if (isset($_FILES["torrent"]))
	{
	   if ($_FILES["torrent"]["error"] != 4)	
	   {
		$fd = fopen($_FILES["torrent"]["tmp_name"], "rb") or die("<p class=\"error\">File upload error 1</p>\n");
		is_uploaded_file($_FILES["torrent"]["tmp_name"]) or die("<p class=\"error\">File upload error 2</p>\n");
		$alltorrent = fread($fd, filesize($_FILES["torrent"]["tmp_name"]));

		$array = BDecode($alltorrent);
		if (!$array)
		{
			echo "<p class=\"error\">There was an error handling your uploaded torrent. The parser didn't like it.</p>";
			endOutput();
			exit;
		}
		$hash = @sha1(BEncode($array["info"]));
		fclose($fd);
		unlink($_FILES["torrent"]["tmp_name"]);
	   }
	}

	if (isset($_POST["filename"]))
		$filename= clean($_POST["filename"]);
	else
		$filename = "";
	
	if (isset($_POST["url"]))
		$url = clean($_POST["url"]);
	else
		$url = "";

	if (isset($_POST["info"]))
		$info = clean($_POST["info"]);
	else
		$info = "";

	if (isset($_POST["autoset"]))
	if (strcmp($_POST["autoset"], "enabled") == 0)
	{
		if (strlen($filename) == 0 && isset($array["info"]["name"]))
			$filename = $array["info"]["name"];
		if (strlen($info) == 0 && isset($array["info"]["piece length"]))
		{
			$info = $array["info"]["piece length"] / 1024 * (strlen($array["info"]["pieces"]) / 20) /1024;
			$info = round($info, 2) . " MB";
			if (isset($array["comment"]))
				$info .= " - ".$array["comment"];
		}
	}
	
	$filename = mysql_escape_string($filename);
	$url = mysql_escape_string($url);
	$info = mysql_escape_string($info);

	if ((strlen($hash) != 40) || !verifyHash($hash))
	{
		echo("<p class=\"error\">Error: Info hash must be exactly 40 hex bytes.</p>");
		endOutput();
	}

	$query = "INSERT INTO namemap (info_hash, filename, url, info) VALUES (\"$hash\", \"$filename\", \"$url\", \"$info\")";
	$status = makeTorrent($hash, true);
	quickQuery($query);
	if ($status)
		echo "<p class=\"error\">Torrent was added successfully.</p>";
	else
		echo "<p class=\"error\">There were some errors. Check if this torrent had been added previously.</p>";

}
else
	echo "<h2>Specify information to proceed<br/>Information fields (except URL) can be loaded from the<br/>torrent automatically using the checkbox below</h2>\n";
endOutput();

function endOutput() {
	// Switch out of PHP mode. Much easier to output a large wad of HTML.
	?>
	<form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<table>
	<tr>
		<td class="right" style="width:50%">Username:</td>
		<td class="left"><input type="text" name="username" size="20"/></td>
	</tr>
	<tr>
		<td class="right">Password:</td>
		<td class="left"><input type="password" name="password" size="20"/></td>
	</tr>
	<tr>
		<td class="right">Torrent file:</td>
		<td class="left"><?php
		if (function_exists("sha1"))
			echo "<input type=\"file\" name=\"torrent\" size=\"30\"/>";
		else
			echo '<i>File uploading not available - no SHA1 function.</i>';
		?></td>
	</tr>
	<?php if (function_exists("sha1")) 
		echo "<tr><td class=\"center\" colspan=\"2\"><input type=\"checkbox\" name=\"autoset\" value=\"enabled\" checked=\"checked\" /> Fill in fields below automatically using data from the torrent file.</td></tr>\n"; ?>
	<tr>
		<td class="right">Info Hash:</td>
		<td class="left"><input type="text" name="hash" size="40"/></td>
	</tr>
	<tr>
		<td class="right">File name (optional): </td>
		<td class="left"><input type="text" name="filename" size="50" maxlength="200"/></td>
	</tr>
	<tr>
		<td class="right">Torrent's URL (optional): </td>
		<td class="left"><input type="text" name="url" size="50" maxlength="200"/></td>
	</tr>
	<tr>
		<td class="right">Short description(optional): </td>
		<td class="left"><input type="text" name="info" size="50" maxlength="200"/></td>
	</tr>
	<tr>
		<td class="right"><input type="submit" value="Create"/></td>
		<td class="left"><input type="reset" value="Clear Settings"/></td>
	</tr>
	</table>
	</form>
	<p><a href="mystats.php">Return to Statistics Page</a></p>
	</div>
	</body></html>
	<?php 	
	// Still in function endOutput()
	exit;
}