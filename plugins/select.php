<?php

// handle selecting of files


// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;
	
// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

$error = '';

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

// initialize properties for select statement
$props = array();

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

$columns = call_user_func(array($_REQUEST['cat'], 'columns'));

// set a show in the request and do validation!
if( !isset($_REQUEST['start']) || !is_numeric($_REQUEST['start']) || $_REQUEST['start'] < 0 )
	$_REQUEST['start'] = 0;
if( !isset($_REQUEST['limit']) || !is_numeric($_REQUEST['limit']) || $_REQUEST['limit'] < 0 )
	$_REQUEST['limit'] = 15;
if( !isset($_REQUEST['order_by']) || !in_array($_REQUEST['order_by'], $columns) )
	$_REQUEST['order_by'] = 'Filepath';
if( !isset($_REQUEST['direction']) || ($_REQUEST['direction'] != 'ASC' && $_REQUEST['direction'] != 'DESC') )
	$_REQUEST['direction'] = 'ASC';
	
$props['OTHER'] = ' ORDER BY ' . $_REQUEST['order_by'] . ' ' . $_REQUEST['direction'] . ' LIMIT ' . $_REQUEST['start'] . ',' . $_REQUEST['limit'];

// add where includes
if(isset($_REQUEST['includes']) && $_REQUEST['includes'] != '')
{
	$props['WHERE'] = '';
	
	// incase an aliased path is being searched for replace it here too!
	if(USE_ALIAS == true) $_REQUEST['includes'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $_REQUEST['includes']);
	$regexp = addslashes(addslashes($_REQUEST['includes']));
	
	// add a regular expression matching for each column in the table being searched
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
	//$_REQUEST['dir'] = stripslashes($_REQUEST['dir']);
	if($_REQUEST['dir'] == '') $_REQUEST['dir'] = DIRECTORY_SEPARATOR;
	// this is necissary for dealing with windows and cross platform queries coming from templates
	//  yes: the template should probably handle this by itself, but this is convenient and easy
	//   it is purely for making all the paths look prettier
	if($_REQUEST['dir'][0] == '/' || $_REQUEST['dir'][0] == '\\') $_REQUEST['dir'] = realpath('/') . substr($_REQUEST['dir'], 1);
	
	// replace aliased path with actual path
	if(USE_ALIAS == true) $_REQUEST['dir'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $_REQUEST['dir']);
	
	// only search for file if is valid dir
	if(realpath($_REQUEST['dir']) !== false && is_dir(realpath($_REQUEST['dir'])))
	{
		// make sure directory is in the database
		$dirs = call_user_func(array('db_file', 'get'), $mysql, array('WHERE' => 'Filepath = "' . addslashes($_REQUEST['dir']) . '"'));
		
		// top level directory / should always exist
		if($_REQUEST['dir'] == realpath('/') || count($dirs) > 0)
		{
			if(!isset($props['WHERE'])) $props['WHERE'] = '';
			elseif($props['WHERE'] != '') $props['WHERE'] .= ' AND ';
			
			// if the includes is blank then only show files from current directory
			if(!isset($_REQUEST['includes']))
			{
				if(isset($_REQUEST['dirs_only']))
					$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($_REQUEST['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '$"';
				else
					$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($_REQUEST['dir'])) . '[^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . '?$"';
			}
			// show all results underneath directory
			else
			{
				if(isset($_REQUEST['dirs_only']))
					$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($_REQUEST['dir'])) . '([^' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ']+' . addslashes(addslashes(DIRECTORY_SEPARATOR)) . ')*$"';
				else
					$props['WHERE'] .= 'Filepath REGEXP "^' . addslashes(addslashes($_REQUEST['dir'])) . '"';
			}
		}
		else
		{
			// set smarty error
			$error = 'Directory does not exist.';
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
	$_REQUEST['order'] = $_REQUEST['order_by'];

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
				$return = call_user_func(array($module, 'get'), $mysql, array('WHERE' => 'Filepath = "' . addslashes($file['Filepath']) . '"'));
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
$result = $mysql->get(constant($_REQUEST['cat'] . '::DATABASE'), $props);

$smarty->assign('total_count', intval($result[0]['count(*)']));

$smarty->assign('error', $error);

// set select variables in template
// set them here because the keys in the list array are recursive
if(isset($_SESSION['select']))
	$smarty->assign('select', $_SESSION['select']);

$smarty->assign('templates', $templates);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	header('Content-Type: ' . getMime($templates['TEMPLATE_SELECT']));
	$smarty->display($templates['TEMPLATE_SELECT']);
}


?>