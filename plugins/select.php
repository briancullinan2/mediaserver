<?php

// handle selecting of files

function register_select()
{
	return array(
		'name' => 'select',
		'description' => 'Allows users to select files and saves the selected files in their session and profile.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('item', 'on', 'off')
	);
}

// combine and manipulate the IDs from a request
//  this function looks for item, on, and off, variables in a request and generates a list of IDs
function validate_select($request)
{
	if(isset($request['select']))
	{
		if($request['select'] == 'All' || $request['select'] == 'None')
			return $request['select'];
		else
			return 'None';
	}
}

function validate_selected($request)
{
	if(!isset($request['selected']) || !is_array($request['selected']))
		$selected = validate_item($request);
	else
		$selected = $request['selected'];
		
	foreach($selected as $i => $id)
	{
		if(!is_numeric($id))
			unset($selected[$i]);
	}
	
	$request['on'] = validate_on($request);
	
	$selected = array_merge($selected, $request['on']);
	$selected = array_unique($selected);
	
	$request['off'] = validate_off($request);
	$selected = array_diff($selected, $request['off']);
	
	$request['id'] = validate_id($request);
	if(isset($request['id']))
		$selected = array($request['id']);
	
	return array_values($selected);
}

// return an array of IDs for the selected value
function validate_item($request)
{
	$select = validate_select($request);
	
	$selected = array();
	
	if(isset($request['item']) && is_string($request['item']))
	{
		$selected = split(',', $request['item']);
	}
	elseif(isset($request['item']) && is_array($request['item']))
	{
		foreach($request['item'] as $id => $value)
		{
			if(($value == 'on' || (isset($select) && $select == 'All')) && !in_array($id, $selected))
			{
				$selected[] = $id;
			}
			elseif(($value == 'off' || (isset($select) && $select == 'None')) && ($key = array_search($id, $selected)) !== false)
			{
				unset($selected[$key]);
			}
		}
	}
	
	return array_values($selected);
}

function validate_id($request)
{
	if(isset($request['id']) && is_numeric($request['id']))
		return $request['id'];
}

function validate_on($request)
{

	if(isset($request['on']))
	{
		$request['on'] = split(',', $request['on']);
		foreach($request['on'] as $i => $id)
		{
			if(!is_numeric($id))
				unset($request['on'][$i]);
		}
		return array_values($request['on']);
	}
	return array();
}

function validate_off($request)
{
	
	if(isset($request['off']))
	{
		$request['off'] = split(',', $request['off']);
		foreach($request['off'] as $i => $id)
		{
			if(!is_numeric($id))
				unset($request['off'][$i]);
		}
		return array_values($request['off']);
	}
	return array();
}

function validate_short($request)
{
	if(isset($request['short']) && is_bool($request['short']))
		return $request['short'];
	else
		return false;
}

function validate_dir($request)
{
	if(isset($request['dir']))
	{
		if(USE_ALIAS == true)
			$tmp = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['dir']);
		if(is_dir(realpath($tmp)))
			return $request['dir'];
	}
}

// passes a validated request to the session select for processing and saving
//  the array of settings returned from this is stored in $_SESSION[<name>] = session_select($request);
function session_select($request)
{
	$save = array();
	$save['on'] = @$request['on'];
	$save['off'] = @$request['off'];
	$save['item'] = @$request['item'];
	
	return $save;
}

function output_select($request)
{
	// set up required request variables
	$request['cat'] = validate_cat($request);
	$request['start'] = validate_start($request);
	$request['order_by'] = validate_order_by($request);
	$request['direction'] = validate_direction($request);
	
	// make select call
	$files = call_user_func_array($request['cat'] . '::get', array($request, &$total_count, &$error));
	
	$order_keys_values = array();
	
	// the ids module will do the replacement of the ids
	if(count($files) > 0)
	{
		$files = db_ids::get(array('cat' => $request['cat']), $tmp_count, $tmp_error, $files);
		$files = db_users::get(array(), $tmp_count, $tmp_error, $files);
	}
	
	// get all the other information from other modules
	foreach($files as $index => $file)
	{
		$tmp_request = array();
		$tmp_request['file'] = $file['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
		
		// short results to not include information from all the modules
		if(!isset($request['short']) || $request['short'] == false)
		{
			// merge all the other information to each file
			foreach($GLOBALS['modules'] as $i => $module)
			{
				if(USE_DATABASE == false || ($module != $request['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($file['Filepath'], $file))))
				{
					$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count, &$tmp_error));
					if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
				}
			}
		}
			
		// pick out the value for the field to sort by
		if(isset($files[$index][$request['order_by']]))
		{
			$order_keys_values[] = $files[$index][$request['order_by']];
		}
		else
		{
			$order_keys_values[] = 'z';
		}
	}
	
	// only order it if the database is not already going to order it
	// this will unlikely be used when the database is in use
	if(USE_DATABASE == false)
	{
		if(isset($order_keys_values[0]) && is_numeric($order_keys_values[0]))
			$sorting = SORT_NUMERIC;
		else
			$sorting = SORT_STRING;
		
		array_multisort($files, SORT_ASC, $sorting, $order_keys_values);
	}
	
	register_output_vars('files', $files);
	
	register_output_vars('total_count', $total_count);
}

