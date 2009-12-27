<?php

// Variables Used:
//  files, total_count, error, select
// Shared Variables:
//  columns, templates

define('SELECT_PRIV', 				1);

// handle selecting of files

// load stuff we might need
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < SELECT_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_ROOT . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . SELECT_PRIV);
	
	exit();
}
	
// get all columns from every module
$columns = getAllColumns();

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
	
	// unset the request stuff because we don't want it to affect what items are retrieved just save it
	//  to select specific items, the select var should be left off in the query
	unset($_REQUEST['on']);
	unset($_REQUEST['off']);
	unset($_REQUEST['item']);
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']) || constant($_REQUEST['cat'] . '::INTERNAL') == true)
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

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
$files = call_user_func_array($_REQUEST['cat'] . '::get', array($_REQUEST, &$total_count, &$error));

if($files === false)
{
	$error = 'The database returned null!';
	$files = array();
}

$order_keys_values = array();

// the ids module will do the replacement of the ids
if(count($files) > 0)
{
	$files = db_ids::get(array('cat' => $_REQUEST['cat']), $tmp_count, $tmp_error, $files);
	$files = db_users::get(array(), $tmp_count, $tmp_error, $files);
}

// get all the other information from other modules
foreach($files as $index => $file)
{
	$tmp_request = array();
	$tmp_request['file'] = $file['Filepath'];

	// merge with tmp_request to look up more information
	$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
	
	// short results to not include information from all the modules
	if(!isset($_REQUEST['short']))
	{
		// merge all the other information to each file
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if(USE_DATABASE == false || ($module != $_REQUEST['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($file['Filepath'], $file))))
			{
				$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count, &$tmp_error));
				if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
			}
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

$GLOBALS['smarty']->assign('files', $files);

$GLOBALS['smarty']->assign('total_count', $total_count);

$GLOBALS['smarty']->assign('error', $error);

// set select variables in template
// set them here because the keys in the list array are recursive
if(isset($_SESSION['select']))
	$GLOBALS['smarty']->assign('select', $_SESSION['select']);

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_SELECT']) == 'php')
		include $GLOBALS['templates']['TEMPLATE_SELECT'];
	else
	{
		header('Content-Type: ' . getMime($GLOBALS['templates']['TEMPLATE_SELECT']));
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_SELECT']);
	}
}


?>