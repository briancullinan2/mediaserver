<?php

define('INSTALL_PRIV', 				10);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// check security level
if( $_SESSION['privilage'] < INSTALL_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_ROOT . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . INSTALL_PRIV);
	
	exit();
}

$_REQUEST['debug'] = true;
$_REQUEST['log_sql'] = true;

// install stuff based on what step we are on
// things to consider:
// install stuff on each page
// show output information
// if there are errors do not go on


// install tables
$GLOBALS['database'] = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$GLOBALS['database']->install();

?>
Install script has completed, if there were errors you would see them above!