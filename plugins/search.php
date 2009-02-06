<?php

// search form for selecting files from the database


// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load template to create output
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty();

// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

// process search query and save in session
if(isset($_REQUEST['search']))
{

	if(isset($_POST['clear']) || isset($_GET['clear']))
		unset($_REQUEST['includes']);

	// store this query in the session
	$_SESSION['search'] = array();
	$_SESSION['search']['cat'] = @$_REQUEST['cat'];
	$_SESSION['search']['includes'] = @$_REQUEST['includes'];
	$_SESSION['search']['lim'] = @$_REQUEST['lim'];
	$_SESSION['search']['dir'] = @$_REQUEST['dir'];
	$_SESSION['search']['order_by'] = @$_REQUEST['order_by'];
	
	// redirect back to self to clear post
	if(isset($_POST['search']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}

}

// set the search vars in the template
if(isset($_SESSION['search']))
	$smarty->assign('search', $_SESSION['search']);

$smarty->assign('modules', $GLOBALS['modules']);

$smarty->assign('templates', $templates);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty->display($templates['TEMPLATE_SEARCH']);



?>