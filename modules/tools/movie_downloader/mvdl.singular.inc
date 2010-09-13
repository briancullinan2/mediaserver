<?php

function output_movie_download_singular($request)
{
	switch($request['info_singular_step_movies'])
	{
		case 'login':
			$infos = get_movie_download_login($request);
			break;
		case 'login2':
			$infos = get_movie_download_login2($request);
			break;
		case 'netflix':
			$infos = get_movie_download_netflix($request);
			break;
		case 'search':
			$infos = get_movie_download_search($request);
			break;
	}
	
	theme('form_tableset', $infos);
}


function get_movie_download_login($request)
{
	$infos = nzbservices_singular_result();
	
	// log in to services
	$infos['movies_login2'] = array(
		'name' => 'Torrent Services Login',
		'status' => '',
		'description' => array(
			'list' => array(
				'Logging in to torrent services.',
			),
		),
		'text' => array(
			'loading' => 'Loading...'
		),
		'type' => 'theme',
		'value' => 'singular',
		'href' => url('admin/tools/movies/downloader/login2', true),
	);
	
	return $infos;
}

function get_movie_download_login2($request)
{
	// get torrent login stuff
	$infos = torservices_singular_result();

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
	
	return $infos;
}

function get_movie_download_netflix($request)
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
		'text' => 'There are ' . count($movies['all_movies']) . ' movies on your Netflix Q'
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
			'status' => '',
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
			'status' => '',
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

	// cancel options
	$infos['netflix_movies_null'] = array(
		'name' => 'Movie Search',
		'status' => '',
		'description' => array(
			'list' => array(
				'A movie can be entered to search for, and the cancel function can be used at any time to stop the automatic search.',
			),
		),
		'type' => 'set',
		'options' => array(
			'movie_search[text]' => array(
				'type' => 'text',
				'value' => (isset($request['movie_search'])?$request['movie_search']:''),
				'name' => 'Manual Search'
			),
			'movie_search[search]' => array(
				'type' => 'submit',
				'value' => 'Search',
			),
		),
	);
	
	// if the manual search is set, just do that one
	if(isset($request['movie_search']))
	{
		// construct first movie singular
		$infos['movies_manual_0'] = array(
			'name' => 'Movies in your Q available for Download',
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
			'href' => url('admin/tools/movies/downloader/search?movie_search=' . urlencode($request['movie_search']), true),
		);
	}
	// begin automatic search
	elseif(count($movies['all_movies']) > 0)
	{
		// add cancel button
		$infos['netflix_movies_null']['options'][] = array(
			'value' => '<br />'
		);
		$infos['netflix_movies_null']['options'][] = array(
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
			'href' => url('admin/tools/movies/downloader/search?movie_index=0', true),
		);
	}
	
	return $infos;
}

function get_movie_download_search($request)
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
				'status' => '',
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
				'href' => url('admin/tools/movies/downloader/search?movie_index=' . ($request['movie_index']+1), true),
			);
		}
	}
	elseif(isset($request['movie_search']))
	{
		$request['movie_search_torindex'] = validate($request, 'movie_search_torindex');
		$request['movie_search_nzbindex'] = validate($request, 'movie_search_nzbindex');
		
		// search for movie on each services
		if(!isset($request['movie_search_nzbindex']) && !isset($request['movie_search_torindex']))
			$request['movie_search_nzbindex'] = 0;

		if(isset($request['movie_search_nzbindex']))
			$results = movies_fetch_movie_service($request['movie_search'], $request['movie_search_nzbindex'], true);
		else
			$results = movies_fetch_movie_service($request['movie_search'], $request['movie_search_torindex'], false);
		
		// service movie info, only if results were found
		if(count($results) > 0)
		{
			if(isset($request['movie_search_nzbindex']))
			{
				$index = $request['movie_search_nzbindex'];
				$config = setting('nzbservice_' . $index);
			}
			else
			{
				$index = $request['movie_search_torindex'];
				$config = setting('torservice_' . $index);
			}
			
			$infos['movie_search_' . $index] = array(
				'name' => 'Searched on ' . $config['name'],
				'status' => '',
				'description' => array(
					'list' => array(
						'This movie has been searched for on ' . $config['name'] . ', and results were found.',
					),
				),
				'text' => $config['name'] . ':<br />' . implode('<br />', $results),
			);
		}
		
		// do next service
		if(isset($request['movie_search_nzbindex']) && $request['movie_search_nzbindex']+1 < count(setting('nzbservices')))
		{
			$config = setting('nzbservice_' . $request['movie_search_nzbindex']);
			$infos['movie_search' . ($request['movie_search_nzbindex']+1)] = array(
				'name' => 'Manual Search on ' . $config['name'],
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
				'href' => url('admin/tools/movies/downloader/search?movie_search_nzbindex=' . ($request['movie_search_nzbindex']+1) . '&movie_search=' . urlencode($request['movie_search']), true),
			);
		}
		elseif(isset($request['movie_search_torindex']) && $request['movie_search_torindex']+1 < count(setting('torservices')))
		{
			$config = setting('torservice_' . $request['movie_search_torindex']);
			$infos['movies_manual_' . ($request['movie_search_torindex']+1)] = array(
				'name' => 'Manual Search on ' . $config['name'],
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
				'href' => url('admin/tools/movies/downloader/search?movie_search_torindex=' . ($request['movie_search_torindex']+1) . '&movie_search=' . urlencode($request['movie_search']), true),
			);
		}
		elseif(isset($request['movie_search_nzbindex']))
		{
			$config = setting('torservice_0');
			$infos['movies_manual_0'] = array(
				'name' => 'Manual Search on ' . $config['name'],
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
				'href' => url('admin/tools/movies/downloader/search?movie_search_torindex=0&movie_search=' . urlencode($request['movie_search']), true),
			);
		}
		else
			session('movie_search', NULL);
	}
	
	return $infos;
}

