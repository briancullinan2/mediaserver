<?php

// handles selecting the display options

// load template
require_once '../include/common.php';

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

// get all columns from every modules
$columns = array();
foreach($GLOBALS['modules'] as $i => $module)
{
	$columns = array_merge($columns, array_flip(call_user_func(array($module, 'DETAILS'), 10)));
}

$columns = array_keys($columns);

if(isset($_REQUEST['display']))
{
	// store in session
	$_SESSION['display'] = array();
	$_SESSION['display']['detail'] = $_REQUEST['detail'];
	
	// store columns
	foreach($columns as $i => $column)
	{
		// since request is merged then it will always merge the checkboxes so use _Post and _Get instead.
		if(isset($_POST['column_' . $column]) || isset($_GET['column_' . $column]))
		{
			$_SESSION['display'][$column] = 'on';
		}
		else
		{
			$_SESSION['display'][$column] = 'off';
		}
	}
	
	if(isset($_POST['display']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}
	
}

// output rss builder
$smarty->assign('columns', $columns);

// set the search vars in the template
if(isset($_SESSION['display']))
	$smarty->assign('display', $_SESSION['display']);


if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_TEMPLATE . 'display.html');


?>