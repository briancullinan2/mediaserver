<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php

function classicoutput($array, $infohash)
{

	if (isset($array["info"]["pieces"]))
		$array["info"]["pieces"] = "<i>Checksum data (" . strlen ($array["info"]["pieces"]) / 20 . " pieces)</i>";


	echo "Info hash: <TT>$infohash</TT><BR>";
	echo "<PRE>";
/*	if (isset($_POST["hexsanity"]))
	{
		if ($_POST["hexsanity"] == 'yes')
		{
			$newarray = cleaner($array);
			print_r($newarray);
		}	
	}
	else if (isset($_GET["cleaner"]))*/
		print_r(cleaner($array));
//	else
//		print_r($array);
	echo "</PRE>";

}

function announceoutput($array)
{
	if (!isset($array["peers"][0]))
	{
		echo "Not a tracker announce block. Falling back on classic.<BR><BR>";
		classicoutput($array, "(Not checked)");
		exit;
	}
	echo "<H2>Client configuration options</H2>";
	echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>";
	foreach ($array as $left => $right)
	{
		if ($left == "peers")
			continue;
		if (is_array($right))
			$myright = "<I>Error</I>";
		else
			$myright = $right;
		echo "<TR><TD ALIGN=RIGHT>".$left."</TD><TD>=</TD><TD>".$myright."</TD></TR>\n";
	}
	echo "</TABLE><BR><H2>Peers</H2><PRE>";
	foreach ($array["peers"] as $data)
	{
		if (!is_array($data)) // special case: [0] == true  means empty list
		{
			echo "(Empty results)\n";
			break;
		}
		echo bin2hex($data["peer id"])." at ".$data["ip"].":".$data["port"]."\n";
	}
	echo "</PRE>";
}

function escapeURL($url)
{
	$ret = "";
	$i=0;
	while (strlen($url) > $i)
	{
		$ret .= "%".$url[$i].$url[$i + 1];
		$i+=2;
	}
	return $ret;
}






?>
<HTML><HEAD><TITLE>DeHackEd's torrent decoder</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=us-ascii">
</HEAD><BODY>
<TABLE WIDTH="50%" BORDER=1><TR><TD>
DumpTorrentCGI: a sample front-end to a bencode library for PHP.
</TD></TR>
</TABLE><BR>
<FORM ENCTYPE="multipart/form-data" METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"]; ?>">
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" VALUE="900000">
Torrent file: <INPUT TYPE="file" NAME="torrent"><BR>
<BR>
OR
<BR><BR>
Torrent URL: <INPUT TYPE=text NAME="url" SIZE=50><BR><BR>
<?php /*
<INPUT TYPE="checkbox" NAME="hexsanity" VALUE="yes">Clean high-ascii<BR><BR>
*/ ?>
Output type: <SELECT NAME="output">
<OPTION VALUE="-1">Auto-detect
<OPTION VALUE="0">Classic (raw)
<OPTION VALUE="1">.torrent file
<OPTION VALUE="2">/scrape
<OPTION VALUE="3">/announce
<?php /* ?> <OPTION VALUE="4">*.torrent -> /announce <?php */ ?>
</SELECT><BR><BR>
<INPUT TYPE="submit" VALUE="Decode">
</FORM>
<?php

/* The source code to a very popular link. It's pretty simple, actually. 
  But when I made it intelligent and output more cleanly, it got less
  clean.
*/


require_once("BDecode.php");
require_once("BEncode.php");

function stringcleaner($str)
{
	/* WARNING:
	
	It appears PHP doesn't handle null bytes in the key portion
	of string-indexed arrays. $array["abcd\0e"] = $something
	will find itself with only 4 letters in the key. This may
	cause some confusion when using /scrape, for example.
	
	*/

	$len = strlen($str);
	for ($i=0; $i < $len; $i ++)
	{
		if (ord($str[$i]) < 32 || ord($str[$i]) > 128)
			return "<B>".bin2hex($str)."</B>";
	}
	return $str;
}

function cleaner($array)
{
	if (!is_array($array))
		return $array;
	$newarray = array();
	foreach($array as $left => $right)
	{
		if (is_string($left))
			$newleft = stringcleaner(stripslashes($left));
		else
			$newleft = $left;

		if (is_string($right))
			$newright = stringcleaner($right);
		else if (is_array($right))
			$newright = cleaner($right);
		else
			$newright = $right;

		$newarray[$newleft] = $newright;
	}
	return $newarray;
}




/* Handling really big files you don't want.

I've had this happen a lot: some idiot uploads a really big file
(by big, I mean 650 MB (CD image?)) and PHP downloads the whole
thing, only to give an error message at the end of it and discard the
results.

If you want to avoid this, use Apache's LimitRequestBody directive.
The connection will be refused outright before any useful data is 
transfered.

*/

if (isset($_POST["output"]))
{
	if (!is_numeric($_POST["output"]))
		$output = -1;
	else
		$output = $_POST["output"];
	if ($output > 4 || $output < 0)
		$output = -1;


}
else if (isset($_GET["style"]))
	$output = $_GET["style"];
else
	$output = -1;

if ($output == 4)
{
	echo "That output type is restricted. Using *.torrent instead.<BR>";
	$output = 1;
}

if (isset($_FILES["torrent"]) || isset($_POST["url"]) || isset($_GET["url"]))
{
	if (strlen($_FILES["torrent"]["tmp_name"]) > 0)
	{
		$fd = fopen($_FILES["torrent"]["tmp_name"], "rb") or die("File upload error 1\n");
		is_uploaded_file($_FILES["torrent"]["tmp_name"]) or die("File upload error 2\n");
		$alltorrent = fread($fd, filesize($_FILES["torrent"]["tmp_name"]));
		fclose($fd);
	}
	else if (isset($_POST["url"]))
	{
		(strlen($_POST["url"]) > 0) or die("Logic error in script.");
		if (strtolower(substr($_POST["url"], 0, 7)) != "http://")
			die("Error: you must specify \"http://\" as part of the URL.");
		$fd = fopen($_POST["url"], "rb") or die("File download error.</BODY></HTML>");
		$alltorrent = "";
		while (!feof($fd))
		{
			$alltorrent .= fread($fd, 4096);
			if (strlen($alltorrent) > 500000)
				die("File too large to download.</BODY></HTML>");
		}
		fclose($fd);
	}
	else if (isset($_GET["url"]))
	{
 	 	(strlen($_GET["url"]) > 0) or die("Logic error in script.");
 	 	if (strtolower(substr($_GET["url"], 0, 7)) != "http://")
 	 	        die("Error: you must specify \"http://\" as part of the URL");
 	 	$fd = fopen($_GET["url"], "rb") or die("File download error.</BODY></HTML>");
 	 	$alltorrent = "";
 	 	while (!feof($fd))
 	 	{
 	 	 	$alltorrent .= fread($fd, 4096);
 	 	 	if (strlen($alltorrent) > 500000)
 	 	 	        die("File too large to download.</BODY></HTML>");
 	 	}
 	 	fclose($fd);

	}
	$array = BDecode($alltorrent);
	if (!isset($array))
	{
		echo "<FONT COLOR=\"red\">There was an error handling your uploaded torrent. It may be corrupted.</FONT></BODY></HTML>";
		exit;
	}

	if ($array == false)
	{
                echo "<FONT COLOR=\"red\">There was an error handling your uploaded torrent. It may be corrupted.</FONT></BODY></HTML>";
                exit;
	}

	// Making torrents look nice: If $array["info"] exists, it is used to calculate
	// an Info_hash value.

	$infohash = "<I>Not applicable</I>";	
	if (isset($array["info"]))
		if (is_array($array["info"]))
		{
			if (function_exists("sha1"))
				$infohash = @sha1(BEncode($array["info"]));
			else
				$infohash = "(No SHA1 available to calculate info_hash)</TT><BR>";
			
			// If the "pieces" section exists, it is replaced by some nice text.
			// The alternative is pages of garbage.
		}

	// Auto-detect file type
	if ($output == -1)
	{
		if (isset($array["announce"]) && isset($array["info"]))
			$output = 1;
		else if (isset($array["files"]))
			$output = 2;
		else if (isset($array["peers"]))
			$output = 3;
		else
			$output = 0;
	}

	// Output information.
	if ($output == 0)
	{
		classicoutput($array, $infohash);
	}

	if ($output == 1)
	{
		if (!isset($array["info"]))
		{
		 	echo "Error: not a torrent file. Falling back on classic.<BR><BR>";

		 	classicoutput($array, "<I>Not applicable</I>");
		 	exit;	                
		}

		echo "<BR><H2>Non-file data</H2>";
		echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2><TR>";
		echo "<TD ALIGN=RIGHT>Info hash</TD><TD>=</TD><TD><TT>$infohash</TT></TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>Announce URL</TD><TD>=</TD><TD>".$array["announce"]."</TD></TR>\n";
		if (isset($array["creation date"]))
		{
			echo "<TR><TD ALIGN=RIGHT>Creation date</TD><TD>=</TD><TD>";
			if (is_numeric($array["creation date"]))
				echo date("F j, Y", $array["creation date"]);
			else
				echo $array["creation date"];
			echo "</TD></TR>";
		}
		foreach ($array as $left => $right)
		{
			if ($left == "announce" || $left == "info" || $left == "creation date")
				continue; // skip
			echo "<TR><TD ALIGN=RIGHT>$left</TD><TD>=</TD><TD>".$array[$left]."</TD></TR>\n";
		}
		
		echo "</TABLE><BR><BR><H2>File data</H2><PRE>";
		$info = $array["info"]; // I'll need it
		
		if (isset($info["files"]))
		{
			echo "Directory: ".$info["name"]."\nFiles:\n";
			foreach ($info["files"] as $file)
			{
				if (isset($file["path"][1]))
				{
					echo "    " . $file["path"][0];
					for ($i=1; isset($file["path"][$i]); $i++)
						echo "/".$file["path"][$i];
				}
				else
					echo "    " . $file["path"][0];
				echo "  (".$file["length"]." bytes)\n";
			}
			echo "\n";
		}
		else
			echo "File: ".$info["name"]. " (".$info["length"]." bytes)\n\n";
		
		echo "Piece length: ".$info["piece length"]."\nNumber of pieces: ". strlen ($array["info"]["pieces"])/20 . "\n</PRE>";
	}

	if ($output == 2)
	{
		if (!isset($array["files"]))
		{
			echo "Error: not /scrape data. Falling back on classic.<BR><BR>";
			classicoutput($array, $infohash);
			exit;		
		}
		$files = $array["files"];
		
		// Copy and paste from python tracker output, with some 
		// formatting changes
		echo '<table cellpadding=2 cellspacing=2 border=1 summary="files"><tr><th>info hash</th><th align="right">complete</th><th align="right">downloading</th><th>finished downloads</th><th>file name</th></tr>';
		
				
		foreach ($files as $hash => $data)
		{
			echo "<TR><TD><TT>".bin2hex(stripslashes($hash))."</TT>";
			echo "</TD><TD>".$data["complete"]."</TD><TD>".$data["incomplete"]."</TD><TD>";
			if (isset($data["downloaded"]))
				echo $data["downloaded"];
			else
				echo "-";
			echo "</TD><TD>";
			if (isset($data["name"]))
				echo $data["name"];
			else
				echo "(unavailable)";
			echo "</TD></TR>";
		}
		echo "</TABLE>";
	}

	// http://tracker.com:6969/announce
	if ($output == 3)
	{
		announceoutput($array);
	}

	if ($output == 4)
	{
		if (!isset($array["info"]))
			die("Not a .torrent file.");
		if (substr($array["announce"], 0, 7) != "http://")
			die("Invalid announce URL in torrent");
 	 	$fd = fopen($array["announce"]."?info_hash=".escapeURL($infohash)."&peer_id=dehacked-diagnostics&left=1048576&uploaded=0&downloaded=0&port=6881&event=stopped&tracker=getting_peer_list", "rb") or die("File download error.</BODY></HTML>");
 	 	$alltorrent = "";
 	 	while (!feof($fd))
 	 	{
 	 	 	$alltorrent .= fread($fd, 4096);
 	 	 	if (strlen($alltorrent) > 500000)
 	 	 	        die("Announce data too large to download (!)</BODY></HTML>");
 	 	}
 	 	fclose($fd);
		$announce = BDecode($alltorrent);
		announceoutput($announce);
	}




// Output helper




} // end of "if there's data submitted."


?>
</BODY></HTML>