<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_movie_metadata()
{
	$tools = array(
		'name' => 'Movie Organizer',
		'description' => 'Download movie data using TMDB and organize movies based on meta data.',
		'privilage' => 10,
		'path' => __FILE__,
		'template' => false,
	);
	
	return $tools;
}
