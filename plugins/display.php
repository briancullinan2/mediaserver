<?php

// handles selecting the display options

// load template
require_once dirname(__FILE__) . '/../include/common.php';

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

if(isset($_REQUEST['display']))
{
	// store in session
	$_SESSION['display'] = array();
	$_SESSION['display']['detail'] = @$_REQUEST['detail'];
	$_SESSION['display']['order'] = @$_REQUEST['order'];
	$_SESSION['display']['limit'] = @$_REQUEST['limit'];
	
	// check for selected files
	if(!isset($_SESSION['columns']))
		$_SESSION['columns'] = array();
		
	// store columns
	if(isset($_REQUEST['column']))
	{
		if(is_string($_REQUEST['column']))
		{
			$tmp_columns = split(',', $_REQUEST['column']);
			// unset all the ones that aren't columns
			foreach($tmp_columns as $i => $value)
			{
				if(!in_array($value, $columns))
				{
					unset($tmp_columns[$i]);
				}
			}
			$_SESSION['columns'] = $tmp_columns;
		}
		elseif(is_array($_REQUEST['column']))
		{
			foreach($_REQUEST['column'] as $id => $value)
			{
				if(($value == 'on' || $_REQUEST['display'] == 'All') && !in_array($id, $_SESSION['columns']))
				{
					$_SESSION['columns'][] = $id;
				}
				elseif(($value == 'off' || $_REQUEST['display'] == 'None') && ($key = array_search($id, $_SESSION['columns'])) !== false)
				{
					unset($_SESSION['columns'][$key]);
				}
			}
		}
	}
	
	if(isset($_REQUEST['on']))
	{
		if($_REQUEST['on'] == '_All_') $_REQUEST['on'] = $columns;
		if(is_string($_REQUEST['on'])) $_REQUEST['on'] = split(',', $_REQUEST['on']);
		foreach($_REQUEST['on'] as $i => $id)
		{
			if(!in_array($id, $_SESSION['columns']))
			{
				$_SESSION['columns'][] = $id;
			}
		}
	}
	
	if(isset($_REQUEST['off']))
	{
		if($_REQUEST['off'] == '_All_') $_REQUEST['off'] = $columns;
		if(is_string($_REQUEST['off'])) $_REQUEST['off'] = split(',', $_REQUEST['off']);
		foreach($_REQUEST['off'] as $i => $id)
		{
			if(((isset($_REQUEST['on']) && !in_array($id, $_REQUEST['on'])) || !isset($_REQUEST['on'])) && ($key = array_search($id, $_SESSION['columns'])) !== false)
			{
				unset($_SESSION['columns'][$key]);
			}
		}
	}
	
	$_SESSION['columns'] = array_values($_SESSION['columns']);	
	if(count($_SESSION['columns']) == 0) unset($_SESSION['columns']);

	
	if(isset($_POST['display']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}
	
}

// set the search vars in the template
if(isset($_SESSION['display']))
	$smarty->assign('display', $_SESSION['display']);


$smarty->assign('templates', $templates);
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display($templates['TEMPLATE_DISPLAY']);


?>