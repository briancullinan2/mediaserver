<?php

// query the database based on search stored in session


// load template
require_once dirname(__FILE__) . '/../include/common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// load template to create output
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

include_once 'type.php';

// get these listed items over the ones saved in the session!
if(isset($_REQUEST['list']))
{
	$selected = array();
	
	if(isset($_REQUEST['item']))
	{
		if(is_string($_REQUEST['item']))
		{
			$selected = split(',', $_REQUEST['item']);
		}
		elseif(is_array($_REQUEST['item']))
		{
			foreach($_REQUEST['item'] as $id => $value)
			{
				if(($value == 'on' || $_REQUEST['select'] == 'All') && !in_array($id, $selected))
				{
					$selected[] = $id;
				}
				elseif(($value == 'off' || $_REQUEST['select'] == 'None') && ($key = array_search($id, $selected)) !== false)
				{
					unset($selected[$key]);
				}
			}
		}
	}
	
	if(isset($_REQUEST['on']))
	{
		$_REQUEST['on'] = split(',', $_REQUEST['on']);
		foreach($_REQUEST['on'] as $i => $id)
		{
			if(!in_array($id, $selected) && $id != '')
			{
				$selected[] = $id;
			}
		}
	}
	
	if(isset($_REQUEST['off']))
	{
		$_REQUEST['off'] = split(',', $_REQUEST['off']);
		foreach($_REQUEST['off'] as $i => $id)
		{
			if(($key = array_search($id, $selected)) !== false)
			{
				unset($selected[$key]);
			}
		}
	}
	
	// make sure all selected items are numerics
	foreach($selected as $i => $value) if(!is_numeric($value)) unset($selected[$i]);
	$selected = array_values($selected);
	// use the session stuff instead
	if(count($selected) == 0) $selected = $_SESSION['selected'];

}
elseif(isset($_SESSION['selected']))
{
	$selected = $_SESSION['selected'];
}

// initialize properties for select statement
$props = array();

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

// select type of output
if(!isset($_REQUEST['type']) || !isset($types[$_REQUEST['type']]))
	$_REQUEST['type'] = 'rss';

// set up limit
if(!isset($_REQUEST['lim']) || !is_numeric($_REQUEST['lim']))
	$_REQUEST['lim'] = -1;

if($_REQUEST['lim'] > 0)
{
	$props['OTHER'] = 'LIMIT 0,' . $_REQUEST['lim'];
}
else
{
	if(isset($_REQUEST['start']) && is_numeric($_REQUEST['start']))
	{
		$props['OTHER'] = 'ORDER BY Filepath LIMIT ' . $_REQUEST['start'] . ',15';
	}
	else
	{
		$props['OTHER'] = 'ORDER BY Filepath LIMIT 0,15';
	}
}

// add where includes
if((isset($selected) && count($selected) > 0) || isset($_REQUEST['selected_only']))
{
	if(isset($selected) && count($selected) > 0)
		$props['WHERE'] = 'id=' . join(' OR id=', $selected);
	// if we only want selected files this will give an empty list if there aren't any!!!1
	elseif(isset($_REQUEST['selected_only']))
		// id=0 is used because no files exist with that id
		$props['WHERE'] = 'id=0';
	unset($props['OTHER']);
}
else
{
	// this returns some files based on other input if there aren't any selected
	$columns = call_user_func(array($_REQUEST['cat'], 'columns'));
	
	if(isset($_REQUEST['includes']) && $_REQUEST['includes'] != '')
	{
		$props['WHERE'] = '';
		
		$regexp = addslashes($_REQUEST['includes']);
		
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

// get all the other information from other modules
foreach($files as $index => $file)
{
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && call_user_func(array($module, 'handles'), $file['Filepath']))
		{
			$return = call_user_func(array($module, 'get'), $mysql, array('WHERE' => 'Filepath = "' . $file['Filepath'] . '"'));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}
}

$smarty->assign('files', $files);


if($_REQUEST['type'] == 'rss')
{
	header('Content-Type: application/rss+xml');
}
elseif($_REQUEST['type'] == 'm3u')
{
	// set the header so the browser can recognize it
	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: attachment; filename="' . (isset($_REQUEST['filename'])?$_REQUEST['filename']:constant($_REQUEST['cat'] . '::NAME')) . '.m3u"'); 
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
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display($types[$_REQUEST['type']]['file']);
	
	


?>