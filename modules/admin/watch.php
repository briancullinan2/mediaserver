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
		'path' => __FILE__,
		'database' => array(
			'Filepath' 	=> 'TEXT',
			// add a space to the end so that it can be NULL in the database
			'Lastwatch' => 'DATETIME'
		),
		'internal' => true,
		'depends on' => array('database', 'search', 'admin_handlers'),
		'template' => true,
	);
}

/**
 * Implementation of setup
 * @ingroup setup
 */
function setup_admin_watch()
{
	// get watched and ignored directories because they are used a lot
	$GLOBALS['ignored'] = get_admin_watch(array('search_Filepath' => '/^!/'), $count);
	$GLOBALS['watched'] = get_admin_watch(array('search_Filepath' => '/^\\^/'), $count);
	// always add user local to watch list
	$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', setting('local_users')));
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_admin_watch()
{
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
	return generic_validate_numeric_zero($request, 'wremove');
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_admin_watch($file)
{
	$dir = str_replace('\\', '/', $file);
	
	if($file[0] == '!' || $file[0] == '^')
	{
		$file = substr($file, 1);
		if(is_dir(str_replace('/', DIRECTORY_SEPARATOR, $file)))
		{
			return true;
		}
	}
	
	return false;
}

/** 
 * Helper function
 */
function add_admin_watch($file)
{
	$file = str_replace('\\', '/', $file);
		
	if(handles($file, 'admin_watch'))
	{
		// add ending backslash
		if( substr($file, strlen($file)-1) != '/' ) $file .= '/';
		
		$db_watch = $GLOBALS['database']->query(array(
				'SELECT' => 'admin_watch',
				'COLUMNS' => array('id'),
				'WHERE' => 'LEFT("' . addslashes($file) . '", LENGTH(Filepath)) = Filepath',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_watch) == 0 && $file != '^' . setting('local_users') )
		{
			// pull information from $info
			$fileinfo = array();
			$fileinfo['Filepath'] = addslashes($file);
		
			PEAR::raiseError('Adding watch: ' . $file, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => 'admin_watch', 'VALUES' => $fileinfo), false);
			
			// add to watch_list and to files database
			add_updates(substr($file, 1));
			
			handle_file(substr($file, 1));
			
			return $id;
		}
		else
		{
			// just pass the first directories to watch_list handler
			return add_updates(substr($file, 1));
		}
		
	}
	return false;
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_admin_watch($request, &$count)
{
	$props = array();
	
	$props = array(
		'SELECT' => 'admin_watch',
		'WHERE' => 'Filepath REGEXP "' . addslashes(substr($request['search_Filepath'], 1, strlen($request['search_Filepath']) - 2)) . '"'
	);
	
	// get directory from database
	$files = $GLOBALS['database']->query($props, false);
	
	// make some changes
	foreach($files as $i => $file)
	{
		$files[$i]['Filepath'] = substr($file['Filepath'], 1);
	}
	
	return $files;
}

/** 
 * Implementation of remove_handler
 * @ingroup remove_handler
 */
function remove_admin_watch($file)
{
	// watch directories are never removed by the script
	return false;
}

/** 
 * Implementation of cleanup_handler
 * @ingroup cleanup_handler
 */
function cleanup_admin_watch()
{
	// do not do anything, watch directories are completely managed
	return false;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_watch($request)
{
	$request['waddpath'] = validate($request, 'waddpath');
	$request['wremove'] = validate($request, 'wremove');

	if(isset($request['waddpath']))
	{
		if(handles($request['waddpath'], 'admin_watch'))
		{
				// pass file to handler
				add_admin_watch($request['waddpath']);
		}
		else
		{
			PEAR::raiseError('Invalid path.', E_USER);
		}
		register_output_vars('waddpath', $request['waddpath']);
	}
	
	if(isset($request['wremove']))
	{
		$GLOBALS['database']->query(array('DELETE' => 'admin_watch', 'WHERE' => 'id=' . $request['wremove']), false);
	}
	
	// reget the watched and ignored because they may have changed
	$GLOBALS['ignored'] = get_files(array('search_Filepath' => '/^!/'), $count, 'admin_watch');
	$GLOBALS['watched'] = get_files(array('search_Filepath' => '/^\\^/'), $count, 'admin_watch');
	$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', setting('local_users')));
	
	// make select call for the file browser
	$files = get_files(array(
		'dir' => validate_dir($request),
		'start' => validate_start($request),
		'limit' => 32000,
		'dirs_only' => true,
	), &$total_count, true);

	$request = validate($request, 'start');

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


function theme_watch()
{
	theme('header');
	
	?>
	This is a list of folders on the server to watch for media files:<br />
	<?php

	theme('errors');

	?>
	<form action="" method="post">
		<select name="wremove" size="10">
		
		<?php
			foreach($GLOBALS['templates']['vars']['ignored'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">ignore: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
			foreach($GLOBALS['templates']['vars']['watched'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">watch: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
		?>
		</select>
		<br />
		<input type="submit" value="Remove" />
	</form>
	<form action="" method="post">
		<input type="text" name="waddpath" size="50" value="<?php echo (isset($GLOBALS['templates']['vars']['waddpath'])?$GLOBALS['templates']['vars']['waddpath']:"")?>" />
		<input type="submit" value="Add" />
		<br />
	</form>
	<?php

	theme('footer');
}
