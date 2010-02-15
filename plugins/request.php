<?php

define('REQUEST_PRIV', 				1);

// this is just a wrapper plugin for moving around request variables
//  this is handy when a client service already uses certain variables but the db_file module recognizes something else
//  this is so the clients can remain simple, and the server can handle some of the complexities

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < REQUEST_PRIV )
{
	// redirect to login page
	header('Location: ' . HTML_ROOT . 'plugins/login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . REQUEST_PRIV);
	
	exit();
}

// check for ampache compitibility
if(strpos($_SERVER['REQUEST_URI'], '/server/xml.server.php?') !== false)
{
	// yes they are trying to access ampache
	// send request to ampache plugin
	@include LOCAL_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'ampache.php';
	
	// exit because the request was handled
	exit;
}

// if it isn't handled and exited by now then there is no hope
header("HTTP/1.0 404 Not Found");
die('Page not found');

?>