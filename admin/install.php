<?php

require_once dirname(__FILE__) . '/../include/common.php';

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
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$mysql->install();

?>