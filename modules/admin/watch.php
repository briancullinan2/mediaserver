<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_watch()
{
	return array(
		'name' => lang('watch title', 'Watch List'),
		'description' => lang('watch decscription', 'Handles the watch table and what directories the website scans.'),
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
	$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', setting('local_users')));
	
	// make select call for the file browser
	$files = fs_file::get(array(
		'dir' => validate_dir($request),
		'start' => validate_start($request),
		'limit' => 32000,
		'dirs_only' => true,
	), &$total_count, true);

	$request = validate_start($request);

	// support paging
	register_output_vars('start', $request['start']);
	register_output_vars('limit', 32000);
	
	// assign variables for a smarty template to use
	register_output_vars('total_count', $total_count);
	register_output_vars('files', $files);
	register_output_vars('dir', $request['dir']);
	
	// watch information
	register_output_vars('watched', $GLOBALS['watched']);
	register_output_vars('ignored', $GLOBALS['ignored']);
}

