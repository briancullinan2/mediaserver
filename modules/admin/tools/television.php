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
		'settings' => array('myepisodes'),
		'subtools' => array(
			array(
				'name' => 'Show Renamer',
				'description' => 'Download show listings from TTVDB and rename files on disk.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Metadata Downloader',
				'description' => 'Download television show metadata using TTVDB.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'MyEpisodes, NZB Service Downloader',
				'description' => 'Automatically download episodes selected on MyEpisodes from an NZB service to a specified folder.',
				'privilage' => 10,
				'path' => __FILE__
			),
		),
		'template' => false,
	);
	
	return $tools;
}

/**
 * Set up the list of settings
 * @ingroup setup
 */
function setup_admin_tools_television()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_television_search_' . $i] = create_function('$settings', 'return setting_television_search($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_television']['settings'][] = 'television_search_' . $i;
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_myepisodes($settings)
{
	if(isset($settings['myepisodes']) && isset($settings['myepisodes']['username']) &&
		isset($settings['myepisodes']['password']))
		return array(
			'username' => $settings['myepisodes']['username'],
			'password' => $settings['myepisodes']['password'],
		);
	return array(
		'username' => '',
		'password' => '',
	);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_television_search($settings, $index)
{
	// return the same static service as listed in the nzbservice module
	if($index == 0)
		return 'http://nzbmatrix.com/nzb-search.php?cat=6&search=%s%%20s%02de%02d';

	// don't continue with this if stuff is missing
	if(isset($settings['television_search_' . $index]) && 
		$settings['television_search_' . $index] != ''
	)
		return $settings['television_search_' . $index];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_info_singular_step_television($request)
{
	if(isset($request['info_singular_step_television']) &&
		in_array($request['info_singular_step_television'], array('login', 'shows', 'download'))
	)
		return $request['info_singular_step_television'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_episode($request)
{
	if(isset($request['episode']) && is_numeric($request['episode']))
		return $request['episode'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_season($request)
{
	if(isset($request['season']) && is_numeric($request['season']))
		return $request['season'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_show_index($request)
{
	if(isset($request['show_index']) && is_numeric($request['show_index']))
		return $request['show_index'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_showname($request)
{
	if(isset($request['showname']))
		return $request['showname'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_show_status($request)
{
	if(isset($request['show_status']))
		return $request['show_status'];
}
	
/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_tools_television($settings)
{
	$settings['myepisodes'] = setting_myepisodes($settings);
	
	$options = array();
	
	$options['myepisodes'] = array(
		'name' => 'My Episodes Account',
		'status' => '',
		'description' => array(
			'list' => array(
				'Enter your MyEpisodes.com username and password to download the list of your selected TV Shows.',
			),
		),
		'type' => 'set',
		'options' => array(
			'setting_myepisodes[username]' => array(
				'type' => 'text',
				'help' => 'Username',
				'value' => $settings['myepisodes']['username'],
			),
			array(
				'value' => '<br />'
			),
			'setting_myepisodes[password]' => array(
				'type' => 'text',
				'help' => 'Password',
				'value' => $settings['myepisodes']['password'],
			),
		),
	);
	
	// add nzb services
	$options = array_merge($options, configure_admin_tools_nzbservices($settings));
	
	// alter the nzbservices form to use television search queries instead
	$settings['nzbservices'] = setting_nzbservices($settings);
	foreach($settings['nzbservices'] as $i => $config)
	{
		// add television search query to form
		$options['nzbservices']['options'][] = array(
			'value' => '<br />'
		);
		$options['nzbservices']['options']['setting_television_search_' . $i] = array(
			'type' => 'text',
			'value' => setting_television_search($settings, $i),
			'help' => $config['name'] . ' Television Search',
		);
	}
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_television($request)
{
	$request['subtool'] = validate_subtool($request);
	$request['info_singular'] = validate_info_singular($request);
	$infos = array();
	
	if($request['info_singular'] == true)
	{
		output_admin_tools_television_singular($request);
		
		return;
	}
	if(isset($request['subtool']) && $request['subtool'] == 2)
	{
		// output configuration link
		PEAR::raiseError('You may need to <a href="' . url('module=admin_modules&configure_module=admin_tools_television') . '">configure</a> this tool in order to use it properly.', E_WARN);
	
		// perform television downloading
		if(dependency('curl_installed') == false)
		{
			$infos['curl_installed'] = array(
				'name' => 'cUrl Not Installed',
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'The system has detected that cUrl API is NOT INSTALLED.',
						'cUrl is used to download content from myepisodes.com, and the specified NZB services.',
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
			// log in to myepisodes
			$infos['television_login'] = array(
				'name' => 'MyEpisodes, Service Login',
				'status' => '',
				'description' => array(
					'list' => array(
						'Logging in to myepisodes.com and the configured NZB services.',
					),
				),
				'text' => array(
					'loading' => 'Loading...'
				),
				'singular' => url('module=admin_tools_television&subtool=2&info_singular=true&info_singular_step_television=login', true),
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
function output_admin_tools_television_singular($request)
{
	$request['info_singular_step_television'] = validate_info_singular_step_television($request);
	$infos = array();
	
	if($request['info_singular_step_television'] == 'login')
	{
		// log in to my episodes first
		$result = television_myepisodes_login();
		if($result['status'] != 200)
		{
			$infos['television_login'] = array(
				'name' => 'MyEpisodes Login',
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'Failed to login to my episodes while fetching the login page.',
					),
				),
				'text' => 'Login Failed!'
			);
		}
		else
		{
			// login succeeded
			$infos['television_login'] = array(
				'name' => 'MyEpisodes Login',
				'status' => '',
				'description' => array(
					'list' => array(
						'Login to myepisodes.com successful!',
					),
				),
				'text' => 'Login Succeeded!'
			);
			
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
			
			// download episode list
			$infos['myepisodes_shows'] = array(
				'name' => 'MyEpisodes TV Shows',
				'status' => '',
				'description' => array(
					'list' => array(
						'Loading TV shows.',
					),
				),
				'text' => array(
					'loading' => 'Loading...'
				),
				'singular' => url('module=admin_tools_television&subtool=2&info_singular=true&info_singular_step_television=shows', true),
			);
		}
	}
	elseif($request['info_singular_step_television'] == 'shows')
	{
		// get list of shows
		$shows = television_myepisodes_fetch_shows();
		
		$infos['myepisodes_shows'] = array(
			'name' => 'MyEpisodes TV Shows',
			'status' => '',
			'description' => array(
				'list' => array(
					'These are all the TV Shows you are subscribed to.',
				),
			),
			'text' => 'TV Shows:<br />' . implode('<br />', $shows['all_shows'])
		);
		
		// list of shows to download
		$infos['myepisodes_episodes'] = array(
			'name' => 'New Episodes',
			'status' => '',
			'description' => array(
				'list' => array(
					'These are all the new episodes that need to be downloaded.',
				),
			),
			'text' => 'Episodes for download:<br />' . implode('<br />', $shows['new_episodes']['combined'])
		);
		
		// add an entry for each show
		foreach($shows['new_episodes']['shows'] as $i => $show)
		{
			$infos['myepisodes_shows_' . $i] = array(
				'name' => 'Searching for ' . $show,
				'status' => '',
				'description' => array(
					'list' => array(
						'Searching NZB Services for show ' . $shows['new_episodes']['combined'][$i] . '.',
					),
				),
				'text' => array(
					'loading' => 'Searching for NZB...'
				),
				'singular' => url('module=admin_tools_television&subtool=2&info_singular=true&info_singular_step_television=download' . 
					'&episode=' . $shows['new_episodes']['episodes'][$i] . 
					'&showname=' . $show . 
					'&season=' . $shows['new_episodes']['seasons'][$i] . 
					'&show_status=' . $shows['new_episodes']['status'][$i] . 
					'&show_index=' . $i
				, true),
			);
		}
	}
	elseif($request['info_singular_step_television'] == 'download')
	{
		$request['showname'] = validate_showname($request);
		$request['season'] = validate_season($request);
		$request['episode'] = validate_episode($request);
		$request['show_status'] = validate_show_status($request);
		$request['show_index'] = validate_show_index($request);
		
		// fetch nzb files
		$status = television_fetch_nzb($request['showname'], $request['season'], $request['episode']);
		
		// nzb file was found but something went terribly wrong
		if($status === -1)
		{
			$infos['myepisodes_shows_' . $request['show_index']] = array(
				'name' => 'Failed to save ' . $request['showname'],
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'Failed to save the file, but the episode ' . $request['showname'] . ' ' . $request['season'] . 'x' . $request['episode'] . ' was found successfully.',
					),
				),
				'text' => 'Failed to save.',
			);
		}
		// nzb was not found
		elseif($status === false)
		{
			$infos['myepisodes_shows_' . $request['show_index']] = array(
				'name' => 'Failed to find ' . $request['showname'],
				'status' => 'warn',
				'description' => array(
					'list' => array(
						'Failed to find the episode for ' . $request['showname'] . ' ' . $request['season'] . 'x' . $request['episode'] . '.',
					),
				),
				'text' => 'Failed to find.',
			);
		}
		// nzb was found and saved to disk
		else
		{
			$infos['myepisodes_shows_' . $request['show_index']] = array(
				'name' => 'Downloaded ' . $request['showname'],
				'status' => '',
				'description' => array(
					'list' => array(
						'The episode for ' . $request['showname'] . ' ' . $request['season'] . 'x' . $request['episode'] . ' was successfully downloaded.',
					),
				),
				'text' => 'Downloaded ' . $request['showname'] . '.',
			);
			
			// save in myepisodes
			television_myepisodes_save_status($request['show_status']);
		}
	}
	
	register_output_vars('infos', $infos);
	
	theme('tools_singular');
}

/**
 * Helper function for performing searches and downloading NZBs
 * @param showname the name of the show to search for
 * @param season the season number
 * @param episode the episode number
 * @return a status indicating if the file has been downloaded successfully
 */
function television_fetch_nzb($showname, $season, $episode)
{
	// construct search arguments
	$args = func_get_args();
	
	$services = setting('nzbservices');
	
	// loop through NZB services until we find the show
	foreach($services as $i => $config)
	{
		$search = setting('television_search_' . $i);
		
		// run query, using television search strings
		$result = fetch(sprintf($search, urlencode($showname), urlencode($season), urlencode($episode)), array(), array(), $_SESSION['nzbservices_' . $i]);
		
		// match nzbs
		$count = preg_match_all($config['match'], $result['content'], $matches);
		
		if($count > 0)
		{
			return nzbservices_fetch_nzb($matches[1][0]);
		}
	}
	
	return false;
}

/**
 * Helper function for logging in to myepisodes
 */
function television_myepisodes_login()
{
	$myepisodes = setting('myepisodes');
	$login_url = 'http://myepisodes.com/login.php?u=views.php';
	$result = fetch($login_url);
	
	$_SESSION['television_cookies'] = $result['cookies'];
	
	// login
	$result = fetch($login_url, array(
		'username' => $myepisodes['username'],
		'password' => $myepisodes['password'],
		'action' => 'Login',
	), array('referer' => $login_url), $_SESSION['television_cookies']);
	
	// store all cookies
	$_SESSION['television_cookies'] = $result['cookies'];
	
	return $result;
}

/**
 * Helper function for fetching the list of shows from myepisodes
 * @return an associative array consistion of a list of all shows and a list of shows to download
 */
function television_myepisodes_fetch_shows()
{
	// try to fetch shows with current cookies
	$result = fetch('http://myepisodes.com/views.php', array(), array(), $_SESSION['television_cookies']);
	
	// save cookies
	$_SESSION['television_cookies'] = $result['cookies'];

	// match shows
	$result = preg_match_all('/<td class="date">.*?>([^<]*?)<[\s\S]*?' . 
		'<td class="showname">.*?>([^<]*?)<[\s\S]*?' . 
		'<td class="longnumber">([^<]*?)<[\s\S]*?' . 
		'<td class="status">.*?name="([^"]*?)"( checked)*.*?>/i', $result['content'], $matches);
		
	$all_shows = array();
	$new_episodes = array(
		'times' => array(),
		'shows' => array(),
		'seasons' => array(),
		'episodes' => array(),
		'combined' => array(),
		'status' => array(),
	);
	foreach($matches[0] as $i => $match)
	{
		$all_shows[] = $matches[2][$i];
		$time = strtotime($matches[1][$i]);
		if($time < time() && $matches[5][$i] == '')
		{
			$new_episodes['times'][] = $matches[1][$i];
			$new_episodes['shows'][] = $matches[2][$i];
			$season_episode = split('x', $matches[3][$i]);
			$new_episodes['seasons'][] = intval($season_episode[0]);
			$new_episodes['episodes'][] = intval($season_episode[1]);
			$new_episodes['combined'][] = $matches[2][$i] . ' ' . $matches[3][$i];
			$new_episodes['status'][] = $matches[4][$i];
		}
	}
	$all_shows = array_unique($all_shows);
	
	return array(
		'all_shows' => $all_shows,
		'new_episodes' => $new_episodes,
	);
}

function television_myepisodes_save_status($show_status)
{
	// try to fetch shows with current cookies
	$result = fetch('http://myepisodes.com/views.php?type=save', array(
		$show_status => 'on',
		'checkboxes' => substr($show_status, 1),
		'action' => 'Save Status',
		'returnaddress' => '/views.php?',
	), array(), $_SESSION['television_cookies']);
}