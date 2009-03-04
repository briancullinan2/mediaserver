<?php

// type selector for list.php


// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load template to create output
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty();
$smarty->compile_dir = LOCAL_ROOT . 'templates_c' . DIRECTORY_SEPARATOR;

if(isset($_REQUEST['type_select']))
{
	// redirect to list with type in the request
}

$count = 0;
$error = '';
// get all the possible types for a list from templates directory
$type_files = array();
$files = fs_file::get(NULL, array('dir' => LOCAL_ROOT . LOCAL_DEFAULT, 'limit' => 32000), $count, $error, true);
foreach($files as $i => $type_file)
{
	if (!is_dir(str_replace('/', DIRECTORY_SEPARATOR, $files[$i]['Filepath'])))
		$type_files[] = $files[$i]['Filepath'];
}

if(LOCAL_TEMPLATE != LOCAL_DEFAULT)
{
	$files = fs_file::get(NULL, array('dir' => LOCAL_ROOT . LOCAL_TEMPLATE, 'limit' => 32000), $count, $error, true);
	foreach($files as $i => $type_file)
	{
		if (!is_dir(str_replace('/', DIRECTORY_SEPARATOR, $files[$i]['Filepath'])))
			$type_files[] = $files[$i]['Filepath'];
	}
}

$types = array();
foreach($type_files as $i => $type_file)
{
	// read first line of file to check if it is a list tag
	$fp = fopen($type_file, 'r');
	$line = fgets($fp, 128); // unlikely that it will ever be longer then this
	fclose($fp);
	
	// check if it is LIST tag
	$result = preg_match('/\{\*\s+LIST\s+(.*)\s+\*\}.*/', $line, $matches);
	
	if($result == true)
	{
		$args = parseCommandArgs($matches[1]);
		// get filename without extension
		$type = substr(basename($type_file), 0, strrpos(basename($type_file), '.'));
		$types[$type] = array('file' => $type_file, 'encoding' => $args[0], 'name' => $args[1]);
	}
}

$smarty->assign('types', $types);

$smarty->assign('templates', $GLOBALS['templates']);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty->display($GLOBALS['templates']['TEMPLATE_TYPE']);

?>