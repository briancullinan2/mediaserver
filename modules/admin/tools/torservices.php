<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_torservices()
{
	$tools = array(
		'name' => 'Torrent Services',
		'description' => 'Provides configuration for Torrenting websites and torrent files.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('torpath'),
		'session' => array('add_torservice', 'remove_torservice', 'reset_configuration', 'save_configuration'),
	);
	
	return $tools;
}

/**
 * Set up the list settings
 * @ingroup setup
 */
function setup_admin_tools_torservices()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_torservice_' . $i] = create_function('$settings', 'return setting_torservice($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_torservices']['settings'][] = 'torservice_' . $i;
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_add_torservice($request)
{
	if(!isset($request['add_torservice']['save']))
		return;
		
	return array(
		'name' => $request['add_torservice']['name'],
		'match' => isset($request['add_torservice']['match'])?$request['add_torservice']['match']:'',
		'search' => isset($request['add_torservice']['search'])?$request['add_torservice']['search']:'',
		'image' => '',
		'login' => isset($request['add_torservice']['login'])?$request['add_torservice']['login']:'',
		'username' => isset($request['add_torservice']['username'])?$request['add_torservice']['username']:'',
		'password' => isset($request['add_torservice']['password'])?$request['add_torservice']['password']:'',
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_remove_torservice($request)
{
	if(isset($request['remove_torservice']))
	{
		// if it is an array because the button value is set to text instead of the index
		if(is_array($request['remove_torservice']))
		{
			$keys = array_keys($request['remove_torservice']);
			$request['remove_torservice'] = $keys[0];
		}
			
		if(is_numeric($request['remove_torservice']) && $request['remove_torservice'] >= 3)
			return $request['remove_torservice'];
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_torservice($settings, $index)
{
	// don't continue with this if stuff is missing
	if(!isset($settings['torservice_' . $index]) || !isset($settings['torservice_' . $index]['name']) || 
		!isset($settings['torservice_' . $index]['match']) || !isset($settings['torservice_' . $index]['search'])
	)
		return;

	// copy values
	$service = array(
		'name' => $settings['torservice_' . $index]['name'],
		'match' => $settings['torservice_' . $index]['match'],
		'search' => isset($settings['torservice_' . $index]['search'])?$settings['torservice_' . $index]['search']:'',
		'image' => isset($settings['torservice_' . $index]['image'])?$settings['torservice_' . $index]['image']:'',
		'login' => isset($settings['torservice_' . $index]['login'])?$settings['torservice_' . $index]['login']:'',
		'username' => isset($settings['torservice_' . $index]['username'])?$settings['torservice_' . $index]['username']:'',
		'password' => isset($settings['torservice_' . $index]['password'])?$settings['torservice_' . $index]['password']:'',
		'userfield' => isset($settings['torservice_' . $index]['userfield'])?$settings['torservice_' . $index]['userfield']:'',
		'passfield' => isset($settings['torservice_' . $index]['passfield'])?$settings['torservice_' . $index]['passfield']:'',
		'loginfail' => isset($settings['torservice_' . $index]['loginfail'])?$settings['torservice_' . $index]['loginfail']:'',
		'exclude' => isset($settings['torservice_' . $index]['exclude'])?$settings['torservice_' . $index]['exclude']:'',
	);
	
	// validate name
	if(!($service['name'] = generic_validate_all_safe(array('service_name' => $service['name']), 'service_name')))
		return;

	// make sure there is valid regular expression
	if(!($service['match'] = generic_validate_regexp(array('service_match' => $service['match']), 'service_match')))
		return;
		
	$service['image'] = generic_validate_all_safe(array('service_image' => $service['image']), 'service_image');
	
	// validate search
	$service['search'] = generic_validate_url(array('service_search' => $service['search']), 'service_search');
		
	$service['login'] = generic_validate_url(array('service_login' => $service['login']), 'service_login');
	
	// validate username and password
	$service['username'] = generic_validate_all_safe(array('service_username' => $service['username']), 'service_username');
	$service['password'] = generic_validate_all_safe(array('service_password' => $service['password']), 'service_password');
	
	// validate extra fields
	$service['userfield'] = generic_validate_all_safe(array('service_userfield' => $service['userfield']), 'service_userfield');
	$service['passfield'] = generic_validate_all_safe(array('service_passfield' => $service['passfield']), 'service_passfield');
	$service['loginfail'] = generic_validate_regexp(array('service_loginfail' => $service['loginfail']), 'service_loginfail');
	$service['exclude'] = generic_validate_all_safe(array('service_exclude' => $service['exclude']), 'service_exclude');
	
	return $service;
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_torservices($settings)
{
	if(!isset($settings['torservices']))
		$settings['torservices'] = array();

	$settings['torservice_0'] = array_merge(isset($settings['torservice_0'])?$settings['torservice_0']:array(), array(
		'name' => 'What.CD',
		'match' => '/<a href="(torrents.php?action=download[^"]*?)"/i',
		'search' => '',
		'login' => 'http://what.cd/login.php',
		'loginfail' => '<span class="warning">',
		'exclude' => 'keeplogged'
	));
	
	$settings['torservice_1'] = array_merge(isset($settings['torservice_1'])?$settings['torservice_1']:array(), array(
		'name' => 'Waffles.FM',
		'match' => '/<a href="(\/download.php\/[^"]*?)"/i',
		'search' => '',
		'login' => 'https://www.waffles.fm/w/index.php?title=Special%3AUserLogin&returnto=Main_Page',
		'userfield' => 'wpName',
		'passfield' => 'wpPassword',
		'loginfail' => '/<h2>Login error<\/h2>/i',
		'exclude' => 'wpMailmypassword,autoLogout,useSSL',
	));
	
	$settings['torservice_2'] = array_merge(isset($settings['torservice_2'])?$settings['torservice_2']:array(), array(
		'name' => 'TvTorrents',
		'match' => '/<a href="" onclick="return loadTorrent\(\'([^\']*?)\'/i',
		'search' => '',
		'login' => 'http://tvtorrents.com/',
		'loginfail' => '/<font class="error">/i',
		'exclude' => 'cookie'
	));
	
	
	// make sure all servers with numeric indexes are on the list
	for($i = 0; $i < 10; $i++)
	{
		$service = setting_torservice($settings, $i);
		if(isset($service))
			$settings['torservices'][$i] = $service;
	}

	return array_values($settings['torservices']);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_torpath($settings)
{
	if(isset($settings['torpath']) && is_dir($settings['torpath']))
		return $settings['torpath'];
	else
		return '';
}

/**
 * Implementation of session
 * @ingroup session
 */
function session_admin_tools_torservices($request)
{
	// reset session if it was saved
	if(isset($request['save_configuration']))
		return array();
	
	// might be configuring the module
	if(!($save = session('torservices')) || isset($request['reset_configuration']))
		$save = array('services' => setting('torservices'));

	// add server
	if(isset($request['add_torservice']))
	{
		$new_service = setting_torservice(array('torservice_0' => $request['add_torservice']), 0);
		if(isset($new_service))
			$save['services'][] = $new_service;
	}

	// remove server
	if(isset($request['remove_torservice']))
	{
		unset($save['services'][$request['remove_torservice']]);
		$save['services'] = array_values($save['services']);
	}
	
	return $save;
}
	
/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_tools_torservices($settings)
{
	$settings['torservices'] = setting('torservices');
	$settings['torpath'] = setting('torpath');

	$service_count = count($settings['torservices']);
	
	// load services from session
	if($session_services = session('torservices'))
	{
		$settings['torservices'] = $session_services['services'];
	}
	
	$options = array();
	
	$service_options = array();
	foreach($settings['torservices'] as $i => $config)
	{
		$service_options[$i] = $config['name'];
	}
	
	$options['torservices'] = array(
		'name' => 'Manage Torrent Services',
		'status' => '',
		'description' => array(
			'list' => array(
				'Select a torrent service to search and download from.',
				'Select multiple services to search if there are no torrents found.',
			),
		),
		'type' => 'set',
		'options' => array(
			'setting_torservices' => array(
				'type' => 'multiselect',
				'options' => $service_options,
				'value' => $settings['torservices'],
			),
		),
	);

	foreach($settings['torservices'] as $i => $config)
	{
		if($config['image'] == '')
		{
			if($address = generic_validate_hostname(array('address' => ($config['search'] != '')?$config['search']:$config['login']), 'address'))
				$result = fetch($address . '/favicon.ico');
				
			if(isset($result) && $result['status'] == 200)
			{
				$config['image'] = 'data:;base64,' . base64_encode($result['content']);
			}
		}
		
		$options['torservices']['options']['setting_torservice_' . $i . '[name]'] = array(
			'type' => 'hidden',
			'value' => $config['name'],
		);
		$options['torservices']['options']['setting_torservice_' . $i . '[search]'] = array(
			'type' => 'hidden',
			'value' => $config['search'],
		);
		$options['torservices']['options']['setting_torservice_' . $i . '[match]'] = array(
			'type' => 'hidden',
			'value' => $config['match'],
		);
		$options['torservices']['options']['setting_torservice_' . $i . '[image]'] = array(
			'type' => 'hidden',
			'value' => $config['image'],
		);
		if($config['login'] != '' || $config['username'] != '')
		{
			$options['torservices']['options']['setting_torservice_' . $i . '[login]'] = array(
				'type' => 'hidden',
				'value' => $config['login'],
			);
			$options['torservices']['options'][] = array(
				'value' => '<br />'
			);
			$options['torservices']['options']['setting_torservice_' . $i . '[username]'] = array(
				'type' => 'text',
				'value' => $config['username'],
				'name' => $config['name'] . ' Username',
			);
			$options['torservices']['options'][] = array(
				'value' => '<br />'
			);
			$options['torservices']['options']['setting_torservice_' . $i . '[password]'] = array(
				'type' => 'text',
				'value' => $config['password'],
				'name' => $config['name'] . ' Password',
			);
			$options['torservices']['options'][] = array(
		value' => '<br />'
			);
		}
		$options['torservices']['options']['setting_torservice_image_' . $i] = array(
			'value' => '<img src="' . $config['image'] . '" alt="" />',
			'name' => $config['name'] . ' Image',
		);
	}
	
	if($settings['torpath'] == '' || is_writable($settings['torpath']))
	{
		$options['torpath'] = array(
			'name' => 'Torrent Save path',
			'status' => '',
			'description' => array(
				'list' => array(
					'Select a path to save the torrent files to.',
					'Some torrent clients such as uTorrent can automatically search a directory for new torrent files.'
				),
			),
			'type' => 'text',
			'value' => $settings['torpath'],
		);
	}
	else
	{
		$options['torpath'] = array(
			'name' => 'Torrent Save path',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The selected path is not writable!',
					'Select a path to save the torrent files to.',
					'Some torrent clients such as uTorrent can automatically search a directory for new torrent files.'
				),
			),
			'type' => 'text',
			'value' => $settings['torpath'],
		);
	}
	
	return $options;
}

/**
 * Helper function
 * Provides fetch functions for logging in to the configured torrent services
 * More importantly, stores cookies in $_SESSION['torservices_0']
 * @return an array of status codes reported by the fetch function for each configured service
 */
function torservices_login()
{
	$services = setting('torservices');
	$stati = array();
	
	foreach($services as $i => $config)
	{
		$result = fetch($config['login']);
		
		// save session info
		session('torservices_' . $i, $result['cookies']);
		
		if(isset($config['userfield']))
		{
			list($login, $post) = get_login_form($result['content'], $config['userfield']);
			
			// set username
			$post[$config['userfield']] = $config['username'];
		}
		else
		{
			list($login, $post) = get_login_form($result['content']);
			
			// set username
			$post['username'] = $config['username'];
		}
			
		// set password
		if(isset($config['passfield']))
			$post[$config['passfield']] = $config['password'];
		else
			$post['password'] = $config['password'];
			
		// remove excluded fields
		if(isset($config['exclude']))
		{
			$exclude = split(',', $config['exclude']);
			foreach($exclude as $j => $remove)
			{
				if(isset($post[$remove]))
					unset($post[$remove]);
			}
		}
		
		// submit
		$session_service = session('torservices_' . $i);
		
		// set second login location
		if($login != '')
			$config['login'] = get_full_url($config['login'], $login);

		$result = fetch(get_full_url($config['login'], $login), $post, array('referer' => $config['login']), isset($session_service)?$session_service:array());

		// save session info
		session('torservices_' . $i, $result['cookies']);

		// try to check if the login was successful
		if(isset($config['loginfail']))
		{
			if(preg_match($config['loginfail'], $result['content']) != 0)
				$stati[$i] = false;
			else
				$stati[$i] = $result['status'];
		}
		elseif(preg_match('/access denied|error/i', $result['content']) != 0)
		{
			$stati[$i] = false;
		}
		else
			$stati[$i] = $result['status'];
	}
	
	return $stati;
}
 
/**
 * Helper function, creates a return for singular based on the login results
 */
function torservices_singular_result()
{
	$infos = array();
	
	// log in to services here
	$services = setting('torservices');
	$results = torservices_login();
	foreach($results as $i => $result)
	{
		if($result == false)
		{
			$infos['torservice_' . $i] = array(
				'name' => $services[$i]['name'] . ' Login Failed',
				'status' => 'fail',
				'description' => array(
					'list' => array(
						'Login to ' . $services[$i]['login'] . ' for ' . $services[$i]['name'] . ' failed!',
					),
				),
				'text' => (is_numeric($result)?'Login Failed!':$result),
			);
		}
		else
		{
			$infos['torservice_' . $i] = array(
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

	return $infos;
}

