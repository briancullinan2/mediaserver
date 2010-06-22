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
		),
		'template' => false,
		'settings' => array('netflix_xml'),
		'session' => array('add_movie_folder', 'remove_movie_folder', 'reset_configuration'),
	);
	
	return $tools;
}

/**
 * Set up the list of settings
 * @ingroup setup
 */
function setup_admin_tools_movies()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_movie_search_' . $i] = create_function('$settings', 'return setting_movie_search($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_movies']['settings'][] = 'movie_search_' . $i;
	}
	// movie folders
	for($i = 0; $i < 50; $i++)
	{
		$GLOBALS['setting_movie_folder_' . $i] = create_function('$settings', 'return setting_movie_folder($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_movies']['settings'][] = 'movie_folder_' . $i;
	}
}

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
 * Implementation of setting
 * @ingroup setting
 */
function setting_movie_search($settings, $index)
{
	// return the same static service as listed in the nzbservice module
	if($index == 0)
		return 'http://nzbmatrix.com/nzb-search.php?cat=movies-all&search=%s';
	if($index == 1)
		return 'http://www.newzbin.com/search/query/?searchaction=Go&category=6&q=%s';

	// don't continue with this if stuff is missing
	if(isset($settings['movie_search_' . $index]) && 
		$settings['movie_search_' . $index] != ''
	)
		return $settings['movie_search_' . $index];
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_movie_folder($settings, $index)
{
	if(isset($settings['movie_folder_' . $index]))
	{
		$settings['movie_folder_' . $index] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $settings['movie_folder_' . $index]);
		if(substr($settings['movie_folder_' . $index], -1) != DIRECTORY_SEPARATOR)
			$settings['movie_folder_' . $index] .= DIRECTORY_SEPARATOR;
		if(file_exists($settings['movie_folder_' . $index]))
			return $settings['movie_folder_' . $index];
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_movie_folders($settings)
{
	if(!isset($settings['movie_folders']))
		$settings['movie_folders'] = array();

	for($i = 0; $i < 50; $i++)
	{
		$folder = setting_movie_folder($settings, $i);
		if(isset($folder))
			$settings['movie_folders'][$i] = $folder;
	}
	
	if(setting_installed() && setting('database_enable'))
	{
		// add folders in the watch list that include the word movie
		foreach($GLOBALS['watched'] as $i => $watch)
		{
			if(preg_match('/movie/i', $watch['Filepath']) != 0)
			{
				$index = count($settings['movie_folders']);
				$folder = setting_movie_folder(array('movie_folder_' . $index => $watch['Filepath']), $index);
			}
		}
	}
	
	return array_values(array_unique($settings['movie_folders']));
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_add_movie_folder($request)
{
	if(!isset($request['add_movie_folder']['add']))
		return;
		
	return $request['add_movie_folder']['folder'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_remove_movie_folder($request)
{
	if(!isset($request['remove_movie_folder']['remove']))
		return;
		
	if(isset($request['remove_movie_folder']['folders']) && is_numeric($request['remove_movie_folder']['folders']))
		return $request['remove_movie_folder']['folders'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_info_singular_step_movies($request)
{
	if(isset($request['info_singular_step_movies']) &&
		in_array($request['info_singular_step_movies'], array('login', 'netflix', 'search'))
	)
		return $request['info_singular_step_movies'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_movie_index($request)
{
	return generic_validate_numeric_zero($request, 'movie_index');
}

/**
 * Implementation of session
 * @ingroup session
 */
function session_admin_tools_movies($request)
{
	// might be configuring the module
	if(!isset($_SESSION['movies']) || isset($request['reset_configuration']))
		$save = array('folders' => setting('movie_folders'));
	else
		$save = $_SESSION['movies'];

	// add server
	if(isset($request['add_movie_folder']))
	{
		$new_folder = setting_movie_folder(array('movie_folder_0' => $request['add_movie_folder']), 0);
		if(isset($new_folder))
			$save['folders'][] = $new_folder;
	}

	// remove server
	if(isset($request['remove_movie_folder']))
	{
		unset($save['folders'][$request['remove_movie_folder']]);
		$save['folders'] = array_values($save['folders']);
	}
	
	// cleanup
	$save['folders'] = array_unique($save['folders']);
	
	return $save;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_tools_movies($settings)
{
	$settings['netflix_xml'] = setting_netflix_xml($settings);
	$settings['movie_folders'] = setting_movie_folders($settings);
	
	$folder_count = count($settings['movie_folders']);
	
	// load services from session
	if(isset($_SESSION['movies']['folders']))
	{
		$settings['movie_folders'] = $_SESSION['movies']['folders'];
	}
	
	$options = array();
	
	$options['netflix_xml'] = array(
		'name' => 'Netflix XML Q',
		'status' => '',
		'description' => array(
			'list' => array(
				'Enter the id of the RSS feed to compare to movies on disk or NZB services.',
				'The Netflix RSS feeds can be found by going to <a href="http://www.netflix.com/RSSFeeds">Netflix RSS Feeds</a>.',
				'It is recommended you enter the id for the entire Queue.'
			),
		),
		'type' => 'text',
		'value' => $settings['netflix_xml'],
	);
	
	// add nzb services
	$options = array_merge($options, configure_admin_tools_nzbservices($settings));
	
	// alter the nzbservices form to use movie search queries instead
	$settings['nzbservices'] = setting_nzbservices($settings);
	foreach($settings['nzbservices'] as $i => $config)
	{
		// add movie search query to form
		$options['nzbservices']['options'][] = array(
			'value' => '<br />'
		);
		$options['nzbservices']['options']['setting_movie_search_' . $i] = array(
			'type' => 'text',
			'value' => setting_movie_search($settings, $i),
			'help' => $config['name'] . ' Movie Search',
		);
	}
	
	// use indices instead
	$options['movie_folders'] = array(
		'name' => 'Movie Folders',
		'status' => '',
		'description' => array(
			'list' => array(
				'This is a list for folders that contain movies.  Movies can be folder names, or file names.',
				'It is recommended that only watched folders be used.'
			),
		),
		'type' => 'set',
		'options' => array(
			'remove_movie_folder[folders]' => array(
				'type' => 'multiselect',
				'options' => $settings['movie_folders'],
				'value' => array(),
				'force_numeric' => true,
			),
			array(
				'value' => '<br />'
			),
			'remove_movie_folder[remove]' => array(
				'type' => 'submit',
				'value' => 'Remove',
			),
			array(
				'value' => '<br />'
			),
			'add_movie_folder[folder]' => array(
				'type' => 'text',
				'value' => '',
				'help' => 'Add Folder',
			),
			'add_movie_folder[add]' => array(
				'type' => 'submit',
				'value' => 'Add',
			),
		),
	);
	
	// add movies for saving
	foreach($settings['movie_folders'] as $i => $folder)
	{
		$options['movie_folders']['options']['setting_movie_folder_' . $i] = array(
			'type' => 'hidden',
			'value' => $folder,
		);
	}
	
	// add unsettings
	for($i = 0; $i < $folder_count - count($settings['movie_folders']); $i++)
	{
		$options['movie_folders']['options']['setting_movie_folder_' . (count($settings['movie_folders']) + $i)] = array(
			'type' => 'hidden',
			'value' => '',
		);
	}
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_movies($request)
{
	$request['subtool'] = validate_subtool($request);
	$request['info_singular'] = validate_info_singular($request);
	
	$infos = array();
	
	if($request['info_singular'] == true)
	{
		output_admin_tools_movies_singular($request);
		
		return;
	}
	if(isset($request['subtool']) && $request['subtool'] == 0)
	{
		// output configuration link
		PEAR::raiseError('You may need to <a href="' . url('module=admin_modules&configure_module=admin_tools_movies') . '">configure</a> this tool in order to use it properly.', E_WARN);
	
		// perform television downloading
		if(dependency('curl_installed') == false)
		{
			$infos['curl_installed'] = array(
				'name' => 'cUrl Not Installed',
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'The system has detected that cUrl API is NOT INSTALLED.',
						'cUrl is used to download content from Netflix, and the specified NZB services.',
					),
				),
				'value' => array(
					'link' => array(
						'url' => 'http://php.net/manual/en/book.curl.php',
						'text' => 'Get cUrl',
					),
				),
			);
		}
		else
		{
			// log in to services
			$infos['movies_login'] = array(
				'name' => 'NZB Services Login',
				'status' => '',
				'description' => array(
					'list' => array(
						'Logging in to NZB Services.',
					),
				),
				'text' => array(
					'loading' => 'Loading...'
				),
				'singular' => url('module=admin_tools_movies&subtool=0&info_singular=true&info_singular_step_movies=login', true),
			);
		}
	}
	
	// output info
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
	
	theme('tools_subtools');
}


/**
 * Helper function for outputting a single piece of the infos
 * @param request the request to process
 */
function output_admin_tools_movies_singular($request)
{
	$request['info_singular_step_movies'] = validate_info_singular_step_movies($request);
	$infos = array();
	
	if($request['info_singular_step_movies'] == 'login')
	{
		// log in to services here
		$results = nzbservices_login();
		$services = setting('nzbservices');
		foreach($results as $i => $result)
		{
			if($result != 200)
			{
				$infos['nzbservice_' . $i] = array(
					'name' => $services[$i]['name'] . ' Login Failed',
					'status' => 'fail',
					'description' => array(
						'list' => array(
							'Login to ' . $services[$i]['login'] . ' for ' . $services[$i]['name'] . ' failed!',
						),
					),
					'text' => 'Login Failed!'
				);
			}
			else
			{
				$infos['nzbservice_' . $i] = array(
					'name' => $services[$i]['name'] . ' Login',
					'status' => '',
					'description' => array(
						'list' => array(
							'Login to ' . $services[$i]['login'] . ' for ' . $services[$i]['name'] . ' successful!',
						),
					),
					'text' => 'Login Succeeded!'
				);
			}
		}
	
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
			'singular' => url('module=admin_tools_movies&subtool=0&info_singular=true&info_singular_step_movies=netflix', true),
		);
	}
	elseif($request['info_singular_step_movies'] == 'netflix')
	{
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
			'text' => 'Movies:<br />There are ' . count($movies['all_movies']) . ' movies on your Netflix Q'
		);
		
		// get intersections
		$disk = movies_get_movie_tokens();
		
		// get repeats on disk
		$repeats_on_disk = movies_repeats_on_disk($disk);
		
		if(count($repeats_on_disk) > 0)
		{
			$disk_repeats = '';
			foreach($repeats_on_disk as $i => $movie)
			{
				$disk_repeats .= '<h3>' . basename($movie[0]) . '</h3>on<br />' . $movie[0] . '<br />' . $movie[1] . '<br />';
			}
			
			$infos['disk_repeat_movies'] = array(
				'name' => 'Repeat Movies On Disk',
				'status' => 'warn',
				'description' => array(
					'list' => array(
						'There are duplicate movies on disk!',
					),
				),
				'text' => 'Movies:<br />' . $disk_repeats,
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
				'text' => 'Movies:<br />No Duplicates found.',
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
				'text' => 'Movies:<br />' . $netflix_repeats,
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
				'text' => 'Movies:<br />No Duplicates found.',
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
				'manual_search[text]' => array(
					'type' => 'text',
					'value' => '',
					'help' => 'Manual Search'
				),
				'manual_search[search]' => array(
					'type' => 'submit',
					'value' => 'Search',
				),
				array(
					'value' => '<br />'
				),
				array(
					'type' => 'button',
					'value' => 'Cancel',
					'action' => 'singular_cancel=true;',
					'help' => 'Automatic Search',
				),
			),
		);
		
		// construct first movie singular
		$infos['netflix_movies_0'] = array(
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
			'singular' => url('module=admin_tools_movies&subtool=0&info_singular=true&info_singular_step_movies=search&movie_index=0', true),
		);
	}
	elseif($request['info_singular_step_movies'] == 'search')
	{
		$services = setting('nzbservices');
		$all_movies = session('all_movies');
		$descriptions = session('descriptions');
		$request['movie_index'] = validate_movie_index($request);
		if(isset($request['movie_index']))
		{
			// search for movies
			$results = movies_fetch_nzb($all_movies[$request['movie_index']]);
			
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
					'text' => 'Services:<br />' . implode('<br />', $results),
				);
			}
	
			if($request['movie_index'] < count($all_movies))
			{
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
						'loading' => 'Loading...'
					),
					'singular' => url('module=admin_tools_movies&subtool=0&info_singular=true&info_singular_step_movies=search&movie_index=' . ($request['movie_index']+1), true),
				);
			}
		}
	}
	
	register_output_vars('infos', $infos);
	
	theme('tools_singular');
}

/**
 * Helper function for fetching a list of movies available for download
 */
function movies_fetch_nzb($movie)
{
	// construct search arguments
	$args = func_get_args();
	
	$services = setting('nzbservices');
	
	$downloads = array();
	
	// loop through NZB services until we find the show
	foreach($services as $i => $config)
	{
		$search = setting('movie_search_' . $i);
		if($search == '')
			$search = $config['search'];
			
		// run query, using television search strings
		$result = fetch(sprintf($search, urlencode($movie)), array(), array(), $_SESSION['nzbservices_' . $i]);
		
		// match nzbs
		$count = preg_match_all($config['match'], $result['content'], $matches);
		if($count > 0)
		{
			// return list of downloads
			$downloads[$i] = '<a href="' . sprintf($search, urlencode($movie)) . '">' . htmlspecialchars($movie) . ' on ' . htmlspecialchars($config['name']) . ' (' . $count . ') <img src="' . $config['image'] . '" alt="icon" /></a><br />';
		}
	}

	return $downloads;
}

/**
 * Helper function for getting the tokens for all the movies
 @return an associative array contains movie names and tokens
 */
function movies_get_movie_tokens()
{
	// get movie folders
	$settings['movie_folders'] = setting('movie_folders');
	
	// first get all directories and merge file paths, and tokens
	$names = array();
	$tokens = array();
	$filepaths = array();
	foreach($settings['movie_folders'] as $i => $folder)
	{
		$movies = get_files(array('dir' => $folder, 'limit' => 32000), $count, true);
		
		foreach($movies as $j => $file)
		{
			$names[] = $file['Filename'];
			$tmp_tokens = tokenize($file['Filename']);
			sort($tmp_tokens['Most']);
			$tokens[] = implode(' ', $tmp_tokens['Most']);
			$filepaths[] = $file['Filepath'];
		}
	}
	
	return array(
		'names' => $names,
		'tokens' => $tokens,
		'filepaths' => $filepaths,
	);
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
 * @return an associative array of all the repeated files on disk
 */
function movies_repeats_on_disk($disk)
{
	$result = array();
	
	// try names first because it's easiest
	$repeats = array_diff_key($disk['names'], array_unique($disk['names']));
	$orig = array_flip(array_unique($disk['names']));
	foreach($repeats as $id => $movie)
	{
		$result[] = array($disk['filepaths'][$id], $disk['filepaths'][$orig[$movie]]);
	}
	
	// now do tokens
	$repeats = array_diff_key($disk['tokens'], array_unique($disk['tokens']));
	$orig = array_flip(array_unique($disk['tokens']));
	foreach($repeats as $id => $movie)
	{
		// exclude movies in the same folder
		if(dirname($disk['filepaths'][$id]) != dirname($disk['filepaths'][$orig[$movie]]))
			$result[] = array($disk['filepaths'][$id], $disk['filepaths'][$orig[$movie]]);
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