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
		'settings' => array('myepisodes', 'nzbpath'),
		'session' => array('add_service', 'remove_service', 'reset_configuration'),
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
 * Set up the list of aliases from the database
 * @ingroup setup
 */
function setup_admin_tools_television()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_nzbservice_' . $i] = create_function('$settings', 'return setting_nzbservice($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_television']['settings'][] = 'nzbservice_' . $i;
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_add_service($request)
{
	if(!isset($request['add_service']['save']))
		return;
		
	return array(
		'name' => $request['add_service']['name'],
		'match' => isset($request['add_service']['match'])?$request['add_service']['match']:'',
		'search' => isset($request['add_service']['search'])?$request['add_service']['search']:'',
		'login' => isset($request['add_service']['login'])?$request['add_service']['login']:'',
		'username' => isset($request['add_service']['username'])?$request['add_service']['username']:'',
		'password' => isset($request['add_service']['password'])?$request['add_service']['password']:'',
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_remove_service($request)
{
	if(isset($request['remove_service']))
	{
		// if it is an array because the button value is set to text instead of the index
		if(is_array($request['remove_service']))
		{
			$keys = array_keys($request['remove_service']);
			$request['remove_service'] = $keys[0];
		}
			
		if(is_numeric($request['remove_service']) && $request['remove_service'] >= 1)
			return $request['remove_service'];
	}
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
	if(isset($request['episode_number']) && is_numeric($request['episode_number']))
		return $request['episode_number'];
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_season($request)
{
	if(isset($request['season_number']) && is_numeric($request['season_number']))
		return $request['season_number'];
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
 * Implementation of setting
 * @ingroup setting
 */
function setting_nzbservice($settings, $index)
{
	// don't continue with this if stuff is missing
	if(!isset($settings['nzbservice_' . $index]) || !isset($settings['nzbservice_' . $index]['name']) || 
		!isset($settings['nzbservice_' . $index]['match']) || !isset($settings['nzbservice_' . $index]['search'])
	)
		return;

	// copy values
	$service = array(
		'name' => $settings['nzbservice_' . $index]['name'],
		'match' => $settings['nzbservice_' . $index]['match'],
		'search' => $settings['nzbservice_' . $index]['search'],
		'login' => isset($settings['nzbservice_' . $index]['login'])?$settings['nzbservice_' . $index]['login']:'',
		'username' => isset($settings['nzbservice_' . $index]['username'])?$settings['nzbservice_' . $index]['username']:'',
		'password' => isset($settings['nzbservice_' . $index]['password'])?$settings['nzbservice_' . $index]['password']:'',
	);
	
	// make sure there is valid regular expression
	
	return $service;
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_nzbservices($settings)
{
	if(!isset($settings['nzbservices']))
		$settings['nzbservices'] = array();

	$settings['nzbservice_0'] = array_merge(isset($settings['nzbservice_0'])?$settings['nzbservice_0']:array(), array(
		'name' => 'NZB Matrix',
		'match' => '/<a href="(http://nzbmatrix.com/nzb-download.php?[^"]*?)"/i',
		'search' => 'http://nzbmatrix.com/nzb-search.php?cat=6&search=%s',
		'login' => 'http://nzbmatrix.com/account-login.php',
	));
	
	// make sure all servers with numeric indexes are on the list
	for($i = 0; $i < 10; $i++)
	{
		$service = setting_nzbservice($settings, $i);
		if(isset($service))
			$settings['nzbservices'][$i] = $service;
	}

	return array_values($settings['nzbservices']);
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
function setting_nzbpath($settings)
{
	if(isset($settings['nzbpath']) && is_dir($settings['nzbpath']))
		return $settings['nzbpath'];
	else
		return '';
}

/**
 * Implementation of session
 * @ingroup session
 */
function session_admin_tools_television($request)
{
	// might be configuring the module
	if(!isset($_SESSION['television']) || isset($request['reset_configuration']))
		$save = array('services' => setting('nzbservices'));
	else
		$save = $_SESSION['television'];

	// add server
	if(isset($request['add_service']))
	{
		$new_service = setting_nzbservice(array('nzbservice_0' => $request['add_service']), 0);
		if(isset($new_service))
			$save['services'][] = $new_service;
	}

	// remove server
	if(isset($request['remove_service']))
	{
		unset($save['services'][$request['remove_service']]);
		$save['services'] = array_values($save['services']);
	}
	
	return $save;
}
	
/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_tools_television($settings)
{
	$settings['myepisodes'] = setting_myepisodes($settings);
	$settings['nzbservices'] = setting_nzbservices($settings);
	$settings['nzbpath'] = setting_nzbpath($settings);
	
	// load services from session
	if(isset($_SESSION['television']['services']))
	{
		$settings['nzbservices'] = $_SESSION['television']['services'];
	}
	
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
	
	$service_options = array();
	foreach($settings['nzbservices'] as $i => $config)
	{
		$service_options[$i] = $config['name'];
	}
	
	$options['nzbservices'] = array(
		'name' => 'NZB Service',
		'status' => '',
		'description' => array(
			'list' => array(
				'Select an NZB service to search and download from.',
				'Select multiple services to search if there are no NZBs found.',
			),
		),
		'type' => 'set',
		'options' => array(
			'setting_nzbservices' => array(
				'type' => 'multiselect',
				'options' => $service_options,
				'value' => $settings['nzbservices'],
			),
		),
	);

	foreach($settings['nzbservices'] as $i => $config)
	{
		if($config['login'] != '' || $config['username'] != '')
		{
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[name]'] = array(
				'type' => 'hidden',
				'value' => $config['name'],
			);
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[search]'] = array(
				'type' => 'hidden',
				'value' => $config['search'],
			);
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[match]'] = array(
				'type' => 'hidden',
				'value' => $config['match'],
			);
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[login]'] = array(
				'type' => 'hidden',
				'value' => $config['login'],
			);
			$options['nzbservices']['options'][] = array(
				'value' => '<br />'
			);
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[username]'] = array(
				'type' => 'text',
				'value' => $config['username'],
				'help' => $config['name'] . ' Username',
			);
			$options['nzbservices']['options'][] = array(
				'value' => '<br />'
			);
			$options['nzbservices']['options']['setting_nzbservice_' . $i . '[password]'] = array(
				'type' => 'text',
				'value' => $config['password'],
				'help' => $config['name'] . ' Password',
			);
		}
	}
	
	$options['custom_nzbservice'] = array(
		'name' => 'Add a NZB Service',
		'status' => '',
		'description' => array(
			'list' => array(
				'Add a custom NZB service.',
				'The search query is the path to a search path, with %s as the search text.',
				'The file match is the regular expression to match the links to download files.',
				'The login url is used before performing the search query.',
			),
		),
		'type' => 'set',
		'options' => array(
			'add_service[name]' => array(
				'type' => 'text',
				'help' => 'Name',
				'value' => 'NZBClub',
			),
			array(
				'value' => '<br />'
			),
			'add_service[search]' => array(
				'type' => 'text',
				'help' => 'Search',
				'value' => 'http://www.nzbclub.com/nzbsearch.aspx?ss=%s&rpp=25&rs=1&sa=1',
			),
			array(
				'value' => '<br />'
			),
			'add_service[match]' => array(
				'type' => 'text',
				'help' => 'Match Files',
				'value' => '/<a href="(/nzb_download.aspx?[^"]*?)"/i',
			),
			array(
				'value' => '<br />'
			),
			'add_service[login]' => array(
				'type' => 'text',
				'help' => 'Login URL',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_service[username]' => array(
				'type' => 'text',
				'help' => 'Username',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_service[password]' => array(
				'type' => 'text',
				'help' => 'Password',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_service[save]' => array(
				'type' => 'submit',
				'value' => 'Add Service',
			),
		),
	);
	
	if($settings['nzbpath'] == '' || is_writable($settings['nzbpath']))
	{
		$options['nzbpath'] = array(
			'name' => 'NZB Save path',
			'status' => '',
			'description' => array(
				'list' => array(
					'Select a path to save the NZB files to.',
					'Some NZB download programs such as SABnzbd or NewzLeecher can automatically search a directory for new NZB files.'
				),
			),
			'type' => 'text',
			'value' => $settings['nzbpath'],
		);
	}
	else
	{
		$options['nzbpath'] = array(
			'name' => 'NZB Save path',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The selected path is not writable!',
					'Select a path to save the NZB files to.',
					'Some NZB download programs such as SABnzbd or NewzLeecher can automatically search a directory for new NZB files.'
				),
			),
			'type' => 'text',
			'value' => $settings['nzbpath'],
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
		// output configuration on same page as tool output
		$request['configure_module'] = validate_configure_module(array('configure_module' => 'admin_tools_television'));
		output_admin_modules($request);
	
		// perform television downloading
		if(dependency('snoopy_installed') == false)
		{
			$infos['snoopy_installed'] = array(
				'name' => 'Snoopy Not Installed',
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'The system has detected that Snoopy cUrl API is NOT INSTALLED.',
						'The Snoopy class (Snoopy.class.php) must be placed in &lt;site root&gt;/include/',
						'Snoopy is used to download content from myepisodes.com, and the specified NZB services.',
					),
				),
				'value' => array(
					'link' => array(
						'url' => 'http://sourceforge.net/projects/snoopy/',
						'text' => 'Get Snoopy',
					),
				),
			);
		}
		else
		{
			// log in to myepisodes
			$infos['myepisodes_login'] = array(
				'name' => 'MyEpisodes Login',
				'status' => '',
				'description' => array(
					'list' => array(
						'Loggin in to myepisodes.com.',
					),
				),
				'text' => array(
					'loading' => 'Loading...'
				),
				'singular' => url('module=admin_tools_television&subtool=2&info_singular=true&info_singular_step_television=login', true),
			);
			
			// parse TV show names
		
			// loop through each show and search on the specified services
			
			// save NZB to specified directory
		}
	}
	
	// output info
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
	
	theme('tools_subtools');
}

function output_admin_tools_television_singular($request)
{
	$request['info_singular_step_television'] = validate_info_singular_step_television($request);
	$infos = array();
	
	if($request['info_singular_step_television'] == 'login')
	{
		television_myepisodes_login();
		if($GLOBALS['snoopy']->status != 200)
		{
			$infos['myepisodes_login'] = array(
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
			$infos['myepisodes_login'] = array(
				'name' => 'MyEpisodes Login',
				'status' => '',
				'description' => array(
					'list' => array(
						'Login to myepisodes.com successful!',
					),
				),
				'text' => 'Login Succeeded!'
			);
			
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
				'singular' => url('module=admin_tools_television&subtool=2&info_singular=true&info_singular_step_television=download&episode=' . $shows['new_episodes']['episodes'][$i] . '&showname=' . $show . '&season=' . $shows['new_episodes']['seasons'][$i], true),
			);
			break;
		}
	}
	elseif($request['info_singular_step_television'] == 'download')
	{
		$request['showname'] = validate_showname($request);
		$request['season'] = validate_season($request);
		$request['episode'] = validate_episode($request);
		
		$status = television_fetch_nzb($request['showname'], $request['season'], $request['episode']);
	}
	
	register_output_vars('infos', $infos);
	
	theme('tools_singular');
}

function television_fetch_nzb($showname, $season, $episode)
{
	$services = setting('nzbservices');
	$GLOBALS['snoopy']->agent = 'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1)';
	
	// loop through NZB services until we find the show
	foreach($services as $i => $config)
	{
		// already logged in
		if(isset($_SESSION['television_service_' . $i]) || !$config['login'])
		{
			// load cookies from session
			$GLOBALS['snoopy']->cookies = $_SESSION['television_service_' . $i];
		}
		else
		{
			$GLOBALS['snoopy']->accept = 'text/html';
			$GLOBALS['snoopy']->fetch($config['login']);
			
			television_save_session();
			
			// extract form elements
			$result = preg_match_all('/<input.*?name=(["\'])([^\1]*?)\1/i', $GLOBALS['snoopy']->results, $names);
			$result = preg_match_all('/<input.*?value=(["\'])([^\1]*?)\1/i', $GLOBALS['snoopy']->results, $values);
			
			// submit
			$GLOBALS['snoopy']->httpmethod = 'POST';
			$GLOBALS['snoopy']->submit($config['login'], array(
				'username' => $config['username'],
				'password' => $config['password'],
				'action' => 'Login',
			));
			
			print_r($GLOBALS['snoopy']->headers);
		}
		
		// run query
		//$GLOBALS['snoopy']->fetch($shows_url);
		
		// match nzbs
		
		// download and save
	}
}

function television_myepisodes_login()
{
	$myepisodes = setting('myepisodes');
	$login_url = 'http://myepisodes.com/login.php?u=views.php';
	$GLOBALS['snoopy']->fetch($login_url);
	
	television_save_session();
	
	// login
	$GLOBALS['snoopy']->referer = $login_url;
	$GLOBALS['snoopy']->httpmethod = 'POST';
	$GLOBALS['snoopy']->submit($login_url, array(
		'username' => $myepisodes['username'],
		'password' => $myepisodes['password'],
		'action' => 'Login',
	));
	
	// store all cookies
	$_SESSION['television_cookies'] = $GLOBALS['snoopy']->cookies;
}

function television_save_session()
{
	// store session id in current cookies
	foreach($GLOBALS['snoopy']->headers as $i => $header)
	{
		if(strtolower(substr($header, 0, 11)) == 'set-cookie:')
		{
			$cookie = split(';', substr($header, 12));
			$cookie_name_val = split('=', $cookie[0]);
			$GLOBALS['snoopy']->cookies[$cookie_name_val[0]] = $cookie_name_val[1];
		}
	}
	
}

function television_myepisodes_fetch_shows()
{
	// try to fetch shows with current cookies
	$shows_url = 'http://myepisodes.com/views.php';
	$GLOBALS['snoopy']->agent = 'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1)';
	$GLOBALS['snoopy']->cookies = $_SESSION['television_cookies'];
	$GLOBALS['snoopy']->fetch($shows_url);

	// match shows
	$result = preg_match_all('/<td class="date">.*?>([^<]*?)<[\s\S]*?<td class="showname">.*?>([^<]*?)<[\s\S]*?<td class="longnumber">([^<]*?)</i', $GLOBALS['snoopy']->results, $matches);
	
	$all_shows = array();
	$new_episodes = array(
		'times' => array(),
		'shows' => array(),
		'seasons' => array(),
		'episodes' => array(),
		'combined' => array(),
	);
	foreach($matches[0] as $i => $match)
	{
		$all_shows[] = $matches[2][$i];
		$time = strtotime($matches[1][$i]);
		if($time < time())
		{
			$new_episodes['times'][] = $matches[1][$i];
			$new_episodes['shows'][] = $matches[2][$i];
			$season_episode = split('x', $matches[3][$i]);
			$new_episodes['seasons'][] = intval($season_episode[0]);
			$new_episodes['episodes'][] = intval($season_episode[1]);
			$new_episodes['combined'][] = $matches[2][$i] . ' ' . $matches[3][$i];
		}
	}
	$all_shows = array_unique($all_shows);
	
	return array(
		'all_shows' => $all_shows,
		'new_episodes' => $new_episodes,
	);
}