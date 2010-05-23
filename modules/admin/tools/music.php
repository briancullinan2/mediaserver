<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_music()
{
	$tools = array(
		'name' => 'Music',
		'description' => 'Tools for reorganizing, downloading, and renaming music files.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Missing Tracks',
				'description' => 'Find out which tracks are missing from your music library using discogs.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Discography Completion',
				'description' => 'Use Discogs to find missing albums and provide links to download off of popular services.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Tag and Rename',
				'description' => 'Rename music files using their ID3 tags or retag music files.',
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
function output_admin_tools_music($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

