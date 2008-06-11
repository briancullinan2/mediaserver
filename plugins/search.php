<?php

// search form for selecting files from the database


// load template
require_once '../include/common.php';

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;
	
// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

// process search query and save in session
if(isset($_REQUEST['search']))
{

	// store this query in the session
	$_SESSION['search'] = array();
	$_SESSION['search']['cat'] = @$_REQUEST['cat'];
	$_SESSION['search']['includes'] = @$_REQUEST['includes'];
	$_SESSION['search']['lim'] = @$_REQUEST['lim'];
	$_SESSION['search']['dir'] = @$_REQUEST['dir'];
	$_SESSION['search']['order_by'] = @$_REQUEST['order_by'];
	
	// redirect if this page is called specifically
	if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	{
		// redirect to select if there is no limit
		if($_SESSION['search']['lim'] == -1)
			header('Location: /' . SITE_PLUGINS . 'select.php');
		// redirect if limit is set
		else
			header('Location: /' . SITE_PLUGINS . 'list.php');
	}
	
	// redirect back to self to clear post
	elseif(isset($_POST['search']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}

}

// set the search vars in the template
if(isset($_SESSION['search']))
	$smarty->assign('search', $_SESSION['search']);

$smarty->assign('modules', $GLOBALS['modules']);

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_TEMPLATE . 'search.html');



?>