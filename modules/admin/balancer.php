<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_balancer()
{
	return array(
		'name' => lang('balancer title', 'Load Balancer'),
		'description' => lang('balancer description', 'Allows configuring of mirror servers running and moving connections from one server to another.'),
		'privilage' => 10,
		'path' => __FILE__,
		'settings' => array(),
		'depends on' => array('settings', 'snoopy_installed'),
		'session' => array('add_server', 'remove_server', 'reset_configuration'),
	);
}

/**
 * Set up the list of aliases from the database
 * @ingroup setup
 */
function setup_admin_balancer()
{
	// add wrapper functions for validating a server entry
	for($i = 0; $i < 5; $i++)
	{
		$GLOBALS['setting_balance_server_' . $i] = create_function('$settings', 'return setting_balance_server($settings, \'' . $i . '\');');
		$GLOBALS['modules']['admin_balancer']['settings'][] = 'balance_server_' . $i;
	}
	
	// use snoopy to download config from remote sites

	// execute redirect here, so as not to waste anymore time
	
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_remove_server($request)
{
	if(isset($request['remove_server']))
	{
		// if it is an array because the button value is set to text instead of the index
		if(is_array($request['remove_server']))
		{
			$keys = array_keys($request['remove_server']);
			$request['remove_server'] = $keys[0];
		}
			
		if(is_numeric($request['remove_server']) && $request['remove_server'] >= 0)
			return $request['remove_server'];
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_add_server($request)
{
	if(!isset($request['add_server']['save']))
		return;
		
	return array(
		'address' => $request['add_server']['address'],
		'protocol' => isset($request['add_server']['protocol'])?$request['add_server']['protocol']:'https',
		'username' => isset($request['add_server']['username'])?$request['add_server']['username']:'',
		'password' => isset($request['add_server']['password'])?$request['add_server']['password']:'',
		'nickname' => isset($request['add_server']['nickname'])?$request['add_server']['nickname']:'',
	);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_balance_server($settings, $index)
{
	// don't continue with this if stuff is missing
	if(!isset($settings['balance_server_' . $index]) || !isset($settings['balance_server_' . $index]['address']) || 
		!isset($settings['balance_server_' . $index]['protocol']) || !isset($settings['balance_server_' . $index]['username']) ||
		!isset($settings['balance_server_' . $index]['password'])
	)
		return;
		
	// copy values
	$server = array(
		'address' => $settings['balance_server_' . $index]['address'],
		'protocol' => $settings['balance_server_' . $index]['protocol'],
		'username' => $settings['balance_server_' . $index]['username'],
		'password' => $settings['balance_server_' . $index]['password'],
		'nickname' => isset($settings['balance_server_' . $index]['nickname'])?$settings['balance_server_' . $index]['nickname']:'',
	);
		
	// validate each part
	if($server['protocol'] != 'http' && $server['protocol'] != 'https')
		return;
		
	// validate address
	if(preg_match('/\b((?#domain)[-A-Z0-9.]+)((?#file)\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?((?#parameters)\?[A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $server['address']) === false)
		return;
	
	// username and password will be validated when used
	
	// make sure nickname isn't blank
	if(isset($server['nickname']) && $server['nickname'] == '')
		unset($server['nickname']);
		
	return $server;
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_balance_rule($settings, $index)
{
	// don't continue with this if stuff is missing
	if(!isset($settings['balance_rule_' . $index])
	)
		return;
		
	// copy values
	$rule = array(
	);
		
	return $rule;
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_balance_servers($settings)
{
	if(!isset($settings['balance_servers']))
		$settings['balance_servers'] = array();
	
	// make sure all servers with numeric indexes are on the list
	for($i = 0; $i < 5; $i++)
	{
		$balancer = setting_balance_server($settings, $i);
		if(isset($balancer))
			$settings['balance_servers'][$i] = $balancer;
	}
	
	return array_values($settings['balance_servers']);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_balance_rules($settings)
{
	if(!isset($settings['balance_rules']))
		$settings['balance_rules'] = array();
	
	// make sure all servers with numeric indexes are on the list
	for($i = 0; $i < 50; $i++)
	{
		$rule = setting_balance_rule($settings, $i);
		if(isset($rule))
			$settings['balance_rules'][$i] = $rule;
	}
	
	return array_values($settings['balance_rules']);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_admin_balancer($settings)
{
	$settings['balance_servers'] = setting_balance_servers($settings);
	
	// check servers are up and running, get configurations
	$status = array();

	// display new server
	if(isset($_SESSION['balancer']['add']))
	{
		foreach($_SESSION['balancer']['add'] as $i => $server)
		{
			$settings['balance_servers'][] = setting_balance_server(array('balance_server_' . count($settings['balance_servers']) => $server), count($settings['balance_servers']));
		}
	}
	
	foreach($settings['balance_servers'] as $i => $server)
	{
		$status['balance_server_' . $i] = array(
			'name' => isset($server['nickname'])?$server['nickname']:('Balance Server ' . $i),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('balence server status description', 'This server is up and running and ready for balancing.'),
				),
			),
			'value' => $server['protocol'] . '://' . $server['address'],
		);
	}
	
	return $status;
}

/**
 * Implementation of session
 * @ingroup session
 */
function session_admin_balancer($request)
{
	if(!isset($_SESSION['balancer']) || isset($request['reset_configuration']))
		$save = array('servers' => setting('balance_servers'));
	else
		$save = $_SESSION['balancer'];

	if(isset($request['add_server']))
	{
		$new_server = setting_balance_server(array('balance_server_0' => $request['add_server']), 0);
		if(isset($new_server))
			$save['servers'][] = $new_server;
	}

	if(isset($request['remove_server']))
	{
		unset($save['servers'][$request['remove_server']]);
		$save['servers'] = array_values($save['servers']);
	}
	
	return $save;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_admin_balancer($settings, $request)
{
	$settings['balance_servers'] = setting_balance_servers($settings);
	$settings['balance_rules'] = setting_balance_rules($settings);
	
	// load servers from session
	if(isset($_SESSION['balancer']['servers']))
	{
		$settings['balance_servers'] = $_SESSION['balancer']['servers'];
	}
	
	$options = array();
	
	// display remove options
	if(count($settings['balance_servers']) > 0)
	{
		$options['manage_servers'] = array(
			'name' => 'Manage Servers',
			'status' => '',
			'description' => array(
				'list' => array(
					'Manage the balance servers.',
				),
			),
			'type' => 'set',
		);
		
		$balence_options = array();
		foreach($settings['balance_servers'] as $i => $server)
		{
			$balence_options['setting_balance_server_' . $i . '[address]'] = array(
				'type' => 'hidden',
				'value' => $server['address'],
			);
			$balence_options['setting_balance_server_' . $i . '[protocol]'] = array(
				'type' => 'hidden',
				'value' => $server['protocol'],
			);
			$balence_options['setting_balance_server_' . $i . '[username]'] = array(
				'type' => 'hidden',
				'value' => $server['username'],
			);
			$balence_options['setting_balance_server_' . $i . '[password]'] = array(
				'type' => 'hidden',
				'value' => $server['password'],
			);
			if(isset($server['nickname']))
			{
				$balence_options['setting_balance_server_' . $i . '[nickname]'] = array(
					'type' => 'hidden',
					'value' => $server['nickname'],
				);
			}
			$balence_options['edit_server[' . $i . ']'] = array(
				'type' => 'submit',
				'value' => 'Edit',
				'help' => isset($server['nickname'])?$server['nickname']:('Balance Server ' . $i),
			);
			$balence_options['remove_server[' . $i . ']'] = array(
				'type' => 'submit',
				'value' => 'Remove',
			);
			$balence_options[] = array('value' => '<br />');
		}
		
		$options['manage_servers']['options'] = $balence_options;
	}
	
	$options['balance_servers'] = array(
		'name' => 'Add Server',
		'status' => '',
		'description' => array(
			'list' => array(
				'Provide the web-accessible http or https address to a remote media server setup.',
			),
		),
		'type' => 'set',
		'options' => array(
			'add_server[protocol]' => array(
				'type' => 'select',
				'options' => array(
					'http' => 'HTTP',
					'https' => 'Secure HTTP',
				),
				'value' => 'https',
				'help' => 'Server Address',
			),
			'add_server[address]' => array(
				'type' => 'text',
				'value' => 'www2.example.com'
			),
			array(
				'value' => '<br />'
			),
			'add_server[username]' => array(
				'type' => 'text',
				'help' => 'Username',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_server[password]' => array(
				'type' => 'text',
				'help' => 'Password',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_server[nickname]' => array(
				'type' => 'text',
				'help' => 'Nickname',
				'value' => '',
			),
			array(
				'value' => '<br />'
			),
			'add_server[save]' => array(
				'type' => 'submit',
				'value' => 'Add Server',
			),
		),
	);
	
	if(count($settings['balance_rules']) > 0)
	{
		// display all rules
	}
	elseif(count($settings['balance_servers']) > 0)
	{
		$options['balance_rules_null'] = array(
			'name' => 'Balancing Rules',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					lang('balance rules empty description', 'There are no rules defined for balancing.'),
					lang('balance rules empty description', 'Rules have a waterfall effect.  That is, when a request is made, the rule is tested on each condition.  The last condition to satisfy the request is the server it is transfered to.'),
				),
			),
			'value' => 'No rules defined',
		);
	}
	else
	{
		$options['balance_rules_null'] = array(
			'name' => 'Balancing Rules',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('balance rules fail description 1', 'You must first specify a balancing server!'),
					lang('balance rules fail description 2', 'There are no rules defined for balancing.'),
				),
			),
			'value' => 'Define a server first!',
		);
	}
	
	$modules = array();
	foreach($GLOBALS['modules'] as $module => $config)
	{
		$modules[$module] = $config['name'];
	}
	
	$servers = array();
	foreach($settings['balance_servers'] as $i => $server)
	{
		$servers[$i] = isset($server['nickname'])?$server['nickname']:('Balance Server ' . $i);
	}
	
	$options['balance_rules'] = array(
		'name' => 'Add Rule',
		'status' => '',
		'description' => array(
			'list' => array(
				'Add a rule for balancing.',
				'The Module field is for specifying which module the balancing rule should act on; this is just a shorthand for using a request condition.',
				'Condition is for specifying which type of condition will activate the balancing.',
				'Value is the value for the condition, depending on the condition this value may be different types.',
				'Server is the server to forward the request to when the condition is met.',
			),
		),
		'type' => 'set',
		'options' => array(
			'balance_rules[module]' => array(
				'type' => 'select',
				'options' => $modules,
				'value' => 'encode',
				'help' => 'Module',
			),
			array(
				'value' => '<br />'
			),
			'balance_rules[condition]' => array(
				'type' => 'select',
				'options' => array(
					'percent' => 'Percentage of Users',
					'request' => 'Request Variable',
					'server' => 'Server Variable',
				),
				'value' => 'percent',
				'help' => 'Condition',
			),
			array(
				'value' => '<br />'
			),
			'balance_rules[value]' => array(
				'type' => 'text',
				'value' => '50',
				'help' => 'Value',
			),
			array(
				'value' => '<br />'
			),
			'balance_rules[server]' => array(
				'type' => 'select',
				'options' => $servers,
				'value' => 0,
				'help' => 'Server',
			),
			array(
				'value' => '<br />'
			),
			array(
				'type' => 'submit',
				'value' => 'Add Rule',
			),
		),
	);
	
	return $options;
}


