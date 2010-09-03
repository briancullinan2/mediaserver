<?php


/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_netflix_xml($settings)
{
	// not much to validate, it either is an xml id or it isn't
	if(isset($settings['netflix_xml']))
		return $settings['netflix_xml'];
	return '';
}

/**
 * Helper function
 * @return an associative array of all the repeated files on netflix
 */
function movies_repeats_on_netflix($disk, $netflix)
{
	$result = array();
	
	// try names first because it's easiest
	$repeats = array_intersect($netflix['all_movies'], $disk['names']);
	$orig = array_flip(array_intersect($disk['names'], $netflix['all_movies']));
	foreach($repeats as $id => $movie)
	{
		$result[] = array(sprintf('%03d', ($id+1)), $movie, $disk['filepaths'][$orig[$movie]]);
	}
	
	// now do tokens
	$repeats = array_intersect($netflix['tokens'], $disk['tokens']);
	$orig = array_flip(array_intersect($disk['tokens'], $netflix['tokens']));
	foreach($repeats as $id => $movie)
	{
		$result[] = array(sprintf('%03d', ($id+1)), $movie, $disk['filepaths'][$orig[$movie]]);
	}
	
	return $result;
}

/**
 * Helper function
 * @return an associative array containing the movie information from netflix
 */
function movies_netflix_fetch_movies()
{
	$id = setting('netflix_xml');
	
	// fetch the movies
	$result = fetch('http://rss.netflix.com/QueueRSS?id=' . urlencode($id), array(), array(), array());
	
	// parse movies
	$count = preg_match_all('/\<title\>([0-9]{3})- ([^\<]*)\<\/title\>\s*<link>([^\<]*)<\/link>[\s\S]*?<description>([^\<]*)</i', $result['content'], $matches);
	
	// loop through movies and build array
	$movies = array(
		'all_movies' => array(),
		'movies' => array(),
		'tokens' => array(),
		'descriptions' => array(),
	);
	foreach($matches[0] as $i => $movie)
	{
		$tmp_tokens = tokenize($matches[2][$i]);
		$movies['all_movies'][] = $matches[2][$i];
		$movies['tokens'][] = $tmp_tokens['Most'];
		$movies['descriptions'][] = $matches[4][$i];

		$movies['movies'][] = array(
			'q_pos' => $matches[1][$i],
			'title' => $matches[2][$i],
			'link' => $matches[3][$i],
		);
	}
	
	return $movies;
}