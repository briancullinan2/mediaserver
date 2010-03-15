<?php

function register_admin_tools()
{
	return array(
		'name' => 'Tools',
		'description' => 'Tools for manipulating the database and viewing different types of information about the system.',
		'privilage' => 10,
		'path' => __FILE__
	);
}

function output_admin_tools($request)
{
	// preffered order is a list for which order the tools should be arranged in, this is completely optional and makes the toolset a little more context aware
	$preffered_order = array('Site Information', 'Log Parser', 'Ascii File Names', 'Excessive Underscores and Periods');
	
}

