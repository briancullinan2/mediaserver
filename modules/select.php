<?php

// handle selecting of files

/**
 * Implementation of register
 * @ingroup register
 */
function register_select()
{
	return array(
		'name' => 'File Selector',
		'description' => 'Allows users to select files and saves the selected files in their session and profile.',
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('item', 'on', 'off'),
		'alter query' => array('selected')
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return None by default, All or None are valid inputs
 */
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

/**
 * Implmenetation of validate, combine and manipulate the IDs from a request
 * this function looks for item, on, and off, variables in a request and generates a list of IDs
 * @ingroup validate
 * @return an array of specified and validate IDs, combines 'item' and 'on' and removes 'off', and empty array by default
 */
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return an array of IDs for the selected value
 */
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
			if(is_numeric($value))
			{
				$id = $value;
				$value = 'on';
			}
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, any numeric ID is acceptable
 */
function validate_id($request)
{
	if(isset($request['id']) && is_numeric($request['id']))
		return $request['id'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return An empty array by default, otherwise a numeric list of IDs to save for later reference
 */
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return an empty array by default or the validated list of numeric IDs to remove from the saved list
 */
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

/**
 * Implementation of validate
 * @ingroup validate
 * @return false by default, if set to true the select call will not append all relevant information to a list of files, only the information from the specified handler (cat) will be retrieved, this is convenient for performing fast queries
 */
function validate_short($request)
{
	if(isset($request['short']))
	{
		if($request['short'] === true || $request['short'] === 'true')
			return true;
		elseif($request['short'] === false || $request['short'] === 'false')
			return false;
	}
	return false;
}
 
/**
 * Implementation of session
 * passes a validated request to the session select for processing and saving
 * @ingroup session
 */ 
function session_select($request)
{
	$save = array();
	$save['on'] = @$request['on'];
	$save['off'] = @$request['off'];
	$save['item'] = @$request['item'];
	$save['selected'] = validate_selected($request);
	
	return $save;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_select($request)
{
	// set up required request variables
	$request['cat'] = validate_cat($request);
	$request['start'] = validate_start($request);
	$request['limit'] = validate_limit($request);
	$request['order_by'] = validate_order_by($request);
	$request['direction'] = validate_direction($request);
	
	// discard selected stuff here, we want to show a full list, the selected stuff is just for saving in the session
	//   in order to list the selected stuff only, one should use the list.php module
	if(isset($request['selected'])) unset($request['selected']);
	if(isset($request['item'])) unset($request['item']);
	if(isset($request['id'])) unset($request['id']);
	
	// make select call
	$files = call_user_func_array($request['cat'] . '::get', array($request, &$total_count));
	
	if(!is_array($files))
	{
		PEAR::raiseError('There was an error with the query.', E_USER);
		register_output_vars('files', array());
		return;
	}
	
	$order_keys_values = array();
	
	// the ids handler will do the replacement of the ids
	if(count($files) > 0)
	{
		// wrappers for parent databases do not get IDs!
		if(!is_wrapper($request['cat']))
		{
			$files = db_ids::get(array('cat' => $request['cat']), $tmp_count, $files);
		}
		$files = db_users::get(array(), $tmp_count, $files);
	}
	
	// count a few types of media for templates to use
	$files_count = 0;
	$image_count = 0;
	$video_count = 0;
	$audio_count = 0;
	
	// get all the other information from other handlers
	foreach($files as $index => $file)
	{
		$tmp_request = array();
		$tmp_request['file'] = $file['Filepath'];
	
		// merge with tmp_request to look up more information
		$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
		
		// short results to not include information from all the handlers
		if(!isset($request['short']) || $request['short'] == false)
		{
			// merge all the other information to each file
			foreach($GLOBALS['handlers'] as $i => $handler)
			{
				if(USE_DATABASE == false || ($handler != $request['cat'] && constant($handler . '::INTERNAL') == false && call_user_func_array($handler . '::handles', array($file['Filepath'], $file))))
				{
					$return = call_user_func_array($handler . '::get', array($tmp_request, &$tmp_count));
					if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
					if($handler == 'db_audio' || $handler == 'fs_audio' || $handler == 'db_video' || $handler == 'fs_video' || $handler == 'db_image' || $handler == 'fs_image')
					{
						if($handler == 'db_audio' || $handler == 'fs_audio') $audio_count++;
						if($handler == 'db_video' || $handler == 'fs_video') $video_count++;
						if($handler == 'db_image' || $handler == 'fs_image') $image_count++;
					}
					else
						$files_count++;
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
	
	// set counts
	register_output_vars('total_count', $total_count);
	register_output_vars('audio_count', $audio_count);
	register_output_vars('video_count', $video_count);
	register_output_vars('image_count', $image_count);
	register_output_vars('files_count', $files_count);
	
	// support paging
	register_output_vars('start', $request['start']);
	register_output_vars('limit', $request['limit']);
	
	// register selected files for templates to use
	if(isset($_SESSION['select']['selected'])) register_output_vars('selected', $_SESSION['select']['selected']);
}

