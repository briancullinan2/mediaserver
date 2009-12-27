<?php

define('UPLOAD_PRIV', 				5);

// allow uploading of files into user directories

// make sure user in logged in
if( $_SESSION['privilage'] < UPLOAD_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_ROOT . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . UPLOAD_PRIV);
	
	exit();
}

?>