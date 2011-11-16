<?php


function output_movie_download_login($request)
{
	$service_index = validate($request, 'service_index');
	
	$infos = get_download_services_login($service_index, 'admin/tools/movies/downloader/login/');

	if($service_index == count(setting('all_download_services'))-1)
	{
		// download netflix Q
		$infos['netflix_movies'] = array(
			'name' => 'Movies from Netflix',
			'status' => '',
			'description' => array(
				'list' => array(
					'Downloading Netflix Q.',
				),
			),
			'text' => array(
				'loading' => 'Loading...'
			),
			'type' => 'theme',
			'value' => 'singular',
			'href' => url('admin/tools/movies/downloader/netflix', true),
		);
	}

	theme('form_fieldrows', $infos);
}

function output_movie_download_netflix($request)
{
	$infos = array();
	
	// download netflix Q
	$movies = movies_netflix_fetch_movies();
	
	$infos['netflix_movies'] = array(
		'name' => 'Netflix Movies',
		'status' => '',
		'description' => array(
			'list' => array(
				'These are all the Movies on your Q.',
			),
		),
		'type' => 'set',
		'options' => array(
			'text' => 'There are ' . count($movies['all_movies']) . ' movies on your Netflix Q'
		),
	);
	
	// get intersections
	$disk = movies_get_movie_tokens();
	
	// get repeats on disk
	$repeats_on_disk = movies_repeats_on_disk($disk);
	
	if(count($repeats_on_disk) > 0)
	{
		$disk_repeats = array();
		foreach($repeats_on_disk as $i => $movie)
		{
			$disk_repeats[] = basename($movie[0]) . ' on ' . $movie[0] . ' - ' . $movie[1];
		}
		
		$infos['disk_repeat_movies'] = array(
			'name' => 'Repeat Movies On Disk',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'There are duplicate movies on disk!',
				),
			),
			'value' => array(
				'list' => $disk_repeats,
			),
		);
	}
	else
	{
		
		$infos['disk_repeat_movies'] = array(
			'name' => 'Repeat Movies On Disk',
			'status' => 'pass',
			'description' => array(
				'list' => array(
					'No duplicate movies have been found on disk.',
				),
			),
			'text' => 'No Duplicates found.',
		);
	}
	
	// get repeats on netflix
	$repeats_on_netflix = movies_repeats_on_netflix($disk, $movies);
	
	if(count($repeats_on_netflix) > 0)
	{
		$netflix_repeats = '';
		foreach($repeats_on_netflix as $i => $movie)
		{
			$netflix_repeats .= '<h3>' . basename($movie[0]) . '</h3>' . $movie[1] . ' - ' . $movie[2] . '<br />';
		}
		
		$infos['netflix_repeat_movies'] = array(
			'name' => 'Repeat Movies On Netflix',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'There are duplicate movies on your Netflix Q!'
				),
			),
			'text' => $netflix_repeats,
		);
	}
	else
	{
		$infos['netflix_repeat_movies'] = array(
			'name' => 'Repeat Movies On Netflix',
			'status' => 'pass',
			'description' => array(
				'list' => array(
					'There are no duplicates found.',
				),
			),
			'text' => 'No Duplicates found.',
		);
	}
	
	// perform searches
	$count = 0;
	$query = '';
	session('all_movies', $movies['all_movies']);
	session('descriptions', $movies['descriptions']);

	// add manual search
	$request['service_index'] = validate($request, 'service_index');
	$request['service_search'] = session('download_services');
	$infos = array_merge($infos, get_download_services_manual($request['service_index'], $request['service_search'], 'movies'));
	
	// begin automatic search
	if(!isset($request['service_search']) && count($movies['all_movies']) > 0)
	{
		// add cancel button
		$infos['netflix_movies']['options'][] = array(
			'type' => 'button',
			'value' => 'Cancel',
			'action' => 'if(current_ajax) current_ajax.abort();',
			'name' => 'Automatic Search',
		);
		
		// construct first movie singular
		$infos['netflix_movies_0'] = array(
			'name' => 'Manual Search Movies for Download',
			'status' => '',
			'description' => array(
				'list' => array(
					'Searching for movies.',
				),
			),
			'text' => array(
				'loading' => 'Loading...'
			),
			'type' => 'theme',
			'value' => 'singular',
			'href' => url('admin/tools/movies/downloader/search/0', true),
		);
	}
	
	theme('form_fieldrows', $infos);
}

function output_movie_download_search($request)
{
	$infos = array();
	
	$services = setting('nzbservices');
	$all_movies = session('all_movies');
	$descriptions = session('descriptions');
	if(isset($request['movie_index']))
	{
		// search for movies
		$results = movies_fetch_movies($all_movies[$request['movie_index']]);

		if(count($results) > 0)
		{
			$infos['netflix_movies_' . $request['movie_index']] = array(
				'name' => 'Searched for ' . $all_movies[$request['movie_index']],
				'status' => 'pass',
				'description' => array(
					'list' => array(
						'This movie has been searched for, and results were found.',
						htmlspecialchars_decode($descriptions[$request['movie_index']]),
					),
				),
				'value' => array(
					'Services:',
					'list' => $results,
				),
			);
		}

		if($request['movie_index'] < count($all_movies))
		{
			$last_query = sprintf(setting('nzb_movie_search_0'), urlencode($all_movies[$request['movie_index']]));
			// construct singular
			$infos['netflix_movies_' . ($request['movie_index']+1)] = array(
				'name' => 'Movies in your Q available for Downloads',
				'status' => '',
				'description' => array(
					'list' => array(
						'Searching for movies.',
					),
				),
				'text' => array(
					'loading' => 'Loading...',
					array(
						'Last query:',
						'link' => array(
							'url' => $last_query,
							'text' => $last_query,
						),
					),
				),
				'type' => 'theme',
				'value' => 'singular',
				'href' => url('admin/tools/movies/downloader/search/' . ($request['movie_index']+1), true),
			);
		}
	}
	
	theme('form_fieldrows', $infos);
}

