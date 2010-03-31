<?php

// search form for selecting files from the database

function register_search()
{
	// create functions for searching
	$columns = getAllColumns();
	foreach($columns as $column)
	{
		$GLOBALS['validate_search_' . $column] = create_function('$request', 'return validate_search($request, \'' . $column . '\');');
	}
	
	return array(
		'name' => 'Database Search',
		'description' => 'Search for files.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('search')
	);
}

function validate_search($request, $column = 'ALL')
{
	if($column = 'ALL' && isset($request['search']))
		return $request['search'];
	if(isset($request['search_' . $column]))
	{
		// validated in modules when used
		return $request['search_' . $column];
	}
}

function session_search($request)
{
	if(isset($_POST['clear_search']) || isset($_GET['clear_search']))
		return array();

	// store this query in the session
	$save = array();
	if(isset($request['cat'])) $save['cat'] = $request['cat'];
	if(isset($request['search'])) $save['search'] = $request['search'];
	foreach($request as $key => $value)
	{
		if(substr($key, 0, 7) == 'search_')
			$save[$key] = $value;
	}
	if(isset($request['dir'])) $save['dir'] = $request['dir'];
	if(isset($request['order_by'])) $save['order_by'] = $request['order_by'];

	return $save;
}

function output_search($request)
{
	if(isset($_SESSION['search']))
		register_output_vars('search', $_SESSION['search']);
		
	$parts = array();
	$search = validate_search($request);
	if($search[0] != '"' && $search[strlen($search)-1] != '"' && $search[0] != '/' && $search[strlen($search)-1] != '/' && $search[0] != '=' && $search[strlen($search)-1] != '=')
	{
		if(isset($search))
		{
			$tmp_parts = array_unique(split(' ', stripslashes($search)));
			foreach($tmp_parts as $i => $part)
			{
				if($part != '')
				{
					if($part[0] == '+') $part = substr($part, 1);
					$parts[] = '/' . preg_quote(htmlspecialchars($part)) . '/i';
				}
			}
		}
	}
	elseif($search[0] == '"' && $search[strlen($search)-1] == '"')
	{
		$parts = array(0 => '/' . preg_quote(substr($search, 1, strlen($search)-2)) . '/i');
	}
	elseif($search[0] == '/' && $search[strlen($search)-1] == '/')
	{
		$parts = array(0 => $search . 'i');
	}
	elseif($search[0] == '=' && $search[strlen($search)-1] == '=')
	{
		$parts = array(0 => '/^' . preg_quote(substr($search, 1, strlen($search)-2)) . '$/i');
	}
	
	if(count($parts) != 0)
		register_output_vars('parts', $parts);
}

?>