<?php

// handle selecting of files


// load template
require_once '../include/common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;
	
// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

$error = '';

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
		if(is_string($_REQUEST['item']))
		{
			$_SESSION['selected'] = split(',', $_REQUEST['item']);
		}
		elseif(is_array($_REQUEST['item']))
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
			if(($key = array_search($id, $_SESSION['selected'])) !== false)
			{
				unset($_SESSION['selected'][$key]);
			}
		}
	}
	
	$_SESSION['selected'] = array_values($_SESSION['selected']);	
	if(count($_SESSION['selected']) == 0) unset($_SESSION['selected']);
	
	// clear post and redirect back to self, this is so there is no stupid post messages
	if(isset($_POST['select']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}


}

// add category
if(!isset($_REQUEST['cat']))
	$_REQUEST['cat'] = 'db_file';

$columns = call_user_func(array($_REQUEST['cat'], 'columns'));

// initialize properties for select statement
$props = array();

// set a show in the request
if( !isset($_REQUEST['start']) || !is_numeric($_REQUEST['start']) || $_REQUEST['start'] < 0 )
	$_REQUEST['start'] = 0;
if( !isset($_REQUEST['limit']) || !is_numeric($_REQUEST['limit']) || $_REQUEST['limit'] < 0 )
	$_REQUEST['limit'] = 15;
if( !isset($_REQUEST['order_by']) || !in_array($_REQUEST['order_by'], $columns) )
	$_REQUEST['order_by'] = 'Filepath';
	
$props['OTHER'] = ' ORDER BY ' . $_REQUEST['order_by'] . ' LIMIT ' . $_REQUEST['start'] . ',' . $_REQUEST['limit'];

// add where includes
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

// add dir filter to where
if(isset($_REQUEST['dir']))
{
	if($_REQUEST['dir'] == '') $_REQUEST['dir'] = '/';
	// only search for file if is valid dir
	if(realpath($_REQUEST['dir']) !== false || isset($_REQUEST['includes']))
	{
		if(!isset($props['WHERE'])) $props['WHERE'] = '';
		elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
		
		// if the includes is blank then only show files from current directory
		if(!isset($_REQUEST['includes']))
		{
			if(isset($_REQUEST['dirs_only']))
				$props['WHERE'] .= 'Filepath REGEXP "^' . $_REQUEST['dir'] . '[^/]*/$" AND Filepath != "' . $_REQUEST['dir'] . '"';
			else
				$props['WHERE'] .= '(Filepath REGEXP "^' . $_REQUEST['dir'] . '[^/]*/$" OR Filepath REGEXP "^' . $_REQUEST['dir'] . '[^/]*$") AND Filepath != "' . $_REQUEST['dir'] . '"';
		}
		// show all results underneath directory
		else
		{
			if(isset($_REQUEST['dirs_only']))
				$props['WHERE'] .= 'Filepath REGEXP "^' . $_REQUEST['dir'] . '([^/]*/)*$"';
			else
				$props['WHERE'] .= 'Filepath REGEXP "^' . $_REQUEST['dir'] . '"';
		}
	}
	else
	{
		// set smarty error
		$error = 'Directory does not exist.';
	}
}

if($error == '')
{
	// make select call
	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);
}
else
{
	$files = array();
}

// do display order
if(!isset($_REQUEST['order']))
	$_REQUEST['order'] = 'Filepath';

$order_keys_values = array();

// get all the other information from other modules
foreach($files as $index => $file)
{
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && call_user_func(array($module, 'handles'), $file['Filepath']))
		{
			$return = call_user_func(array($module, 'get'), $mysql, array('WHERE' => 'Filepath = "' . $file['Filepath'] . '"'));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}
	
	if(isset($files[$index][$_REQUEST['order']]))
	{
		$order_keys_values[] = $files[$index][$_REQUEST['order']];
	}
	else
	{
		$order_keys_values[] = 'z';
	}
}

if($_REQUEST['order'] != $_REQUEST['order_by'])
{
	if(isset($order_keys_values[0]) && is_numeric($order_keys_values[0]))
		$sorting = SORT_NUMERIC;
	else
		$sorting = SORT_STRING;
	
	array_multisort($files, SORT_ASC, $sorting, $order_keys_values);
}

$smarty->assign('files', $files);

// this is how we get the count of all the items
unset($props['OTHER']);
$props['SELECT'] = 'count(*)';

// get count
$result = $mysql->get(get_class_const($_REQUEST['cat'], 'DATABASE'), $props);

$smarty->assign('total_count', intval($result[0]['count(*)']));

$smarty->assign('error', $error);

// set select variables in template
// set them here because the keys in the list array are recursive
if(isset($_SESSION['select']))
	$smarty->assign('select', $_SESSION['select']);

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
{
	header('Content-Type: ' . getMime($templates['TEMPLATE_SELECT']));
	$smarty->display($templates['TEMPLATE_SELECT']);
}


?>