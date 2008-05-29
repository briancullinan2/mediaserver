<?php

// query the database based on search stored in session


// load template
require_once '../include/common.php';
require_once 'template.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
$template =& new Template(SITE_TEMPLATE, 'remove', SITE_DEFAULT);

// get all the possible types for a list from templates directory
$files = db_file::get(NULL, array('DIR' => SITE_TEMPLATE));

$types = array();
foreach($files as $i => $file)
{
	// read first line of file to check if it is a list tag
	$fp = fopen(SITE_TEMPLATE . $file, 'r');
	$line = fgets($fp, 128); // unlikely that it will ever be longer then this
	fclose($fp);
	
	// check if it is LIST tag
	$result = preg_match('/\<\!--\s+LIST\s+(.*)\s+--\>.*/', $line, $matches);
	
	if($result == true)
	{
		$args = parseCommandArgs($matches[1]);
		// get filename without extension
		$type = substr($file, 0, strrpos($file, '.'));
		$types[$type] = array('file' => $file, 'encoding' => $args[0], 'name' => $args[1]);
	}
}

// select type of output
if(!isset($_REQUEST['type']) || !isset($types[$_REQUEST['type']]))
	$_REQUEST['type'] = 'rss';
	
	
$template->setFile('TYPE', $types[$_REQUEST['type']]['file']);
$template->setBlock('TYPE', 'LIST');
$template->setBlock('TYPE', 'ITEM');


// initialize properties for select statement
$props = array();

// set up limit
if(!isset($_REQUEST['lim']) || !is_numeric($_REQUEST['lim']))
	$_REQUEST['lim'] = -1;

if($_REQUEST['lim'] > 0)
{
	$props['OTHER'] = 'LIMIT 0,' . $_REQUEST['lim'];
}
else
{
	if(isset($_REQUEST['show']) && is_numeric($_REQUEST['show']))
	{
		$props['OTHER'] = 'ORDER BY Filepath LIMIT ' . $_REQUEST['show'] . ',15';
	}
	else
	{
		$props['OTHER'] = 'ORDER BY Filepath LIMIT 0,15';
	}
}


// add category
if(!isset($_REQUEST['cat']))
{
	$_REQUEST['cat'] = 'db_file';
}

// add where includes
if(isset($_SESSION['selected']) && count($_SESSION['selected']) > 0)
{
	$props['WHERE'] = 'id=' . join(' OR id=', $_SESSION['selected']);
	unset($props['OTHER']);
}
else
{
	
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

}


// make select call
$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);


// loop though and create items
foreach($files as $i => $file)
{
	if($types[$_REQUEST['type']]['encoding'] == 'XML')
	{
		$template->setVar('TITLE', htmlspecialchars(basename($file['Filepath'])));
	
		$template->setVar('DESCRIPTION', '');
		
		$template->setVar('LINK', htmlspecialchars(SITE_HTMLPATH . SITE_PLUGINS . 'file.php?cat=' . $_REQUEST['cat'] . '&id=' . $file['id']));
	}
	else
	{
		$template->setVar('TITLE', basename($file['Filepath']));
	
		$template->setVar('DESCRIPTION', '');
		
		$template->setVar('LINK', SITE_HTMLPATH . SITE_PLUGINS . 'file.php?cat=' . $_REQUEST['cat'] . '&id=' . $file['id']);
	}
	
	$template->fparse('ITEMS', 'ITEM', true);
}

$template->setVar('CATEGORY', constant($_REQUEST['cat'] . '::NAME'));

$template->setVar('DESCRIPTION', '');

// remove beginning slash
if(substr($_SERVER['REQUEST_URI'], 0, 1) == '/')
{
	$link = substr($_SERVER['REQUEST_URI'], 1);
}
else
{
	$link = $_SERVER['REQUEST_URI'];
}

$template->setVar('LINK', htmlspecialchars(SITE_HTMLPATH . $link));

// finally parse the RSS feed
$template->fparse('OUTPUT', 'LIST');

if($_REQUEST['type'] == 'rss')
{
	header('Content-Type: application/rss+xml');
}
elseif($_REQUEST['type'] == 'm3u')
{
	// set the header so the browser can recognize it
	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: attachment; filename="' . constant($_REQUEST['cat'] . '::NAME') . '.m3u"'); 
}
elseif($_REQUEST['type'] == 'wpl')
{
	header('Content-Type: application/vnd.ms-wpl');
}


// output entire template
// if this is the file being called as opposed to an include
// this is how we implement FRAME functionality! very tricky
// this makes the caller in charge of using the output whereever it wants
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	print $template->fparse('out', 'OUTPUT');
else
	$template->fparse(basename(__FILE__), 'OUTPUT');
	
	


?>