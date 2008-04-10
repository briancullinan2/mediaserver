<?php

// load template
require_once '../include/common.php';
require_once 'template.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
$template =& new Template(SITE_TEMPLATE, 'remove', SITE_DEFAULT);

// this will be appended to throughout the session
$error = '';

if(isset($_REQUEST['submit']))
{
	// if rss parameters are not specify display builder
	$template->setFile('RSS', 'rss.xml');
	$template->setBlock('RSS', 'RSS_XML');
	$template->setBlock('RSS', 'CHANNEL');
	$template->setBlock('RSS', 'ITEM');
		

	// set up limit
	$limit_str = '';
	if(isset($_REQUEST['lim']) && is_numeric($_REQUEST['lim']))
	{
		$limit_str = 'LIMIT 0,' . $_REQUEST['lim'];
	}
	
	// add category
	if(!isset($_REQUEST['cat']))
	{
		$_REQUEST['cat'] = 'db_file';
	}
	
	// add includes
	$where_includes = get_column_where($_REQUEST['cat']);
		
	$where = '';
	if($where != '' && $where_includes != '') { $where .= ' AND ' . $where_includes; }
	elseif($where_includes != '') { $where = $where_includes; }
	
	$props = array(
			'WHERE' => $where,
			'OTHER' => $limit_str
	);
	
	if($where == '')
	{
		unset($props['WHERE']);
	}
	
	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);
	
	// only do this section if error is still blank
	if($error == '')
	{
		
		if( isset($_REQUEST['cat']) )
		{
			$name = constant($_REQUEST['cat'] . '::NAME');
		}
		else
		{
			$name = 'All';
		}

		// loop though and create items
		foreach($files as $i => $file)
		{
			$template->setVar('TITLE', htmlspecialchars($file['Filepath']));

			$template->setVar('DESCRIPTION', '');
			
			$template->setVar('LINK', htmlspecialchars(SITE_HTMLPATH . SITE_PLUGINS . 'file.php?cat=' . $_REQUEST['cat'] . '&id=' . $file['id']));
			
			$template->fparse('ITEMS', 'ITEM', true);
		}
		
		$template->setVar('CATEGORY', $name);
		
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
		
		// parse the channel
		$template->fparse('CHANNELS', 'CHANNEL', true);
		
		// finally parse the RSS feed
		$template->fparse('OUTPUT', 'RSS_XML');
		
		// set the header so the browser can recognize it
		header('Content-Type: application/rss-xml');
		
	}
}
// display rss builder if there is an error
else
{

	// output rss builder
	$template->setFile('OUTPUT', 'rss-builder.html');
	
	$template->setVar('ERROR', $error);

	// make a list of all the modules
	$html_modules = '';
	foreach($GLOBALS['modules'] as $i => $module)
	{
		$html_modules .= '<option value="' . $module . '">' . get_class_const($module, 'NAME') . '</option>';
	}
	
	$template->setVar('MODULES', $html_modules);
}

// output entire template
print $template->fparse('out', 'OUTPUT');
	
	
	
// just generates a where statement for each column
function get_column_where($module)
{
	
	$columns = call_user_func(array($module, 'DETAILS'), 10);
	
	$where_include = '';
	if(isset($_REQUEST['includes']) && $_REQUEST['includes'] != '')
	{
		$regexp = $_REQUEST['includes'];
		
		$where_include .= '(';
		foreach($columns as $i => $column)
		{
			$columns[$i] .= ' REGEXP "' . $regexp . '"';
		}
		$where_include .= join(' OR ', $columns) . ')';
	}
	return $where_include;
}


?>