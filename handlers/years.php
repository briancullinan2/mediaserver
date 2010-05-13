<?php
// provide an easy to access interface to all the unique albums

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_years()
{
	return array(
		'name' => 'Years',
		'description' => 'Provide easy access to years.',
		'columns' => array('id', 'SongCount', 'Year', 'Filepath'),
		'wrapper' => 'audio',
	);
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_years($request, &$count)
{
		
	// modify some request stuff
	if(isset($request['dir']))
	{
		$request['dir'] = str_replace('\\', '/', $request['dir']);
		if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
		if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
		if($request['dir'] == '$Unknown$')
			$request['dir'] = '';
		$request['search'] = '=' . $request['dir'] . '=';
		$request['columns'] = 'Year';
		unset($request['dir']);
		
		$files = parent::get($request, $count, 'db_audio');
	}
	else
	{
		$request['order_by'] = 'Year';
		$request['group_by'] = 'Year';
		
		$files = parent::get($request, $count, 'db_audio');
		
		// make some changes
		foreach($files as $i => $file)
		{
			if($files[$i]['Year'] == '')
				$files[$i]['Year'] = '$Unknown$';
			$files[$i]['Filetype'] = 'FOLDER';
			$files[$i]['Filesize'] = '0';
			$files[$i]['Filepath'] = '/' . $files[$i]['Year'] . '/';
			$files[$i]['Filename'] = $files[$i]['Year'];
			$files[$i]['SongCount'] = $files[$i]['count(*)'];
		}
	}
	
	return $files;
}


