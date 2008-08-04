<?include "inc_security.php";?>
<?include "inc_config.php";?>
<?include "inc_functions.php";?>
<?php
$message = "&nbsp;<a href=\"index.php\" onclick=\"javascript:var x=document.getElementById('message').innerHTML='';return false;\"><img src=\"images/delete.png\"/></a>";

if ( isset($_POST['upload']) ) 
{
	$file = preg_replace( "/[^a-zA-Z0-9\-_\.]+/", "_", basename($_FILES['file']['name']) );
	$uploadfile = $download_path . "/" . $file;
	if (!isset($_FILES['file'])) {
		$endresult = "Please attach a file"; 
	}
	elseif (file_exists($uploadfile)) {
		$endresult = "Torrent already here";
	}
	elseif (! strrchr($_FILES['file']['name'], '.')==".torrent") {
		$endresult = "Not a Torrent";
	}
	else{
		if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
			$endresult = "Torrent uploaded";
		}
		else {
			$endresult = "Could not copy the Torrent";
		}
	}
		if(isset($_POST["dyn"]))
			echo $endresult . $message;
		else
		    header("Location: index.php?endresult=$endresult");
}
elseif ($_GET["start"])
{
		$endresult = start($_GET["start"]);			
		if(isset($_GET["dyn"]))
			echo $endresult . $message;
		else
		    header("Location: index.php?endresult=$endresult");
}
elseif ($_GET["stop"]) {	
		$endresult = stop($_GET["stop"]);
		if(isset($_GET["dyn"]))
			echo $endresult . $message;
		else
		    header("Location: index.php?endresult=$endresult");
}
elseif(isset($_GET["delete"]))
{
		$endresult = delete($_GET["delete"]);
		if(isset($_GET["dyn"]))
			echo $endresult . $message;
		else
		    header("Location: index.php?endresult=$endresult");
}
?>
