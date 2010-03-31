<?php
// query the database based on search stored in session

function register_list()
{
	return array(
		'name' => 'Playlist',
		'description' => 'Allow users to download different types of lists of files they have selected, such as RSS, XML, and M3U.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true
	);
}

function setup_list()
{
	$GLOBALS['lists'] = array();
	
	// get all the possible types for a list from templates directory
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['lists']))
		{
			foreach($template['lists'] as $i => $list)
			{
				if(file_exists(LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php'))
				{
					include_once LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php';
					
					if(function_exists('register_' . $name . '_' . $list))
						$GLOBALS['lists'][$list] = call_user_func_array('register_' . $name . '_' . $list, array());
				}
			}
		}
	}
}

function validate_list($request)
{
	if(!isset($GLOBALS['lists'])) setup_list();
	if(isset($request['list']) && in_array($request['list'], $GLOBALS['lists']))
		return $request['list'];
}

function output_list($request)
{
	$request['cat'] = validate_cat($request);
	
	$request['list'] = validate_list($request);
	
	// if there isn't a list specified show the list template
	
	header('Cache-Control: no-cache');
	if($_REQUEST['list'] == 'rss')
	{
		header('Content-Type: application/rss+xml');
	}
	elseif($_REQUEST['list'] == 'wpl')
	{
		header('Content-Type: application/vnd.ms-wpl');
	}
	else
	{
		header('Content-Type: ' . getMime($types[$_REQUEST['list']]['file']));
	}
	
	// set some output variables
	register_output_vars('list', $request['list']);
	if(isset($_SESSION['select']['selected'])) register_output_vars('selected', $_SESSION['select']['selected']);
	
	// use the select.php plugin file selector to generate a list from the request
	//   should be the same list, and it will register the files output
	output_select();
	
	//   then the list template will be used
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	{
		if(getExt($types[$_REQUEST['list']]['file']) == 'php')
			@include $types[$_REQUEST['list']]['file'];
		else
			$GLOBALS['smarty']->display($types[$_REQUEST['list']]['file']);
	}
	
}
