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
 * Implementation of setting
 * @ingroup setting
 */
function setting_tor_music_search($settings, $index)
{
	// the static services to not have movies, only tv and music
	if($index == 0)
		return 'http://what.cd/torrents.php?searchstr=%s';
	if($index == 1)
		return 'http://www.waffles.fm/browse.php?q=%s';
	if($index == 2)
		return '';

	// don't continue with this if stuff is missing
	if(isset($settings['tor_movie_search_' . $index]) && 
		$settings['tor_movie_search_' . $index] != ''
	)
		return $settings['tor_movie_search_' . $index];
	elseif(isset($settings['torservice_' . $index]['search']) && 
		$settings['torservice_' . $index]['search'] != ''
	)
		return $settings['torservice_' . $index]['search'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_music($request)
{
	$request['subtool'] = validate($request, 'subtool');
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

