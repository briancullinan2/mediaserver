<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_alias()
{
	return array(
		'name' => lang('alias title', 'Aliasing'),
		'description' => lang('alias description', 'Alias the paths from the filesystem to display as differen/less complicated paths when shown to the users.'),
		'privilage' => 10,
		'path' => __FILE__,
		'depends on' => array('database'),
		'database' => array(
			'Filepath' 		=> 'TEXT',
			'Alias' 		=> 'TEXT',
			'Paths_regexp'	=> 'TEXT',
			'Alias_regexp'	=> 'TEXT'
		),
		'internal' => true,
		'template' => true,
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_admin_alias()
{
	$status = array();
		
	if(dependency('admin_alias'))
	{
		$status['use_alias'] = array(
			'name' => 'Aliasing',
			'status' => '',
			'description' => array(
				'list' => array(
					'Aliasing is enabled.',
				),
			),
			'value' => 'Aliases enabled',
		);
	}
	else
	{
		$status['use_alias'] = array(
			'name' => 'Aliasing',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'Aliasing is turned off, and therefore people could see the actual paths to files on the filesystem.',
				),
			),
			'value' => 'Aliases disabled',
		);
	}
	
	return $status;

}

/**
 * Set up the list of aliases from the database
 * @ingroup setup
 */
function setup_admin_alias()
{
	// get the aliases to use to replace parts of the filepath
	$GLOBALS['paths_regexp'] = array();
	$GLOBALS['alias_regexp'] = array();
	$GLOBALS['paths'] = array();
	$GLOBALS['alias'] = array();
	
	// if the dependencies are not met, exit here
	if(dependency('admin_alias') == false)
		return;

	if(setting('admin_alias_enable') == true)
	{
		$aliases = $GLOBALS['database']->query(array('SELECT' => 'admin_alias'), false);
		if($aliases !== false)
		{
			foreach($aliases as $key => $alias_props)
			{
				$GLOBALS['paths_regexp'][] = $alias_props['Paths_regexp'];
				$GLOBALS['alias_regexp'][] = $alias_props['Alias_regexp'];
				$GLOBALS['paths'][] = $alias_props['Filepath'];
				$GLOBALS['alias'][] = $alias_props['Alias'];
			}
		}
	}
}

/**
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_admin_alias($request)
{
	$request['order_by'] = 'id';
	
	$props['SELECT'] = 'admin_alias';
	
	$files = $GLOBALS['database']->query($props, false);
	
	return $files;
}
