<?php

// handles the watch tables

function register_watch()
{
	return array(
		'name' => 'watch',
		'description' => 'Handles the watch table and what directories the website scans.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

function validate_waddpath($request)
{
	if(isset($request['waddpath']) && $request['waddpath'][0] != '!' && $request['waddpath'][0] != '^')
		return '^' . $request['waddpath'];
	elseif(isset($request['waddpath']))
		return $request['waddpath'];
}

function validate_wremove($request)
{
	if(isset($request['wremove']) && is_numeric($request['wremove']))
		return $request['wremove'];
}

function output_watch($request)
{
	$request['waddpath'] = validate_waddpath($request);
	$request['wremove'] = validate_wremove($request);

	if(isset($request['waddpath']))
	{
		if(db_watch::handles($request['waddpath']))
		{
				// pass file to module
				db_watch::handle($request['waddpath']);
		}
		else
		{
			PEAR::raiseError('Invalid path.', E_USER);
		}
		register_output_vars('waddpath', $request['waddpath']);
	}
	
	if(isset($request['wremove']))
	{
		$GLOBALS['database']->query(array('DELETE' => db_watch::DATABASE, 'WHERE' => 'id=' . $request['wremove']), false);
	}
	
	// reget the watched and ignored because they may have changed
	$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count);
	$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count);
	$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', LOCAL_USERS));
	
	// assign variables for a smarty template to use
	register_output_vars('watched', $GLOBALS['watched']);
	register_output_vars('ignored', $GLOBALS['ignored']);
}
