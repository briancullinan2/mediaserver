<?php
// query the database based on search stored in session

/**
 * Implementation of register
 * @ingroup register
 */
function register_list()
{
	return array(
		'name' => 'Playlist',
		'description' => 'Allow users to download different types of lists of files they have selected, such as RSS, XML, and M3U.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true,
		'depends on' => array('template'),
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_list()
{
}

/**
 * Set up a list of different types of lists that can be outputted from any theme at any time
 * @ingroup setup
 */
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
				if(file_exists(setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php'))
				{
					include_once setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php';
					
					if(isset($GLOBALS['lists'][$list]))
						PEAR::raiseError('List already defined!', E_DEBUG|E_WARN);
					
					if(function_exists('register_' . $name . '_' . $list))
						$GLOBALS['lists'][$list] = call_user_func_array('register_' . $name . '_' . $list, array());
				}
			}
		}
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts any valid list name
 */
function validate_list($request)
{
	if(isset($request['list']) && in_array($request['list'], array_keys($GLOBALS['lists'])))
		return $request['list'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_list($request)
{
	$request['cat'] = validate_cat($request);
	
	$request['list'] = validate_list($request);
	
	// if there isn't a list specified show the list template
	if(!isset($request['list']))
	{
		theme('list');
		
		return;
	}	
	else
	{
		header('Cache-Control: no-cache');
		if($request['list'] == 'rss')
		{
			header('Content-Type: application/rss+xml');
		}
		elseif($request['list'] == 'wpl')
		{
			header('Content-Type: application/vnd.ms-wpl');
		}
		else
		{
			header('Content-Type: ' . getMime($request['list']));
		}
	
		// set some output variables
		register_output_vars('list', $request['list']);
		if(isset($_SESSION['select']['selected'])) register_output_vars('selected', $_SESSION['select']['selected']);
	
		// use the select.php module file selector to generate a list from the request
		//   should be the same list, and it will register the files output
		output_select();
	
		//   then the list template will be used
		theme($request['list']);
	}
}
