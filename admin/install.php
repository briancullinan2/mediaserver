<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( loggedIn() )
{
}
else
{
	// redirect to login page
	header('Location: login.php?return=' . $_SERVER['REQUEST_URI']);
	
	exit();
}

// install stuff based on what step we are on
// things to consider:
// install stuff on each page
// show output information
// if there are errors do not go on


// install tables
$GLOBALS['database'] = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$GLOBALS['database']->install();

// upgrade for good measure just to make sure
if(isset($_REQUEST['upgrade']) && $_REQUEST['upgrade'] == true)
	$GLOBALS['database']->upgrade();

?>
Install script has completed, if there were errors you would see them above!