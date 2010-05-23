<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_movies()
{
	$tools = array(
		'name' => 'Movies',
		'description' => 'Tools for downloading movie meta data.',
		'privilage' => 10,
		'path' => __FILE__,
		'subtools' => array(
			array(
				'name' => 'Netflix/Newsgroups Checker',
				'description' => 'Set up a Netflix and News Groups account to check if movies in your queue already exist on disk or in news groups.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Metadata Downloader',
				'description' => 'Download movie data using TMDB.',
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
function output_admin_tools_movies($request)
{
	$request['subtool'] = validate_subtool($request);
	$infos = array();
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
}

