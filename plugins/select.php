<?php

// handle selecting of files


// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
if(USE_DATABASE) $mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $mysql = NULL;

// load template to create output
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty();
$smarty->compile_dir = LOCAL_ROOT . 'templates_c' . DIRECTORY_SEPARATOR;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;
	
// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

//print_r($_SESSION['selected']);

// check if trying to change selected items
if(isset($_REQUEST['select']))
{
	
	// store this query in the session
	$_SESSION['select'] = array();
	$_SESSION['select']['on'] = @$_REQUEST['on'];
	$_SESSION['select']['off'] = @$_REQUEST['off'];
	$_SESSION['select']['item'] = @$_REQUEST['item'];
	
	getIDsFromRequest($_REQUEST, $_SESSION['selected']);
	
	// clear post and redirect back to self, this is so there is no stupid post messages
	if(isset($_POST['select']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}


}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

// do validation!
if( !isset($_REQUEST['start']) || !is_numeric($_REQUEST['start']) || $_REQUEST['start'] < 0 )
	$_REQUEST['start'] = 0;
if( !isset($_REQUEST['limit']) || !is_numeric($_REQUEST['limit']) || $_REQUEST['limit'] < 0 )
	$_REQUEST['limit'] = 15;
if( !isset($_REQUEST['direction']) || ($_REQUEST['direction'] != 'ASC' && $_REQUEST['direction'] != 'DESC') )
	$_REQUEST['direction'] = 'ASC';
if( !isset($_REQUEST['order_by']) || !in_array($_REQUEST['order_by'], $columns) )
	$_REQUEST['order_by'] = 'Filepath';
if(!isset($_REQUEST['order']))
	$_REQUEST['order'] = $_REQUEST['order_by'];

// make select call
$files = call_user_func_array($_REQUEST['cat'] . '::get', array($mysql, $_REQUEST, &$count, &$error));

if($error != '')
{
	$files = array();
}

$order_keys_values = array();

// get all the other information from other modules
foreach($files as $index => &$file)
{

	// short results to not include information from all the modules
	if(!isset($_REQUEST['short']))
	{
		// merge all the other information to each file
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if($module != $_REQUEST['cat'] && call_user_func(array($module, 'handles'), $file['Filepath']))
			{
				$tmp_count = 0;
				$return = call_user_func(array($module, 'get'), $mysql, array('file' => $file['Filepath']), $tmp_count, $error);
				if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
			}
		}
	}
	
	// do alias replacement on every file path
	if(USE_ALIAS == true)
	{
		$files[$index]['Filepath'] = preg_replace($GLOBALS['paths_regexp'], $GLOBALS['alias'], $file['Filepath']);
		$alias_flipped = array_flip($GLOBALS['alias']);
		// check if the replaced path was the entire alias path
		// in this case we want to replace the filename with the alias name
		if(isset($alias_flipped[$file['Filepath']]))
		{
			$index = $alias_flipped[$file['Filepath']];
			$files[$index]['Filename'] = substr($GLOBALS['alias'][$index], 1, strlen($GLOBALS['alias'][$index]) - 2);
		}
	}
	
	// pick out the value for the field to sort by
	if(isset($files[$index][$_REQUEST['order']]))
	{
		$order_keys_values[] = $files[$index][$_REQUEST['order']];
	}
	else
	{
		$order_keys_values[] = 'z';
	}
}

// only order it if the database is not already going to order it
// this will unlikely be used when the database is in use
if($_REQUEST['order'] != $_REQUEST['order_by'])
{
	if(isset($order_keys_values[0]) && is_numeric($order_keys_values[0]))
		$sorting = SORT_NUMERIC;
	else
		$sorting = SORT_STRING;
	
	array_multisort($files, SORT_ASC, $sorting, $order_keys_values);
}

$smarty->assign('files', $files);

$smarty->assign('total_count', $count);

$smarty->assign('error', $error);

// set select variables in template
// set them here because the keys in the list array are recursive
if(isset($_SESSION['select']))
	$smarty->assign('select', $_SESSION['select']);

$smarty->assign('templates', $GLOBALS['templates']);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	header('Content-Type: ' . getMime($GLOBALS['templates']['TEMPLATE_SELECT']));
	$smarty->display($GLOBALS['templates']['TEMPLATE_SELECT']);
}


?>