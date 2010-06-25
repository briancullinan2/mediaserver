<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_genres()
{
	return array(
		'name' => 'Genres',
		'description' => 'Provide easy to access interface to all the unique genres.',
		'wrapper' => 'audio',
		'columns' => array('id', 'SongCount', 'Genre', 'Filepath'),
	);
}

/**
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_genres($request, &$count)
{
	$request['cat'] = validate(array('cat' => 'audio'), 'cat');
	// modify some request stuff
	if(isset($request['dir']))
	{
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
		if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
		if($request['dir'] == '$Unknown$')
			$request['dir'] = '';
		$request['search'] = '=' . $request['dir'] . '=';
		$request['columns'] = 'Genre';
		unset($request['dir']);
		
		$files = get_files($request, $count, 'files');
	}
	else
	{
		$request['order_by'] = 'Genre';
		$request['group_by'] = 'Genre';
		
		$files = get_files($request, $count, 'files');
		
		// make some changes
		foreach($files as $i => $file)
		{
			if($files[$i]['Genre'] == '')
				$files[$i]['Genre'] = '$Unknown$';
			$files[$i]['Filetype'] = 'FOLDER';
			$files[$i]['Filesize'] = '0';
			$files[$i]['Filepath'] = '/' . $files[$i]['Genre'] . '/';
			$files[$i]['Filename'] = $files[$i]['Genre'];
			$files[$i]['SongCount'] = $files[$i]['count(*)'];
		}
	}
	
	return $files;
}

