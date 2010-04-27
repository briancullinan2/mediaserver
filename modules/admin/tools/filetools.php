<?php

function register_admin_tools_filetools()
{
	$tools = array(
		'name' => 'File Tools',
		'description' => 'Tools for reorganizing files and folders easily and quickly.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Ascii File Names',
				'description' => 'List of files in the database that have strange named and the option to fix them.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Excessive Underscores and Periods',
				'description' => 'Options to find and rename files that have many underscores and periods, such as files downloaded from the internet.',
				'privilage' => 10,
				'path' => __FILE__
			)
		)
	);
	
	return $tools;
}

function output_admin_tools_filetools($request)
{
	$request['subtool'] = validate_subtool($request);
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
}

