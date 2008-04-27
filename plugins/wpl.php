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
	$template->setFile('WPL', 'wpl.xml');
	$template->setBlock('WPL', 'WPL_XML');
	$template->setBlock('WPL', 'ITEM');
		

	// set up limit
	$limit_str = '';
	if(isset($_REQUEST['lim']) && is_numeric($_REQUEST['lim']) && $_REQUEST['lim'] > 0)
	{
		$limit_str = 'LIMIT 0,' . $_REQUEST['lim'];
	}
	else
	{
		$_REQUEST['lim'] = -1;
		if(isset($_REQUEST['show']) && is_numeric($_REQUEST['show']))
		{
			$limit_str = 'ORDER BY Filepath LIMIT ' . $_REQUEST['show'] . ',15';
		}
		else
		{
			$limit_str = 'ORDER BY Filepath LIMIT 0,15';
		}
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
	
	if(isset($_REQUEST['list']))
	{
		if(count($_POST) > 0)
		{
			header('Location: ' . $_SERVER['REQUEST_URI'] . '&list=');
			// don't exit so it will still save changes to the list
		}
		else
		{
			$props['WHERE'] = 'id=' . join(' OR id=', $_SESSION['selected']);
			unset($props['OTHER']);
			$_REQUEST['lim'] = 0;
		}
	}
	
	if($props['WHERE'] == '')
	{
		unset($props['WHERE']);
	}
	
	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql, $props);
	
	// only do this section if error is still blank
	if($_REQUEST['lim'] != -1)
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
		
		// finally parse the RSS feed
		$template->fparse('OUTPUT', 'WPL_XML');
		
		// set the header so the browser can recognize it
		header('Content-Type: application/vnd.ms-wpl');
		header('Content-Disposition: attachment; filename="' . $name . '.wpl"'); 
		
	}
	else
	{
			
		// process post
		if(isset($_POST['item']))
		{
			if(!isset($_SESSION['selected']))
				$_SESSION['selected'] = array();
				
			foreach($_POST['item'] as $id => $value)
			{
				if($value == 'on' && !in_array($id, $_SESSION['selected']))
				{
					$_SESSION['selected'][] = $id;
				}
				elseif($value == 'off')
				{
					foreach($_SESSION['selected'] as $i => $sub_id)
					{
						if($id == $sub_id)
							unset($_SESSION['selected'][$i]);
					}
				}
			}
		}
		// clear post and redirect back to self, this is so there is no stupid post messages
		if(count($_POST) > 0)
		{
			header('Location: ' . $_SERVER['REQUEST_URI'] . (isset($_REQUEST['list'])?'&list=':''));
			exit();
		}
		
		// set a show in the request
		if( !isset($_REQUEST['show']) || (isset($_REQUEST['show']) && !is_numeric($_REQUEST['show'])) )
			$_REQUEST['show'] = 0;
		
		// there is no limit so bring up selector
		$template->setFile('WPL_LIST', 'rss-list.html');
		$template->setBlock('WPL_LIST', 'ITEM', 'ITEMS');
		
		unset($props['OTHER']);
		$props['SELECT'] = 'count(*)';
		
		// get count
		$result = $mysql->get(get_class_const($_REQUEST['cat'], 'DATABASE'), $props);
		
		$template->setVar('COUNT', $result[0]['count(*)']);
		
		$template->setVar('RANGE', $_REQUEST['show'] . ' to ' . (($_REQUEST['show']+15<$result[0]['count(*)'])?$_REQUEST['show']+15:$result[0]['count(*)']));
		
		// display the 15 results
		foreach($files as $i => $file)
		{
			$template->setVar('ID', $file['id']);
			
			// set the on or off for each item
			if(isset($_SESSION['selected']) && in_array($file['id'], $_SESSION['selected']))
			{
				$template->setVar('ON_CHECKED', 'checked="checked"');
				$template->setVar('OFF_CHECKED', '');
			}
			else
			{
				$template->setVar('OFF_CHECKED', 'checked="checked"');
				$template->setVar('ON_CHECKED', '');
			}
			
			$template->setVar('TITLE', htmlspecialchars($file['Filepath']));
			
			$template->fparse('ITEMS', 'ITEM', true);
		}
		
		// set the link to show different parts of the list
		$request = $_GET;
		unset($request['PHPSESSID']);
		
		if($_REQUEST['show'] > 0)
		{
			$request['show'] = $_REQUEST['show'] - 15;
			$request_str = '';
			foreach($request as $key => $value) $request_str .= '&amp;' . $key . '=' . $value;
			$request_str = substr($request_str, 5, strlen($request_str) - 5);
			$template->setVar('LINK_PREV', '<a href="' . SITE_HTMLPATH . SITE_PLUGINS . 'wpl.php?' . $request_str . '">Prev</a>');
		}
			
		if($_REQUEST['show'] < $result[0]['count(*)'] - 15)
		{
			$request['show'] = $_REQUEST['show'] + 15;
			$request_str = '';
			foreach($request as $key => $value) $request_str .= '&amp;' . $key . '=' . $value;
			$request_str = substr($request_str, 5, strlen($request_str) - 5);
			$template->setVar('LINK_NEXT', '<a href="' . SITE_HTMLPATH . SITE_PLUGINS . 'wpl.php?' . $request_str . '">Next</a>');
		}
		
		$template->setVar('BACK', '/' . SITE_PLUGINS . 'wpl.php');
		
		// finally parse the RSS lister
		$template->fparse('OUTPUT', 'WPL_LIST');
		
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