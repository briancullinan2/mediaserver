<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_tv_downloader()
{
	$tools = array(
		'name' => 'TV Downloader',
		'description' => 'Automatically download episodes selected on MyEpisodes from NZB or torrent services to a specified folder.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('myepisodes'),
		'template' => false,
	);
	
	return $tools;
}

/**
 * Set up the list of settings
 * @ingroup setup
 */
function setup_admin_tools_tv_downloader()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_nzb_television_search_' . $i] = create_function('$settings', 'return setting_nzb_television_search($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_tv_downloader']['settings'][] = 'television_nzb_search_' . $i;
	}
	
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_tor_television_search_' . $i] = create_function('$settings', 'return setting_tor_television_search($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_tv_downloader']['settings'][] = 'television_tor_search_' . $i;
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
function setting_nzb_television_search($settings, $index)
{
	// return the same static service as listed in the nzbservice module
	if($index == 0)
		return 'http://nzbmatrix.com/nzb-search.php?cat=6&search=%s%%20s%02de%02d';
	if($index == 1)
		return 'http://www.newzbin.com/search/query/?searchaction=Go&category=8&q=%s%%20%dx%02d';

	// don't continue with this if stuff is missing
	if(isset($settings['nzb_television_search_' . $index]) && 
		$settings['nzb_television_search_' . $index] != ''
	)
		return $settings['nzb_television_search_' . $index];
	// use default
	elseif(isset($settings['nzbservice_' . $index]['search']) && 
		$settings['nzbservice_' . $index]['search'] != ''
	)
		return $settings['nzbservice_' . $index]['search'];
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_tor_television_search($settings, $index)
{
	// the static services to not have movies, only tv and music
	if($index == 0 || $index == 1)
		return '';
	if($index == 2)
		return 'http://tvtorrents.com/loggedin/search.do?search=%s %d %02d';

	// don't continue with this if stuff is missing
	if(isset($settings['tor_movie_search_' . $index]) && 
		$settings['tor_movie_search_' . $index] != ''
	)
		return $settings['tor_movie_search_' . $index];
	// use default
	elseif(isset($settings['torservice_' . $index]['search']) && 
		$settings['torservice_' . $index]['search'] != ''
	)
		return $settings['torservice_' . $index]['search'];
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
	return generic_validate_numeric($request, 'episode');
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_season($request)
{
	return generic_validate_numeric($request, 'season');
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_show_index($request)
{
	return generic_validate_numeric($request, 'show_index');
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_showname($request)
{
	return generic_validate_all_safe($request, 'showname');
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_show_status($request)
{
	if(isset($request['show_status']) && $request['show_status'] != '' && preg_match('/^[a-z0-9-]*$/i', $request['show_status']) != 0)
		return $request['show_status'];
}
	
/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_tools_tv_downloader($settings)
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
				'name' => 'Username',
				'value' => $settings['myepisodes']['username'],
			),
			array(
				'value' => '<br />'
			),
			'setting_myepisodes[password]' => array(
				'type' => 'text',
				'name' => 'Password',
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
		$options['nzbservices']['options']['setting_nzb_television_search_' . $i] = array(
			'type' => 'hidden',
			'value' => setting_nzb_television_search($settings, $i),
		);
	}
	
	// add torrent services
	$options = array_merge($options, configure_admin_tools_torservices($settings));
	
	// alter the torservices form to use television search queries instead
	$settings['torservices'] = setting_torservices($settings);
	foreach($settings['torservices'] as $i => $config)
	{
		$options['torservices']['options']['setting_tor_television_search_' . $i] = array(
			'type' => 'hidden',
			'value' => setting_tor_television_search($settings, $i),
		);
	}
	
	return $options;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_tv_downloader($request)
{
	$request['info_singular'] = validate($request, 'info_singular');
	$infos = array();
	
	if($request['info_singular'] == true)
	{
		output_admin_tools_television_singular($request);
		
		return;
	}
	
	// output configuration link
	raise_error('You may need to <a href="' . url('module=admin_modules&configure_module=admin_tools_tv_downloader') . '">configure</a> this tool in order to use it properly.', E_WARN);

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
			'singular' => url('module=admin_tools_tv_downloader&info_singular=true&info_singular_step_television=login', true),
		);
	}
	
	// output info
	register_output_vars('infos', $infos);
	
	theme('tool_info');
}

/**
 * Helper function for outputting a single piece of the infos
 * @param request the request to process
 */
function output_admin_tools_television_singular($request)
{
	$request['info_singular_step_television'] = validate($request, 'info_singular_step_television');
	$infos = array();
	
	if($request['info_singular_step_television'] == 'login')
	{
		// log in to my episodes first
		$result = television_myepisodes_login();
		if($result['status'] == false)
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
			$infos = array_merge(nzbservices_singular_result(), $infos);
			
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
				'singular' => url('module=admin_tools_tv_downloader&info_singular=true&info_singular_step_television=shows', true),
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
				'singular' => url('module=admin_tools_tv_downloader&info_singular=true&info_singular_step_television=download' . 
					'&episode=' . urlencode($shows['new_episodes']['episodes'][$i]) . 
					'&showname=' . urlencode($show) . 
					'&season=' . urlencode($shows['new_episodes']['seasons'][$i]) . 
					'&show_status=' . urlencode($shows['new_episodes']['status'][$i]) . 
					'&show_index=' . $i
				, true),
			);
		}
	}
	elseif($request['info_singular_step_television'] == 'download')
	{
		$request['showname'] = validate($request, 'showname');
		$request['season'] = validate($request, 'season');
		$request['episode'] = validate($request, 'episode');
		$request['show_status'] = validate($request, 'show_status');
		$request['show_index'] = validate($request, 'show_index');
		
		// fetch nzb files
		$status = television_fetch_nzb($request['showname'], $request['season'], $request['episode']);
		
		$show_title = htmlspecialchars($request['showname']) . ' ' . 
			htmlspecialchars($request['season']) . 'x' . 
			htmlspecialchars($request['episode']);
		
		// nzb file was found but something went terribly wrong
		if($status === -1)
		{
			$infos['myepisodes_shows_' . $request['show_index']] = array(
				'name' => 'Failed to save ' . $request['showname'],
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'Failed to save the file, but the episode ' . $show_title . ' was found successfully.',
					),
				),
				'text' => 'Failed to save.',
			);
		}
		// nzb was not found
		elseif($status === false)
		{
			$services = setting('nzbservices');
			$links = '';
			foreach($services as $i => $config)
			{
				$search = setting('nzb_television_search_' . $i);
				
				$links .= '<a href="' . sprintf($search, urlencode($request['showname']), urlencode($request['season']), urlencode($request['episode'])) . '">Search for ' . $request['showname'] . ' on ' . $config['name'] . '</a><br />';
			}
			$infos['myepisodes_shows_' . $request['show_index']] = array(
				'name' => 'Failed to find ' . $request['showname'],
				'status' => 'warn',
				'description' => array(
					'list' => array(
						'Failed to find the episode for ' . $show_title . '.',
					),
				),
				'text' => 'Failed to find:<br />' . $links,
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
						'The episode for ' . $show_title . ' was successfully downloaded.',
					),
				),
				'text' => 'Downloaded ' . htmlspecialchars($request['showname']) . '.',
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
		$search = setting('nzb_television_search_' . $i);
		
		// run query, using television search strings
		$result = fetch(sprintf($search, urlencode($showname), urlencode($season), urlencode($episode)), array(), array(), session('nzbservices_' . $i));
		
		// match nzbs
		$count = preg_match_all($config['match'], $result['content'], $matches);
		
		if($count > 0)
		{
			if($address = generic_validate_hostname(array('address' => $matches[1][0]), 'address'))
				$file = $matches[1][0];
			else
			{
				if($address = generic_validate_hostname(array('address' => $config['search']), 'address'))
					$file = $address . '/' . $matches[1][0];
				else
					$file = $matches[1][0];
			}
				
			// download and save
			$result = fetch($file, array(), array(), session('nzbservices_' . $i));
			
			if(strlen($result['content']) == 0)
				return false;
			
			$path = setting('nzbpath');
			if(substr($path, -1) != '/') $path .= '/';
			if(isset($result['headers']['Content-disposition']) && strpos($result['headers']['Content-disposition'], 'filename=') !== false)
			{
				$start = strpos($result['headers']['Content-disposition'], 'filename=') + 9;
				$filename = substr($result['headers']['Content-disposition'], $start);
				if($filename[0] == '"') $filename = trim(substr($filename, 1));
				if($filename[strlen($filename)-1] == '"') $filename = trim(substr($filename, 0, -1));
			}
			else
			{
				$filename = $showname . ' Season ' . $season . ' Episode ' . $episode . '.nzb';
			}
			$path .= $filename;

			if($fh = fopen($path, 'w'))
			{
				fwrite($fh, $result['content']);
				fclose($fh);
				
				return true;
			}
			else
				return -1;
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
	
	session('television_cookies', $result['cookies']);
	
	// login
	$result = fetch($login_url, array(
		'username' => $myepisodes['username'],
		'password' => $myepisodes['password'],
		'action' => 'Login',
	), array('referer' => $login_url), session('television_cookies'));

	// check for failure
	if(preg_match('/<div class="warning">/i', $result['content']) != 0)
		$result['status'] = false;
	
	// store all cookies
	session('television_cookies', $result['cookies']);
	
	return $result;
}

/**
 * Helper function for fetching the list of shows from myepisodes
 * @return an associative array consistion of a list of all shows and a list of shows to download
 */
function television_myepisodes_fetch_shows()
{
	// try to fetch shows with current cookies
	$result = fetch('http://myepisodes.com/views.php', array(), array(), session('television_cookies'));
	
	// save cookies
	session('television_cookies', $result['cookies']);

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
	), array(), session('television_cookies'));
}