<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_watch()
{
	return array(
		'name' => 'Watch List',
		'description' => 'Handles the watch table and what directories the website scans.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, returns the path with a prepended carrot ^, accepts a path with prepended carrot or exclamation point
 */
function validate_waddpath($request)
{
	if(isset($request['waddpath']) && $request['waddpath'][0] != '!' && $request['waddpath'][0] != '^')
		return '^' . $request['waddpath'];
	elseif(isset($request['waddpath']))
		return $request['waddpath'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return accepts any positive numeric index to remove
 */
function validate_wremove($request)
{
	if(isset($request['wremove']) && is_numeric($request['wremove']) && $request['wremove'] >= 0)
		return $request['wremove'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_watch($request)
{
	$request['waddpath'] = validate_waddpath($request);
	$request['wremove'] = validate_wremove($request);

	if(isset($request['waddpath']))
	{
		if(db_watch::handles($request['waddpath']))
		{
				// pass file to handler
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
