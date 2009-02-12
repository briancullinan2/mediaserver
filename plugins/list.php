<?php

// query the database based on search stored in session


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

include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'type.php';

// get these listed items over the ones saved in the session!
if(!isset($_REQUEST['list']) && isset($_SESSION['selected']))
{
	$_REQUEST['item'] = join(',', $_SESSION['selected']);
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

// select type of output
if(!isset($_REQUEST['type']) || !isset($types[$_REQUEST['type']]))
	$_REQUEST['type'] = 'rss';

// make select call
$files = call_user_func_array($_REQUEST['cat'] . '::get', array($mysql, $_REQUEST, &$count, &$error));

// get all the other information from other modules
foreach($files as $index => $file)
{
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && call_user_func_array($module . '::handles', array($file['Filepath'])))
		{
			$return = call_user_func_array($module . '::get', array($mysql, array('file' => $file['Filepath']), &$tmp_count, &$error));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}
}

$smarty->assign('files', $files);


header('Cache-Control: no-cache');
if($_REQUEST['type'] == 'rss')
{
	header('Content-Type: application/rss+xml');
}
elseif($_REQUEST['type'] == 'wpl')
{
	header('Content-Type: application/vnd.ms-wpl');
}
else
{
	header('Content-Type: ' . getMime($types[$_REQUEST['type']]['file']));
}


// output entire template
// if this is the file being called as opposed to an include
// this is how we implement FRAME functionality! very tricky
// this makes the caller in charge of using the output whereever it wants
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty->display($types[$_REQUEST['type']]['file']);
	
	


?>