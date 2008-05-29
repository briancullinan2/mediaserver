<?php

// handle selecting of files


// load template
require_once '../include/common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

// check if trying to change selected items
if(isset($_REQUEST['select']))
{
		
	// store this query in the session
	$_SESSION['select'] = array();
	$_SESSION['select']['on'] = @$_REQUEST['on'];
	$_SESSION['select']['off'] = @$_REQUEST['off'];
	$_SESSION['select']['item'] = @$_REQUEST['item'];

	// check for selected files
	if(!isset($_SESSION['selected']))
		$_SESSION['selected'] = array();
	
	if(isset($_REQUEST['item']))
	{
		foreach($_REQUEST['item'] as $id => $value)
		{
			if(($value == 'on' || $_REQUEST['select'] == 'All') && !in_array($id, $_SESSION['selected']))
			{
				$_SESSION['selected'][] = $id;
			}
			elseif(($value == 'off' || $_REQUEST['select'] == 'None') && ($key = array_search($id, $_SESSION['selected'])) !== false)
			{
				unset($_SESSION['selected'][$key]);
			}
		}
	}
	
	if(isset($_REQUEST['on']))
	{
		$_REQUEST['on'] = split(',', $_REQUEST['on']);
		foreach($_REQUEST['on'] as $i => $id)
		{
			if(!in_array($id, $_SESSION['selected']))
			{
				$_SESSION['selected'][] = $id;
			}
		}
	}
	
	if(isset($_REQUEST['off']))
	{
		$_REQUEST['off'] = split(',', $_REQUEST['off']);
		foreach($_REQUEST['off'] as $i => $id)
		{
			if($key = array_search($id, $_SESSION['selected']))
			{
				unset($_SESSION['selected'][$key]);
			}
		}
	}
	
	if(count($_SESSION['selected']) == 0) unset($_SESSION['selected']);
	
	
	// clear post and redirect back to self, this is so there is no stupid post messages
	if(isset($_POST['select']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}


}


// initialize properties for select statement
$props = array();

// set a show in the request
if( !isset($_REQUEST['show']) || !is_numeric($_REQUEST['show']) )
	$_REQUEST['show'] = 0;
if( !isset($_REQUEST['count']) || !is_numeric($_REQUEST['count']) )
	$_REQUEST['count'] = 15;
	
$props['OTHER'] = 'ORDER BY Filepath LIMIT ' . $_REQUEST['show'] . ',' . $_REQUEST['count'];

// add category
if(!isset($_REQUEST['cat']))
{
	$_REQUEST['cat'] = 'db_file';
}

// add where includes
$columns = call_user_func(array($_REQUEST['cat'], 'DETAILS'), 10);

if(isset($_REQUEST['includes']) && $_REQUEST['includes'] != '')
{
	$props['WHERE'] = '';
	
	$regexp = $_REQUEST['includes'];
	
	$props['WHERE'] .= '(';
	foreach($columns as $i => $column)
	{
		$columns[$i] .= ' REGEXP "' . $regexp . '"';
	}
	$props['WHERE'] .= join(' OR ', $columns) . ')';
}

// make select call
$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);

// this is how we get the count of all the items
unset($props['OTHER']);
$props['SELECT'] = 'count(*)';

// get count
$result = $mysql->get(get_class_const($_REQUEST['cat'], 'DATABASE'), $props);

$smarty->assign('total_count', intval($result[0]['count(*)']));

// display the 15 results
foreach($files as $i => &$file)
{
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != 'db_file' && call_user_func(array($module, 'handles'), $file['Filepath']))
		{
			$return = call_user_func(array($module, 'get'), $mysql, array('WHERE' => 'Filepath = "' . $file['Filepath'] . '"'));
			if(isset($return[0])) $file = array_merge($return[0], $file);
		}
	}

}

$smarty->assign('files', $files);

// set select variables in template
// set them here because the keys in the list array are recursive
if(isset($_SESSION['select']))
	$smarty->assign('select', $_SESSION['select']);

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_TEMPLATE . 'select.html');


?>