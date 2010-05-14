<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_balancer()
{
	return array(
		'name' => lang('balancer title', 'Load Balancer'),
		'description' => lang('balancer description', 'Allows configuring of mirror servers running and moving connections from one server to another.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('balance_servers', 'balance_rules'),
		'depends on' => array('snoopy_installed'),
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_admin_balancer()
{
}

/**
 * Set up the list of aliases from the database
 * @ingroup setup
 */
function setup_admin_balancer()
{
	// use snoopy to download config from remote sites
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_balancer()
{
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


