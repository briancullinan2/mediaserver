<?php

// search form for selecting files from the database

/**
 * Implementation of register
 * @ingroup register
 */
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
		'session' => array('search'),
		'alter query' => array('search'),
		'always output' => array('search'),
		'depends on' => 'search',
	);
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_search($settings)
{
	if(setting('admin_alias_enable') == false)
		return array('database');
	else
		return array('database', 'admin_alias');
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, returns any search query, this is validated in the alter_query instead of here
 */
function validate_search($request, $column = 'ALL')
{
	if($column = 'ALL' && isset($request['search']))
		return $request['search'];
	if(isset($request['search_' . $column]))
	{
		// validated in handlers when used
		return $request['search_' . $column];
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts 'AND' or 'OR'
 */
function validate_search_operator($request)
{
	if(isset($request['search_operator']) && ($request['search_operator'] == 'AND' || $request['search_operator'] == 'OR'))
		return $request['search_operator'];
}

/**
 * Implementation of session
 * Saves the previous search information for references
 * @ingroup session
 */
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

/**
 * Determines the type of search a user would like to perform based on the surrounding characters
 * @param search the search query for any column
 * @return 'normal' by default indicating the search string should be tokenized and read for '+' required tokens, '-' excluded tokens, and optional include tokens
 * 'literal' if the search string is surrounded by double quotes
 * 'equal' if the search string is surrounded by equal signs
 * 'regular' for regular expression if the search is surrounded by forward slashes
 */
function search_get_type($search)
{
	if(strlen($search) > 1 && $search[0] == '"' && $search[strlen($search)-1] == '"')
		return 'literal';
	elseif(strlen($search) > 1 && $search[0] == '=' && $search[strlen($search)-1] == '=')
		return 'equal';
	elseif(strlen($search) > 1 && $search[0] == '/' && $search[strlen($search)-1] == '/')
		return 'regular';
	else
		return 'normal';
}

/**
 * For a normal search, get each piece that may be preceeded with a '+' for require or a '-' for exclude
 * @param search the search string
 * @return an associative array containing:
 * 'length' of the query string
 * 'count' of all the pieces
 * 'required' all the pieces preceeded by a '+' in the query string
 * 'excluded' all the pieces to be excluded preceeded by a '-'
 * 'includes' all the pieces that should contain at least 1 include
 */
function search_get_pieces($search)
{
	// loop through search terms and construct query
	$pieces = split(' ', $search);
	$pieces = array_unique($pieces);
	$empty = array_search('', $pieces, true);
	if($empty !== false) unset($pieces[$empty]);
	$pieces = array_values($pieces);
	
	// sort items by inclusive, exclusive, and string size
	// rearrange pieces, but keep track of index so we can sort them correctly
	uasort($pieces, 'termSort');
	$length = strlen(join(' ', $pieces));
	
	// these are the 3 types of terms we can have
	$required = array();
	$excluded = array();
	$includes = array();

	foreach($pieces as $j => $piece)
	{
		if($piece[0] == '+')
			$required[$j] = substr($piece, 1);
		elseif($piece[0] == '-')
			$excluded[$j] = substr($piece, 1);
		else
			$includes[$j] = $piece;
	}
	
	return array(
		'length' => $length,
		'count' => count($pieces),
		'required' => $required,
		'excluded' => $excluded,
		'includes' => $includes
	);
}

/**
 * Generate an SQL query from the pieces
 * @param pieces The pieces from search_get_pieces()
 * @return an associative array of properties of the SQL query, containing COLUMNS, ORDER, and WHERE
 */
function search_get_pieces_query($pieces)
{
	$props = array();
	$props['COLUMNS'] = '';
	$props['ORDER'] = '';
	
	$required = '';
	$excluded = '';
	$includes = '';
	
	foreach($pieces['required'] as $j => $piece)
	{
		if($required != '') $required .= ' AND';
		$required .= ' LOCATE("' . addslashes($piece) . '", {column}) > 0';
		$props['COLUMNS'] .= (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", {column}) > 0) AS result{column_index}' . $j;
		$props['ORDER'] .= 'result{column_index}' . ($pieces['count'] - $j - 1) . ' DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
	}
	
	foreach($pieces['excluded'] as $j => $piece)
	{
		if($excluded != '') $excluded .= ' AND';
		$excluded .= ' LOCATE("' . addslashes($piece) . '", {column}) = 0';
		$props['COLUMNS'] .= (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", {column}) = 0) AS result{column_index}' . $j;
		$props['ORDER'] .= 'result{column_index}' . ($pieces['count'] - $j - 1) . ' DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
	}
	
	foreach($pieces['includes'] as $j => $piece)
	{
		if($includes != '') $includes .= ' OR';
		$includes .= ' LOCATE("' . addslashes($piece) . '", {column}) > 0';
		$props['COLUMNS'] .= (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',(LOCATE("' . addslashes($piece) . '", {column}) > 0) AS result{column_index}' . $j;
		$props['ORDER'] .= 'result{column_index}' . ($pieces['count'] - $j - 1) . ' DESC,' . (isset($props['ORDER'])?$props['ORDER']:'');
	}
	
	$part = '';
	$part .= (($required != '')?(($part != '')?' AND':'') . $required:'');
	$part .= (($excluded != '')?(($part != '')?' AND':'') . $excluded:'');
	$part .= (($includes != '')?(($part != '')?' AND':'') . $includes:'');
	$props['WHERE'] = $part;
	
	return $props;
}

/**
 * Implementation of alter_query
 * Alter a database queries when the search variable is set in the request
 * @ingroup alter_query
 */
function alter_query_search($request, $props)
{
	// do not alter the query if selected is set
	$request['selected'] = validate_selected($request);
	if(isset($request['selected']) && count($request['selected']) > 0 ) return $props;
	
	// they can specify multiple columns to search for the same string
	if(isset($request['columns']))
	{
		$columns = split(',', $request['columns']);
	}
	// search every column for the same string
	else
	{
		$columns = columns($request['cat']);
	}
		
	// array for each column
	$parts = array();
		
	foreach($columns as $i => $column)
	{
		if(isset($request['search_' . $column]))
			$search = $request['search_' . $column];
		elseif(isset($request['search']))
			$search = $request['search'];
		else
			continue;

		$type = search_get_type($search);
		
		// remove characters on either side of input
		if($type != 'normal')
			$search = substr($search, 1, -1);
		
		// incase an aliased path is being searched for replace it here too!
		if(setting('admin_alias_enable') == true && isset($GLOBALS['alias_regexp']))
			$search = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $search);
		
		if($type == 'normal')
		{
			$pieces = search_get_pieces($search);
			$query = search_get_pieces_query($pieces);
		}
		
		// tokenize input
		if($type == 'normal')
		{
			if($request['order_by'] == 'Relevance')
				$props['ORDER'] = 'r_count' . $i . ' ASC,' . (isset($props['ORDER'])?$props['ORDER']:'');
				
			$replaced = str_replace(array('{column}', '{column_index}'), array($column, $i), $query);
			$parts[] = $replaced['WHERE'];
			$props['COLUMNS'] = $replaced['COLUMNS'] . (isset($props['COLUMNS'])?$props['COLUMNS']:'');
			$props['ORDER'] = $replaced['ORDER'] . (isset($props['ORDER'])?$props['ORDER']:'');
			
			$props['COLUMNS'] = (isset($props['COLUMNS'])?$props['COLUMNS']:'') . ',ABS(LENGTH(' . $column . ') - ' . $pieces['length'] . ') as r_count' . $i;
		}
		elseif($type == 'equal')
		{
			$parts[] = $column . ' = "' . addslashes($search) . '"';
		}
		elseif($type == 'regular')
		{
			$parts[] = $column . ' REGEXP "' . addslashes($search) . '"';
		}
		elseif($type == 'literal')
		{
			$parts[] = ' LOCATE("' . addslashes($search) . '", ' . $column . ')';
		}
	}
	if(!isset($props['WHERE']))
		$props['WHERE'] = array();
	if(is_string($props['WHERE']))
		$props['WHERE'] = array($props['WHERE']);
		
	if(count($parts) > 0)
		$props['WHERE'][] = join((isset($request['search_operator'])?(' ' . $request['search_operator'] . ' '):' OR '), $parts);

	return $props;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_search($request)
{
	// output search information
	if(isset($_SESSION['search']))
		register_output_vars('search', $_SESSION['search']);

	// replace search results with highlight
	$search_regexp = array();

	// get columns being searched
	$columns = columns($request['cat']);
	
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
				$query = isset($GLOBALS['output']['search']['search'])?$GLOBALS['output']['search']['search']:'';
				
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
