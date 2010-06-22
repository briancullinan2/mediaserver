<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_nzbservices()
{
	$tools = array(
		'name' => 'NZB Services',
		'description' => 'Provides configuration for News Groups and NZB files.',
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array('nzbpath'),
		'session' => array('add_service', 'remove_service', 'reset_configuration', 'save_configuration'),
	);
	
	return $tools;
}

/**
 * Set up the list settings
 * @ingroup setup
 */
function setup_admin_tools_nzbservices()
{
	// add wrapper functions for validating a service entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_nzbservice_' . $i] = create_function('$settings', 'return setting_nzbservice($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_tools_nzbservices']['settings'][] = 'nzbservice_' . $i;
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
		'image' => '',
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
		'image' => isset($settings['nzbservice_' . $index]['image'])?$settings['nzbservice_' . $index]['image']:'',
		'login' => isset($settings['nzbservice_' . $index]['login'])?$settings['nzbservice_' . $index]['login']:'',
		'username' => isset($settings['nzbservice_' . $index]['username'])?$settings['nzbservice_' . $index]['username']:'',
		'password' => isset($settings['nzbservice_' . $index]['password'])?$settings['nzbservice_' . $index]['password']:'',
	);
	
	// validate name
	if(!($service['name'] = generic_validate_all_safe(array('service_name' => $service['name']), 'service_name')))
		return;

	// make sure there is valid regular expression
	if(!($service['match'] = generic_validate_regexp(array('service_match' => $service['match']), 'service_match')))
		return;
		
	$service['image'] = generic_validate_all_safe(array('service_image' => $service['image']), 'service_image');
	
	// validate search
	if(!($service['search'] = generic_validate_url(array('service_search' => $service['search']), 'service_search')))
		return;
		
	$service['login'] = generic_validate_url(array('service_login' => $service['login']), 'service_login');
	
	// validate username and password
	$service['username'] = generic_validate_all_safe(array('service_username' => $service['username']), 'service_username');
	$service['password'] = generic_validate_all_safe(array('service_password' => $service['password']), 'service_password');
	
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
		'match' => '/<a href="(http:\/\/nzbmatrix.com\/nzb-download.php?[^"]*?)&amp;nozip=1"/i',
		'search' => 'http://nzbmatrix.com/nzb-search.php?search=%s',
		'login' => 'http://nzbmatrix.com/account-login.php',
	));
	
	$settings['nzbservice_1'] = array_merge(isset($settings['nzbservice_1'])?$settings['nzbservice_1']:array(), array(
		'name' => 'Newzbin',
		'match' => '/<a.*?href="(\/browse\/post\/[0-9]*?\/nzb)">/i',
		'search' => 'http://www.newzbin.com/search/query/?searchaction=Go&q=%s',
		'login' => 'http://www.newzbin.com/account/login/',
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
function session_admin_tools_nzbservices($request)
{
	// reset session if it was saved
	if(isset($request['save_configuration']))
		return array();
	
	// might be configuring the module
	if(!isset($_SESSION['nzbservices']) || isset($request['reset_configuration']))
		$save = array('services' => setting('nzbservices'));
	else
		$save = $_SESSION['nzbservices'];

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
function configure_admin_tools_nzbservices($settings)
{
	$settings['nzbservices'] = setting_nzbservices($settings);
	$settings['nzbpath'] = setting_nzbpath($settings);
	
	$service_count = count($settings['nzbservices']);
	
	// load services from session
	if(isset($_SESSION['nzbservices']['services']))
	{
		$settings['nzbservices'] = $_SESSION['nzbservices']['services'];
	}
	
	$options = array();
	
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
		if($config['image'] == '')
		{
			if($address = generic_validate_hostname(array('address' => $config['search']), 'address'))
				$result = fetch($address . '/favicon.ico');
				
			if(isset($result) && $result['status'] == 200)
			{
				$config['image'] = 'data:;base64,' . base64_encode($result['content']);
			}
		}
		
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
		$options['nzbservices']['options']['setting_nzbservice_' . $i . '[image]'] = array(
			'type' => 'hidden',
			'value' => $config['image'],
		);
		if($config['login'] != '' || $config['username'] != '')
		{
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
		$options['nzbservices']['options']['setting_nzbservice_image_' . $i] = array(
			'value' => '<img src="' . $config['image'] . '" alt="" />',
			'help' => $config['name'] . ' Image',
		);
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
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_service[search]' => array(
				'type' => 'text',
				'help' => 'Search',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_service[match]' => array(
				'type' => 'text',
				'help' => 'Match Files',
				'value' => '',
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
	
	// add unsettings
	for($i = 0; $i < $service_count - count($settings['nzbservices']); $i++)
	{
		$options['custom_nzbservice']['options']['setting_nzbservice_' . (count($settings['nzbservices']) + $i)] = array(
			'type' => 'hidden',
			'value' => '',
		);
	}

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
 * Helper function
 * Provides fetch functions for logging in to the configured NZB services
 * More importantly, stores cookies in $_SESSION['nzbservices_0']
 * @return an array of status codes reported by the fetch function for each configured service
 */
function nzbservices_login()
{
	$services = setting('nzbservices');
	$stati = array();
	
	foreach($services as $i => $config)
	{
		$result = fetch($config['login']);
		
		// save session info
		$_SESSION['nzbservices_' . $i] = $result['cookies'];
		
		// extract form elements
		$result = preg_match_all('/<input.*?name=(["\'])([^\1]*?)\1.*?>/i', $result['content'], $names);
		$result = preg_match_all('/<input.*?value=(["\'])([^\1]*?)\1/i', $result['content'], $values);
		
		$post = array();
		foreach($names[2] as $j => $name)
		{
			if(isset($values[2][$j]))
				$post[$name] = $values[2][$j];
			else
				$post[$name] = '';
		}
		$post['username'] = $config['username'];
		$post['password'] = $config['password'];
		
		// submit
		$result = fetch($config['login'], $post, array(), array(), isset($_SESSION['nzbservices_' . $i])?$_SESSION['nzbservices_' . $i]:array());
		
		// save session info
		$_SESSION['nzbservices_' . $i] = $result['cookies'];
		
		$stati[$i] = $result['status'];
	}
	
	return $stati;
}

/**
 * Helper function for performing searches and downloading NZBs
 * @param search the query to perform the search
 * @return a status indicating if the file has been downloaded successfully
 */
function nzbservices_search_nzb($search)
{
	// construct search arguments
	$args = func_get_args();
	
	$services = setting('nzbservices');
	
	// loop through NZB services until we find the show
	foreach($services as $i => $config)
	{
		// run query
		$result = fetch(call_user_func_array('sprintf', array_merge(array($config['search']), $args)), array(), array(), $_SESSION['nzbservices_' . $i]);
		
		// match nzbs
		$count = preg_match_all($config['match'], $result['content'], $matches);
		
		if($count > 0)
		{
			return nzbservices_fetch_nzb($matches[1][0], $_SESSION['nzbservices_' . $i]);
		}
	}
	
	return false;
}

/**
 * Helper function for retrieving nzb files
 * @return true if the file was saved, -1 if there was an error saving the file
 */
function nzbservices_fetch_nzb($file, $cookies, $filename = '')
{
	// download and save
	$result = fetch($file, array(), array(), $cookies);
	
	// get the save path
	$path = setting('nzbpath');
	if(substr($path, -1) != '/') $path .= '/';
	
	// try creating a file name based on what is reported in the headers
	if($filename == '' && 
		isset($result['headers']['Content-disposition']) && 
		strpos($result['headers']['Content-disposition'], 'filename=') !== false)
	{
		$start = strpos($result['headers']['Content-disposition'], 'filename=') + 9;
		$filename = substr($result['headers']['Content-disposition'], $start);
		if($filename[0] == '"') $filename = trim(substr($filename, 1));
		if($filename[strlen($filename)-1] == '"') $filename = trim(substr($filename, 0, -1));
	}
	// try creating the filename based on the name in the link
	if($filename == '' && strrpos($file, '.') > strrpos($file, '/'))
	{
		$filename = basename($file);
	}
	// if all else fails, make something up
	if($filename == '')
	{
		$filename = 'selected_' . time() . '.nzb';
	}
	
	// add filename to save path
	$path .= $filename;
	
	// save the file
	if($fh = fopen($path, 'w'))
	{
		fwrite($fh, $result['content']);
		fclose($fh);
		
		return true;
	}
	else
		return -1;
}