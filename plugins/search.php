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
	if($column = 'ALL')
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
	$save['cat'] = @$_REQUEST['cat'];
	$save['search'] = @$_REQUEST['search'];
	$save['dir'] = @$_REQUEST['dir'];
	$save['order_by'] = @$_REQUEST['order_by'];

	return $save;
}

function output_search($request)
{
	if(isset($_SESSION['search']))
		register_output_vars('search', $_SESSION['search']);
}

?>