<?php


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

