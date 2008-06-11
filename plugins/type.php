<?php

// type selector for list.php


// load template
require_once '../include/common.php';

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

if(isset($_REQUEST['type_select']))
{
	// redirect to list with type in the request
}

// get all the possible types for a list from templates directory
$type_files = db_file::get(NULL, array('DIR' => SITE_TEMPLATE));

$types = array();
foreach($type_files as $i => $type_file)
{
	// read first line of file to check if it is a list tag
	$fp = fopen(SITE_TEMPLATE . $type_file, 'r');
	$line = fgets($fp, 128); // unlikely that it will ever be longer then this
	fclose($fp);
	
	// check if it is LIST tag
	$result = preg_match('/\{\*\s+LIST\s+(.*)\s+\*\}.*/', $line, $matches);
	
	if($result == true)
	{
		$args = parseCommandArgs($matches[1]);
		// get filename without extension
		$type = substr($type_file, 0, strrpos($type_file, '.'));
		$types[$type] = array('file' => $type_file, 'encoding' => $args[0], 'name' => $args[1]);
	}
}

$smarty->assign('types', $types);

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_TEMPLATE . 'type.html');

?>