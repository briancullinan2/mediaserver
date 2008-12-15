<?php

// type selector for list.php


// load template
require_once dirname(__FILE__) . '/../include/common.php';

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

if(isset($_REQUEST['type_select']))
{
	// redirect to list with type in the request
}

// get all the possible types for a list from templates directory
$type_files = db_file::get(NULL, array('DIR' => SITE_LOCALROOT . SITE_DEFAULT));
foreach($type_files as $i => $type_file)
{
	$type_files[$i] = SITE_LOCALROOT . SITE_DEFAULT . $type_files[$i];
}

$type_files2 = db_file::get(NULL, array('DIR' => SITE_LOCALROOT . SITE_TEMPLATE));
foreach($type_files2 as $i => $type_file)
{
	$type_files2[$i] = SITE_LOCALROOT . SITE_TEMPLATE . $type_files2[$i];
}

$type_files = array_unique(array_merge($type_files, $type_files2));

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

$smarty->assign('templates', $templates);
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display($templates['TEMPLATE_TYPE']);

?>