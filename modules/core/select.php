<?php

// handle selecting of files

function rewrite_select($request)
{
	if(isset($request['path_info']))
	{
		$dirs = split('/', $request['path_info']);
		if(isset($dirs) && $dirs[1] == 'dir')
		{
			unset($dirs[1]);
			unset($dirs[0]);
			
			$request['dir'] = '/' . implode('/', $dirs);

			return $request;
		}
	}
}

function url_select($request)
{
	if(isset($request['dir']))
	{
		$tmp_request = array('module' => $request['module']);
		$path = create_path_info($tmp_request);
		$path .= 'dir' . $request['dir'];
		unset($request['dir']);
		unset($request['module']);
		return array($request, $path);
	}
	return array($request, '');
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
		$selected = validate($request, 'item');
	else
		$selected = $request['selected'];
		
	foreach($selected as $i => $id)
	{
		if(!is_numeric($id))
			unset($selected[$i]);
	}
	
	$request['on'] = validate($request, 'on');
	
	$selected = array_merge($selected, $request['on']);
	$selected = array_unique($selected);
	
	$request['off'] = validate($request, 'off');
	$selected = array_diff($selected, $request['off']);
	
	$request['id'] = validate($request, 'id');
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
	$select = validate($request, 'select');

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
	return generic_validate_numeric($request, 'id');
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
	return generic_validate_boolean_false($request, 'short');
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
	$save['selected'] = validate($request, 'selected');

	return $save;
}

function alter_query_select($request, &$props)
{
	$request['selected'] = validate($request, 'selected');
	
	// select an array of ids!
	if(isset($request['selected']) && count($request['selected']) > 0 )
	{
		$where = '';
		// compile where statement for either numeric id or encoded path
		foreach($request['selected'] as $i => $id)
		{
			if(is_numeric($id))
			{
				$where .= ' id = ' . $id . ' OR';
			}
			else
			{
				// unpack encoded path and add it to where
				$where .= ' Hex = "' . $id . '" OR';
			}
		}
		// remove last or and add to where list
		$props['WHERE'] = array(substr($where, 0, strlen($where)-2));

		// selected items have priority over all the other options!
		unset($props['LIMIT']);
		unset($props['ORDER']);
		
		// get ids from centralized id database
		$files = $GLOBALS['database']->query(array('WHERE' => $props['WHERE'], 'SELECT' => 'ids'), true);
		
		if(count($files) > 0)
		{
			// loop through ids and construct new where based on handler
			$where = '';
			foreach($files as $i => $file)
			{
					$where .= ' id = ' . $file[$request['cat'] . '_id'] . ' OR';
			}
			$props['WHERE'] = array(substr($where, 0, strlen($where)-2));
		}
		else
		{
			raise_error('IDs not found!', E_USER);
			return false;
		}
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_select($request)
{
	// set up required request variables
	$request['cat'] = validate($request, 'cat');
	$request['start'] = validate($request, 'start');
	$request['limit'] = validate($request, 'limit');
	$request['order_by'] = validate($request, 'order_by');
	$request['direction'] = validate($request, 'direction');
	
	// discard selected stuff here, we want to show a full list, the selected stuff is just for saving in the session
	//   in order to list the selected stuff only, one should use the list.php module
	if(isset($request['module']) && $request['module'] == 'select')
	{
		if(isset($request['selected'])) unset($request['selected']);
		if(isset($request['item'])) unset($request['item']);
		if(isset($request['id'])) unset($request['id']);
	}
	
	// make select call
	$files = get_files($request, $total_count, $request['cat']);
	if(!is_array($files))
	{
		raise_error('There was an error with the query.', E_USER);
		register_output_vars('files', array());
		return;
	}
	
	$order_keys_values = array();
	
	// the ids handler will do the replacement of the ids
	if(setting('database_enable') == true)
	{
		if(count($files) > 0)
		{
			// wrappers for parent databases do not get IDs!
			if(!is_wrapper($request['cat']))
			{
				$files = get_ids(array('cat' => $request['cat']), $tmp_count, $files);
			}
			else
			{
				$files = get_ids(array('cat' => $GLOBALS['modules'][$request['cat']]['wrapper']), $tmp_count, $files);
			}
			$files = get_users(array(), $tmp_count, $files);
		}
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
			foreach($GLOBALS['modules'] as $handler => $config)
			{
				if($handler != $request['cat'] && !is_internal($handler) && handles($file['Filepath'], $handler))
				{
					$return = get_files($tmp_request, $tmp_count, $handler);
					if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
					
					// do some counting
					if($handler == 'audio')
						$audio_count++;
					elseif($handler == 'video')
						$video_count++;
					elseif($handler == 'image') 
						$image_count++;
					elseif($handler == 'files')
						$files_count++;
				}
				// this will help with our counting
				elseif($handler == $request['cat'])
					$files_count++;
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
	if(setting('database_enable') == false)
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
	
	// register selected files for templates to use
	if($session_select = session('select'))
		register_output_vars('selected', $session_select['selected']);
}

function theme_select()
{
	theme('header');

	theme('search_block');

	?>
	There are <?php print $GLOBALS['templates']['html']['total_count']; ?> result(s).<br />
	Displaying items <?php print $GLOBALS['templates']['html']['start']; ?> to <?php print $GLOBALS['templates']['html']['start'] + $GLOBALS['templates']['html']['limit']; ?>.
	<br />
	<?php
	
	theme('pages');
	?>
	<br />
	<form name="select" action="<?php print $GLOBALS['templates']['html']['get']; ?>" method="post">
		<input type="submit" name="select" value="All" />
		<input type="submit" name="select" value="None" />
		<p style="white-space:nowrap">
		Select<br />
		On : Off<br />
		<?php
		theme('files');
		?>
		<input type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php
		
	theme('pages');
	
	theme('list_block');
	
	theme('template_block');

	theme('footer');
}

function theme_files()
{
	if(count($GLOBALS['templates']['vars']['files']) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		$column_lengths = array();
		if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
		{
			// go through files ahead of time and make them monospaced
			foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
			{
				// find the longest string for each column
				foreach($file as $column => $value)
				{
					if(!isset($column_lengths[$column]) || strlen($value) > $column_lengths[$column])
						$column_lengths[$column] = strlen($value);
				}
			}
			?><code><?php
			
			?><input type="checkbox" name="item" value="All" /> <?php
			print str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths['Filepath']+2) . 's', 'Filepath'));
			foreach($GLOBALS['templates']['vars']['settings']['columns'] as $i => $column)
			{
				print ' | ' . str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths[$column]+2) . 's', $column));
			}
			?> | Download<br /><?php
		}
		elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
		{
			?><table cellpadding="10" cellspacing="0" border="1"><tr><td><?php
		}
		
		foreach($GLOBALS['templates']['html']['files'] as $i => $file)
		{
			$file = alter_file($GLOBALS['templates']['vars']['files'][$i], $column_lengths);
			$GLOBALS['templates']['html']['files'][$i] = $file;
			// make links browsable
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'archive')) $cat = 'archive';
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'playlist')) $cat = 'playlist';
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'diskimage')) $cat = 'diskimage';
			else $cat = $GLOBALS['templates']['vars']['cat'];
			
			if($GLOBALS['templates']['vars']['cat'] != $cat || $GLOBALS['templates']['vars']['files'][$i]['Filetype'] == 'FOLDER') $new_cat = $cat;
			
			$link = isset($new_cat)?url('module=select&cat=' . $new_cat . '&dir=' . urlencode($GLOBALS['templates']['vars']['files'][$i]['Filepath'])):url('module=file&cat=' . $cat . '&id=' . $file['id'] . '&filename=' . urlencode($GLOBALS['templates']['vars']['files'][$i]['Filename']));
			?>
			<input type="checkbox" name="item[]" value="<?php print $file['id']; ?>" <?php print isset($GLOBALS['templates']['vars']['selected'])?(in_array($GLOBALS['templates']['vars']['files'][$i]['id'], $GLOBALS['templates']['vars']['selected'])?'checked="checked"':''):''; ?> />
			<a href="<?php print $link; ?>"><?php print trim($file['Filepath'], '&nbsp;'); ?></a><?php print substr($file['Filepath'], strlen(trim($file['Filepath'], '&nbsp;'))); ?>
			<?php
			
			foreach($GLOBALS['templates']['vars']['settings']['columns'] as $j => $column)
			{
				if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
					print ' | ';
				elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
					print '</td><td>';
				else
					print ' - ';
				
				if(isset($file[$column]))
				{
					print $file[$column];
				}
			}
			
			if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
			{
				print ' | ';
			}
			elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
			{
				print '</td><td>';
			}
			else
			{
				?> - Download: <?php
			}
			?>
			<a href="<?php print url(array(
							'module' => 'zip',
							'cat' => $GLOBALS['templates']['vars']['cat'],
							'id' => $file['id'],
							'filename' => 'Files.zip'
						)); ?>">zip</a> :
			<a href="<?php print url(array(
							'module' => 'torrent',
							'cat' => $GLOBALS['templates']['vars']['cat'],
							'id' => $file['id'],
							'filename' => 'Files.torrent'
						)); ?>">torrent</a>
			<?php
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'video'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp4', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mpg', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MPG</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'wmv', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMV</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'audio'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp4a', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp3', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP3</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'wma', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMA</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'image'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'jpg', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">JPG</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'gif', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">GIF</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'png', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">PNG</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'code'))
			{
				?>
				: <a href="<?php print url(array('module' => 'code', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">view</a>
				<?php
			}
			
			if($GLOBALS['templates']['vars']['settings']['view'] == 'table')
			{
				if($i < count($GLOBALS['templates']['vars']['files']) - 1)
				{
					?></td></tr><tr><td><?php
				}
			}
			else
			{
				?><br /><?php
			}
		}
		if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
		{
			?></code><?php
		}
		if($GLOBALS['templates']['vars']['settings']['view'] == 'table')
		{
			?></td></tr></table><?php
		}
	}
}

function alter_file($file, $column_lengths = NULL)
{
	foreach($file as $column => $value)
	{
		if(isset($column_lengths[$column]))
			$file[$column] = sprintf('%-' . ($column_lengths[$column]+2) . 's', $value);
		if(isset($GLOBALS['templates']['vars']['search_regexp']) && 
			isset($GLOBALS['templates']['vars']['search_regexp'][$column]))
			$file[$column] = preg_replace($GLOBALS['templates']['vars']['search_regexp'][$column], '\'<strong style="background-color:#990;">\' . str_replace(\' \', \'&nbsp;\', htmlspecialchars(\'$0\')) . \'</strong>\'', $file[$column]);
		else
			$file[$column] = str_replace(' ', '&nbsp;', htmlspecialchars($file[$column]));
	}
	return $file;
}

