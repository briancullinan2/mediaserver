<?php
// query the database based on search stored in session

function register_list()
{
	return array(
		'name' => 'list',
		'description' => 'Allow users to download different types of lists of files they have selected, such as RSS, XML, and M3U.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true
	);
}

function setup_list()
{
	// get all the possible types for a list from templates directory
	$type_files = array();
	$files = fs_file::get(array('dir' => LOCAL_ROOT . LOCAL_BASE, 'limit' => 32000), $count, true);
	if(is_array($files))
	{
		foreach($files as $i => $type_file)
		{
			if (!is_dir(str_replace('/', DIRECTORY_SEPARATOR, $files[$i]['Filepath'])))
				$type_files[] = $files[$i]['Filepath'];
		}
	}
	
	if(LOCAL_TEMPLATE != LOCAL_BASE)
	{
		$files = fs_file::get(array('dir' => LOCAL_ROOT . LOCAL_TEMPLATE, 'limit' => 32000), $count, true);
		if(is_array($files))
		{
			foreach($files as $i => $type_file)
			{
				if (!is_dir(str_replace('/', DIRECTORY_SEPARATOR, $files[$i]['Filepath'])))
					$type_files[] = $files[$i]['Filepath'];
			}
		}
	}
	
	$types = array();
	foreach($type_files as $i => $type_file)
	{
		// read first line of file to check if it is a list tag
		$fp = @fopen($type_file, 'r');
		$line = fgets($fp, BUFFER_SIZE); // unlikely that it will ever be longer then this
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
	$GLOBALS['lists'] = $types;
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
