<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_television()
{
	$tools = array(
		'name' => 'Television',
		'description' => 'Tools for downloading TV information and reorganizing TV show files.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Show Renamer',
				'description' => '.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Metadata Downloader',
				'description' => 'Download television show metadata using TTVDB.',
				'privilage' => 10,
				'path' => __FILE__
			),
		)
	);
	
	return $tools;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_television($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

