<?php

function register_filetools()
{
	$tools = array();
	$tools[0] = array(
		'name' => 'Ascii File Names',
		'description' => 'List of files in the database that have strange named and the option to fix them.',
		'privilage' => 10,
		'path' => __FILE__
	);
	$tools[1] = array(
		'name' => 'Excessive Underscores and Periods',
		'description' => 'Options to find and rename files that have many underscores and periods, such as files downloaded from the internet.',
		'privilage' => 10,
		'path' => __FILE__
	);
	return $tools;
}

