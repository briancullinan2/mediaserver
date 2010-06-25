<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_albums()
{
	return array(
		'name' => 'Album',
		'description' => 'Provide easy to access interface to all the unique albums.',
		'wrapper' => 'audio',
		'columns' => array('id', 'SongCount', 'Album', 'Filepath'),
	);
}

/**
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_albums($request)
{
	if(isset($request['dir']) && ($request['dir'] == '' || $request['dir'] == '/'))
	{
		unset($request['dir']);
	}
	
	// change the cat to the table we want to use
	$request['cat'] = validate(array('cat' => 'audio'), 'cat');
	$request['selected'] = validate($request, 'selected');
	
	// modify some request stuff
	if(isset($request['dir']))
	{
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
		if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
		if($request['dir'] == '$Unknown$')
			$request['dir'] = '';
		$request['search_Album'] = '=' . $request['dir'] . '=';
		unset($request['dir']);
		
		$files = get_files($request, $count, 'audio');
	}
	elseif(!isset($request['selected']))
	{
		$request['order_by'] = 'Album';
		$request['group_by'] = 'Album';
		
		$files = get_files($request, $count, 'audio');
		
		// make some changes
		foreach($files as $i => $file)
		{
			if($files[$i]['Album'] == '')
				$files[$i]['Album'] = '$Unknown$';
			$files[$i]['Filetype'] = 'FOLDER';
			$files[$i]['Filesize'] = '0';
			$files[$i]['Filepath'] = '/' . $files[$i]['Album'] . '/';
			$files[$i]['Filename'] = $files[$i]['Album'];
			$files[$i]['SongCount'] = $files[$i]['count(*)'];
			unset($files[$i]['Title']);
			unset($files[$i]['Track']);
			unset($files[$i]['Bitrate']);
			unset($files[$i]['Length']);
			unset($files[$i]['Artist']);
		}
	}
	else
	{
		$files = get_files($request, $count, 'audio');
	}
	
	return $files;
}
