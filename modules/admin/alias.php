<?php

/**
 * Implementation of register_module
 * @ingroup register
 */
function register_admin_alias()
{
	return array(
		'name' => lang('alias title', 'Aliasing'),
		'description' => lang('alias description', 'Alias the paths from the filesystem to display as differen/less complicated paths when shown to the users.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('use_alias'),
		'depends on' => array('database'),
		'database' => array(
			'Filepath' 		=> 'TEXT',
			'Alias' 		=> 'TEXT',
			'Paths_regexp'	=> 'TEXT',
			'Alias_regexp'	=> 'TEXT'
		),
		'internal' => true,
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_admin_alias()
{
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
	
	if(setting('use_alias') == true)
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

/**
 * Implementation of setting
 * @ingroup setting
 * @return true by default
 */
function setting_use_alias($settings)
{
	// can't use alias is not using database
	$settings['use_database'] = setting_use_database($settings);
	if($settings['use_database'] == false)
		return false;
	
	if(isset($settings['use_alias']))
	{
		if($settings['use_alias'] === true || $settings['use_alias'] === 'true')
			return true;
		elseif($settings['use_alias'] === false || $settings['use_alias'] === 'false')
			return false;
	}
	return true;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_alias($settings)
{
	$settings['use_alias'] = setting_use_alias($settings);
	
	$options = array();
		
	$options['use_alias'] = array(
		'name' => 'Aliasing',
		'status' => '',
		'description' => array(
			'list' => array(
				'Path aliasing is used to disguise the location of files on your file system.  Aliases can be set up to convert a path such as /home/share/ to /Shared/.',
			),
		),
		'type' => 'boolean',
		'value' => $settings['use_alias'],
		'options' => array(
			'Use Aliased Paths',
			'Display Actual Path to Users',
		),
	);

	
	return $options;

}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_alias($request)
{
	// nothing to do here yet
}