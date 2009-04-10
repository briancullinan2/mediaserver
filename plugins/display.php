<?php

// handles selecting the display options

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// get all columns from every module
$columns = getAllColumns();
$GLOBALS['smarty']->assign('columns', getAllColumns());

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
			$_SESSION['columns'] = split(',', $_REQUEST['column']);
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
	
	if(isset($_REQUEST['columns_on']))
	{
		if($_REQUEST['columns_on'] == '_All_') $_REQUEST['columns_on'] = $columns;
		if(is_string($_REQUEST['columns_on'])) $_REQUEST['columns_on'] = split(',', $_REQUEST['columns_on']);
		foreach($_REQUEST['columns_on'] as $i => $id)
		{
			if(!in_array($id, $_SESSION['columns']))
			{
				$_SESSION['columns'][] = $id;
			}
		}
	}
	
	if(isset($_REQUEST['columns_off']))
	{
		if($_REQUEST['columns_off'] == '_All_') $_REQUEST['columns_off'] = $columns;
		if(is_string($_REQUEST['columns_off'])) $_REQUEST['columns_off'] = split(',', $_REQUEST['columns_off']);
		foreach($_REQUEST['columns_off'] as $i => $id)
		{
			if(((isset($_REQUEST['columns_off']) && !in_array($id, $_REQUEST['columns_off'])) || !isset($_REQUEST['columns_off'])) && ($key = array_search($id, $_SESSION['columns'])) !== false)
			{
				unset($_SESSION['columns'][$key]);
			}
		}
	}
	
	// make sure all selected items are numerics
	foreach($_SESSION['columns'] as $i => $value) if(!in_array($value, $columns)) unset($_SESSION['columns'][$i]);
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
	$GLOBALS['smarty']->assign('display', $_SESSION['display']);

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_DISPLAY']) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_DISPLAY'];
	else
	{
		header('Content-Type: ' . getMime($GLOBALS['templates']['TEMPLATE_DISPLAY']));
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_DISPLAY']);
	}
}

?>