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
	// output search information
	if(isset($_SESSION['search']))
		register_output_vars('search', $_SESSION['search']);

	// replace search results with highlight
	$search_regexp = array();

	// get columns being searched
	$columns = call_user_func($request['cat'] . '::columns');
	
	$all_columns = getAllColumns();
	
	// replace each column with search match
	foreach($all_columns as $i => $column)
	{
		if(in_array($column, $columns))
		{
			// select input for individual columns
			if(isset($GLOBALS['output']['search']['search_' . $column]))
				$query = $GLOBALS['output']['search']['search_' . $column];
			else
				$query = $GLOBALS['output']['search']['search'];
				
			// replace with search
			if(substr($query, 0, 1) == '/' && substr($query, -1) == '/')
			{
				$search_regexp[$column] = $query . 'ie';
			}
			elseif(substr($query, 0, 1) == '"' && substr($query, -1) == '"')
			{
				$search_regexp[$column] = '/' . substr($query, 1, strlen($query) - 2) . '/ie';
			}
			elseif(substr($query, 0, 1) == '=' && substr($query, -1) == '=')
			{
				$search_regexp[$column] = '/^' . substr($query, 1, strlen($query) - 2) . '$/ie';
			}
			else
			{
				$tmp_parts = array_unique(split(' ', stripslashes($query)));
				$search_regexp[$column] = array();
				foreach($tmp_parts as $i => $part)
				{
					if($part != '')
					{
						if($part[0] == '+') $part = substr($part, 1);
						$search_regexp[$column][] = '/' . preg_quote($part) . '/ie';
					}
				}
			}
		}
		else
		{
			$search_regexp[$column] = '//e';
		}
	}
	register_output_vars('search_regexp', $search_regexp);
}

?>