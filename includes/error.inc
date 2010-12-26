<?php

function register_error()
{
	return array(
		'name' => 'Errors and Logging',
		'description' => 'Settings for handling fatal errors and logging errors.',
		'privilage' => 1,
		'settings' => 'error',
		'output' => 'output_errors',
		'internal' => true,
		'package' => 'core',
		'database' => array(
			'Errors' => 'LONGBLOB',
			'Time' => 'DATETIME',
			'Filepath' => 'TEXT',
		),
	);
}

// space hold function for table
function handles_error()
{
	return false;
}


function bootstrap_error($module)
{
	if(!defined('E_' . strtoupper($module)))
	{
		$name = 'E_' . strtoupper($module);
		$bit = pow(2, count($GLOBALS['error_bits']));
		define($name, $bit);
		$GLOBALS['error_bits'][$name] = $bit;
		$GLOBALS['error_names'][$name] = get_module($module, 'name') . ' Errors';
	}
}

function preload_error()
{
	/**
	 * @name Error Levels
	 * Error codes so we know which errors to print to the user and which to print to debug
	 */
	//@{
	/** @enum E_DEBUG the DEBUG level error used for displaying errors in the debug template block */
	define('E_DEBUG',					32768);
	define('E_VERBOSE',					65536);
	/** @enum E_USER USER level errors are printed to the user by the templates */
	define('E_USER',					131072);
	/** @enum E_WARN the WARN level error prints a different color in the error block, this is
	 * used by parts of the site that cause problems that may not be intentional */
	define('E_WARN',					262144);
	/** @enum E_FATAL the FATAL errors are ones that cause the script to end at an unexpected point */
	define('E_FATAL',					524288);
	/** @enum E_NOTE the NOTE error level is used for displaying positive information to users such as
	 * "account has been created" */
	define('E_NOTE',					1048576);
	
	define('E_ALL_MEDIA',				E_ALL|E_DEBUG|E_FATAL);
	//@}

	// set up some globals to keep track of readable error names
	$GLOBALS['error_bits'] = array(
		// Media server errors
		'E_DEBUG' => E_DEBUG,
		'E_VERBOSE' => E_VERBOSE,
		'E_USER' => E_USER,
		'E_WARN' => E_WARN,
		'E_FATAL' => E_FATAL,
		'E_NOTE' => E_NOTE,
		'E_ALL_MEDIA' => E_ALL_MEDIA,
		
		// PHP predefined errors
        'E_ERROR' => E_ERROR,
        'E_WARNING' => E_WARNING,
        'E_PARSE' => E_PARSE,
        'E_NOTICE' => E_NOTICE,
        'E_CORE_ERROR' => E_CORE_ERROR,
        'E_CORE_WARNING' => E_CORE_WARNING,
        'E_COMPILE_ERROR' => E_COMPILE_ERROR,
        'E_COMPILE_WARNING' => E_COMPILE_WARNING,
        'E_USER_ERROR' => E_USER_ERROR,
        'E_USER_WARNING' => E_USER_WARNING,
        'E_USER_NOTICE' => E_USER_NOTICE,
        'E_STRICT' => E_STRICT,
        'E_RECOVERABLE_ERROR' => E_RECOVERABLE_ERROR,
		'E_DEPRECATED' => E_DEPRECATED,
		'E_USER_DEPRECATED' => E_USER_DEPRECATED,
		
		'E_ALL' => E_ALL,
    );

	$GLOBALS['error_names'] = array(
		// Media server errors
		'E_DEBUG' => 'Media Debug Error',
		'E_VERBOSE' => 'Media Verbose Error',
		'E_USER' => 'Media User Error',
		'E_WARN' => 'Media User Warning',
		'E_FATAL' => 'Media Fatal Error',
		'E_NOTE' => 'Media User Notice',
		'E_ALL_MEDIA' => 'All Errors',
		
		// PHP predefined errors
        'E_ERROR' => 'Fatal Error',
        'E_WARNING' => 'Warning',
        'E_PARSE' => 'Parse Error',
        'E_NOTICE' => 'Notice',
        'E_CORE_ERROR' => 'Fatal Core Error',
        'E_CORE_WARNING' => 'Core Warning',
        'E_COMPILE_ERROR' => 'Compilation Error',
        'E_COMPILE_WARNING' => 'Compilation Warning',
        'E_USER_ERROR' => 'Triggered Error',
        'E_USER_WARNING' => 'Triggered Warning',
        'E_USER_NOTICE' => 'Triggered Notice',
        'E_STRICT' => 'Deprecation Notice',
        'E_RECOVERABLE_ERROR' => 'Catchable Fatal Error',
		'E_DEPRECATED' => 'Deprecated Error',
		'E_USER_DEPRECATED' => 'Triggered Deprecated Error',
		
		'E_ALL' => 'All PHP Errors',
    );

	/** require pear for error handling */
	if(@include_once 'PEAR.php')
	{
		//include_once 'MIME' . DIRECTORY_SEPARATOR . 'Type.php';
	}
	else
	{
		define('PEAR_ERROR_CALLBACK',  16);
		
		class PEAR_Error
		{
			var $code = 0;
			var $message = '';
			var $backtrace = array();
		}
		
		// bootstrap pear error handling but don't load any other pear dependencies
		class PEAR
		{
			var $callback = NULL;
			static function raiseError($message, $code)
			{
				$error = new PEAR_Error();
				$error->code = $code;
				$error->message = $message;
				$error->backtrace = debug_backtrace();
				call_user_func_array($GLOBALS['_callback'], array($error));
			}
			
			static function setErrorHandling($type, $error_func)
			{
				if(is_callable($error_func))
				{
					$GLOBALS['_callback'] = $error_func;
				}
			}
		}
	}
	
	/** Set the error handler to use our custom function for storing errors */
	error_reporting(E_ALL);
	
	/** stores a list of all user errors */
	$GLOBALS['user_errors'] = array();
	/** stores a list of all warnings */
	$GLOBALS['warn_errors'] = array();
	/** stores a list of all notices and friendly messages */
	$GLOBALS['note_errors'] = array();
	/** stores a list of all debug information */
	$GLOBALS['debug_errors'] = array();
	// stores a list of callback functions for error processing
	$GLOBALS['error_callback'] = array();
	
	// error connections
	$GLOBALS['error_handles'] = array();
	
	setup_error();
}

/**
 * Implementation of validate
 * @return false by default
 */
function validate_errors_only($request)
{
	if(!isset($request['errors_only']))
		return false;
	else
		return $request['errors_only'] == true;
}


function setup_error()
{
	// do some extra error stuff since we made it to this point
	set_error_handler('phperror_wrapper', E_ALL | E_STRICT);
	error_reporting(E_ALL | E_STRICT);
	set_exception_handler('exception_wrapper');
	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
	
	// open all the error logs for writing
	$error_logs = setting('error_logs');

	foreach($error_logs as $i => $config)
	{
		if(!isset($GLOBALS['error_handles'][$i]) || !is_resource($GLOBALS['error_handles'][$i]))
		{
			if($config['connection'] == 'local' && is_writeable($config['location']))
				$GLOBALS['error_handles'][$i] = fopen($config['location'], 'w');
			elseif($config['connection'] == 'ssh2' && dependency('ssh2_installed'))
			{
				$params = parseDSN($config['location']);
				$GLOBALS['error_handles'][$i] = ssh2_connect($params['hostspec'], isset($params['port'])?$params['port']:22);
				ssh2_auth_password($GLOBALS['error_handles'][$i], $params['username'], $params['password']);
				ssh2_exec($GLOBALS['error_handles'][$i], 'echo "" > ' . $params['database']);
			}
		}
	}
}

function setting_error()
{
	$settings = array();
	
	// add wrapper functions for validating a server entry
	for($i = 0; $i < 10; $i++)
	{
		$GLOBALS['setting_error_log_' . $i] = create_function('$settings', 'return setting_error_log($settings, \'' . $i . '\');');
		$settings[] = 'error_log_' . $i;
	}

	return $settings;
}

/**
 * Converts PHP errors into PEAR errors
 * @param error_code the PHP code for the error
 * @param error_str the error text
 * @param error_file the file the error occured in
 * @param error_line the line the error was triggered from
 * @return true so the backend error handle knows the error has been processed
 */
function phperror_wrapper($error_code, $error_str, $error_file, $error_line)
{
	if(substr($error_str, 0, 12) == 'mysql error:')
		raise_error('DB ERROR:' . substr($error_str, 12), $error_code|(defined('E_DATABASE')?E_DATABASE:0));
	else
		raise_error($error_str, $error_code);

	return !(setting('display_errors') & E_VERBOSE);
}

function exception_wrapper($exception)
{
	raise_error('PHP EXCEPTION: ' . $exception->getMessage(), $exception->getCode());

	return !(setting('display_errors') & E_VERBOSE);
}

function raise_error($str, $code)
{
	$error = new StdClass;
	$error->code = $code;
	$error->message = $str;
	$error->backtrace = debug_backtrace();
	
	if(error_reporting() != 0 || setting('display_errors') & E_VERBOSE)
		error_callback($error);
}


function add_error_callback($callback)
{
	if(is_callable($callback) && !in_array($callback, $GLOBALS['error_callback']))
	{
		$GLOBALS['error_callback'][] = $callback;
		// call the callback with all the buffered errors since it was just added
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			call_user_func_array($callback, array($error));
		}
		return true;
	}
	
	return false;
}



/**
 * The callback function for the PEAR error handler to use
 * @param error the pear error object to add to the error stack
 */
function error_callback($error, $callback = NULL)
{
	if($error->code & E_USER)
		$GLOBALS['user_errors'][] = $error->message;
	if($error->code & E_WARN)
		$GLOBALS['warn_errors'][] = $error->message;
	if($error->code & E_NOTE)
		$GLOBALS['note_errors'][] = $error->message;
	
	$module_paths = get_modules('path');

	// add time to error object
	if(isset($GLOBALS['tm_start']))
		$error->time = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];
		
	// determine the module that generate the error from the stack trace
	foreach($error->backtrace as $i => $stack)
	{
		if(($stack['function'] == 'raise_error' || $stack['function'] == 'phperror_wrapper') && isset($error->backtrace[$i+1]['file']))
		{
			$error->message .= ' in ' . $error->backtrace[$i+1]['file'] . ' on line ' . $error->backtrace[$i+1]['line'];
		}
		
		if(isset($stack['file']) && ($module = array_search($stack['file'], $module_paths)) && $module != 'error')
		{
			//$error->message = '[' . strtoupper($module) . ']' . $error->message;
			if(defined('E_' . strtoupper($module)))
				$error->code = $error->code | constant('E_' . strtoupper($module));
			break;
		}
	}
		
	// calling all callbacks
	foreach($GLOBALS['error_callback'] as $i => $callback)
		call_user_func_array($callback, array($error));
	
	// debug information
	$error->count = 0;
	if(($error->code & setting('display_errors')) && setting('debug_mode'))
	{
		// only show verbose errors if it is really verbose!
		if(isset($GLOBALS['debug_errors'][md5($error->message)]))
			$GLOBALS['debug_errors'][md5($error->message)]->count++;
		else
			//$GLOBALS['debug_errors'][] = $error;
			$GLOBALS['debug_errors'][md5($error->message)] = $error;
	}
	
	// write errors to all logs
	$error_logs = setting('error_logs');

	foreach($error_logs as $i => $config)
	{
		if(is_string($config['types']))
			$config['types'] = error_code_eval($config['types']);

		if($config['types'] == -1)
			continue;

		if(($error->code & $config['types']) && isset($GLOBALS['error_handles'][$i]) && is_resource($GLOBALS['error_handles'][$i]))
		{
			if($config['connection'] == 'local')
				fwrite($GLOBALS['error_handles'][$i], $error->message . "\n");
			elseif($config['connection'] == 'ssh2')
			{
				$params = parseDSN($config['location']);
				ssh2_exec($GLOBALS['error_handles'][$i], 'echo "' . addslashes($error->message) . '" >> ' . $params['database']);
			}
		}
	}
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_add_log($request)
{
	if(!isset($request['add_log']['save']))
		return;
		
	return array(
		'location' => isset($request['add_log']['location'])?$request['add_log']['location']:'',
		'types' => isset($request['add_log']['types'])?$request['add_log']['types']:'',
		'connection' => isset($request['add_log']['connection'])?$request['add_log']['connection']:'',
	);
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default, set to true to record all notices
 */
function setting_display_errors($settings)
{
	if(isset($settings['display_errors']) && is_numeric($settings['display_errors']))
		return $settings['display_errors'];
	elseif(isset($settings['display_errors']) && ($code = error_code_eval($settings['display_errors'])))
		return $code;
	else
		return E_ALL_MEDIA | E_STRICT;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default
 */
function setting_debug_mode($settings)
{
	if(!isset($settings['debug_mode']) || !$settings['debug_mode'])
		return !setting_installed();
	else
		return $settings['debug_mode'] == true;
}

function setting_error_log($settings, $index)
{
	// don't continue with this if stuff is missing
	if(!isset($settings['error_log_' . $index]) || !isset($settings['error_log_' . $index]['location']) || 
		!isset($settings['error_log_' . $index]['types']) || !isset($settings['error_log_' . $index]['connection'])
	)
		return;
		
	// copy values
	$log = array(
		'location' => $settings['error_log_' . $index]['location'],
		'types' => $settings['error_log_' . $index]['types'],
		'connection' => $settings['error_log_' . $index]['connection'],
	);
	
	// validate connection type
	if($log['connection'] != 'local' && $log['connection'] != 'ssh2')
		return;
	
	// all validation feedback is provided in the status for error logs
	if(empty($log['location']))
		return;
	if($log['connection'] == 'ssh2')
	{
		if(strpos($log['location'], '://') === false)
			$log['location'] = 'ssh://' . $log['location'];
			
		$parsed_dsn = parseDSN($log['location']);
		$log_config['location'] = 'ssh://' . 
			(isset($parsed_dsn['username'])?$parsed_dsn['username']:$_ENV['USER']) . 
			(isset($parsed_dsn['password'])?':******@':'@') . 
			$parsed_dsn['hostspec'] . 
			(isset($parsed_dsn['port'])?$parsed_dsn['port']:'') . 
			$parsed_dsn['database'];
	}
	
	return $log;
}


/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_error_logs($settings)
{
	if(!isset($settings['error_logs']))
		$settings['error_logs'] = array();
	
	// make sure all servers with numeric indexes are on the list
	for($i = 0; $i < 10; $i++)
	{
		$log = setting_error_log($settings, $i);
		if(isset($log))
			$settings['error_logs'][$i] = $log;
	}
	
	return array_values($settings['error_logs']);
}


function error_code_eval($code)
{
	if(preg_match('/^[a-z0-9_&\|\^~<> ]*$/i', $code))
	{
		$code = str_replace(array_keys($GLOBALS['error_bits']), array_values($GLOBALS['error_bits']), $code);
		if(preg_match('/^[0-9&\|\^~<> ]*$/i', $code) == 0)
		{
			return -1;
		}
		else
		{
			return eval('return @(' . $code . ');');
		}
	}
}

function eval_error_code($code)
{
	$result = array();

	// convert the number back in to a string
	foreach($GLOBALS['error_bits'] as $error => $bits)
	{
		if(($code & $bits) == $bits)
			$result[] = $error;
	}

	if($code != E_ALL_MEDIA && $code != E_ALL)
	{
		$all_errors = explode(' | ', eval_error_code(E_ALL));
		$all_media_errors = explode(' | ', eval_error_code(E_ALL_MEDIA));
		
		if(($code & E_ALL) == E_ALL)
			$result = array_merge(array('E_ALL'), array_diff($result, array_intersect($result, $all_errors)));
		if(($code & E_ALL_MEDIA) == E_ALL_MEDIA)
			$result = array_merge(array('E_ALL_MEDIA'), array_diff($result, array_intersect($result, $all_media_errors)));
	}

	return implode(' | ', $result);
}


function status_error($settings)
{
	$settings['error_logs'] = setting('error_logs');
	
	// loop through each error log and check a few things
	$status = array();
	
	// load log list from session
	if($session_balancer = session('error'))
		$settings['error_logs'] = $session_balancer['error_logs'];
	
	if(count($settings['error_logs']) == 0)
	{
		$status['no_logs'] = array(
			'name' => 'No Logs Defined',
			'status' => 'warn',
			'description' => array('Use the form below to define logs for the system to keep track of usage and errors.'),
			'value' => 'Define a log first!'
		);
	}
	else
	{
		foreach($settings['error_logs'] as $i => $log_config)
		{
			// check the type of error log
			if($log_config['connection'] == 'ssh2' && !dependency('ssh2_installed'))
			{
				$status['error_log_' . $i] = array(
					'name' => 'Error Log ' . $i,
					'status' => 'fail',
					'value' => 'SSH2 is not properly installed',
					'description' => array('Error log located at ' . $log_config['location'] . ' required SSH2 to be installed.'),
				);
			}
			
			// check that the log file is accessible and writable
			if($log_config['connection'] == 'local' && !is_writable($log_config['location']))
			{
				$status['error_log_' . $i] = array(
					'name' => 'Error Log ' . $i,
					'status' => 'fail',
					'value' => 'Log not writable',
					'description' => array('Error log located at ' . $log_config['location'] . ' cannot be written to, check permissions or change the log location.'),
				);
			}
			
			// check that the types compiles to valid bitwise
			$result = error_code_eval($log_config['types']);
			if(!isset($result))
			{
				$status['error_log_' . $i] = array(
					'name' => 'Error Log ' . $i,
					'status' => 'fail',
					'value' => 'Invalid characters in error log ' . $i,
					'description' => array('Error log located at ' . $log_config['location'] . ' has characters that are not alpha numeric or bitwise operators in the Types field.'),
				);
			}
			elseif($result === false)
			{
				$status['error_log_' . $i] = array(
					'name' => 'Error Log ' . $i,
					'status' => 'fail',
					'value' => 'Problem evaluating error log ' . $i,
					'description' => array('Error log located at ' . $log_config['location'] . ' had problems evaluating.'),
				);
			}
			elseif($result === -1)
			{
				$status['error_log_' . $i] = array(
					'name' => 'Error Log ' . $i,
					'status' => 'fail',
					'value' => 'Error Log ' . $i . ' has errors',
					'description' => array('Error log located at ' . $log_config['location'] . ' has invalid error types.'),
				);
			}
		}
	}
	
	if(count($status) == 0)
	{
		$status['error_logs'] = array(
			'name' => 'All Error Logs',
			'status' => '',
			'value' => 'No errors found',
			'description' => array('No errors found in the configuration for logging.'),
		);
	}
	
	return $status;
}

function output_errors($request)
{
	// save errors and stuff in the database
	ksort($_REQUEST);
	$request_info = array(
		'Errors' => gzdeflate(serialize(array(
			'user' => $GLOBALS['user_errors'],
			'warn' => $GLOBALS['warn_errors'],
			'note' => $GLOBALS['note_errors'],
			'debug' => $GLOBALS['debug_errors'],
		))),
		'Time' => date('Y-m-d h:i:s', $GLOBALS['tm_start']),
		'Filepath' => serialize($_REQUEST)
	);
	
	if(setting('database_enable'))
	{
		db_query('INSERT INTO error ' . sql_insert($request_info), array_values($request_info));
		
		// get all recent requests
		$requests = db_assoc('SELECT * FROM (SELECT * FROM error WHERE Filepath=? ORDER BY Time DESC LIMIT 0,4) a ORDER BY Time ASC', array(serialize($_REQUEST)));
		register_output_vars('requests', $requests);
	}
}

function configure_error($settings)
{
	$settings['error_logs'] = setting('error_logs');
	
	$error_types = implode(' ', array_keys($GLOBALS['error_bits']));
	
	// load log list from session
	if($session_error = session('error'))
		$settings['error_logs'] = $session_error['error_logs'];
	
	$options = array();

	$options['error_action'] = array(
		'name' => lang('error action title', 'Error Handling'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('error action description 1', 'Specify the action to take when a fatal error occurs.'),
				lang('error action description 2', 'It is important to note that an action will only be taken if it is possible.'),
				lang('error action description 3', 'This is not the only method of error reporting that should be used.'),
			),
		),
		'type' => 'select',
		'value' => setting('error_action'),
		'options' => array(
			lang('error action option 1', 'No Action'),
			lang('error action option 2', 'Display Error Page'),
			lang('error action option 3', 'Error Page and E-mail Admin'),
			lang('error action option 4', 'Just E-mail Administrator'),
		)
	);
	
	$options['debug_mode'] = array(
		'name' => lang('debug mode title', 'Debug Mode'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('debug mode description 1', 'Debug mode is used by many templates to display debugging options on the page.'),
				lang('debug mode description 2', 'This is usefull for viewing information about file system and database problems and to test if the system is running properly.'),
			),
		),
		'type' => 'boolean',
		'value' => setting('debug_mode'),
		'options' => array(
			lang('debug mode option 1', 'Turn Debug Mode On'),
			lang('debug mode option 2', 'Do Not Use Debug Mode'),
		)
	);
	
	$options['setting_display_errors'] = array(
		'name' => 'Display Errors',
		'status' => '',
		'description' => array(
			'list' => array(
				'Set the types of errors and warnings to display in the debug bar.',
			),
		),
		'type' => 'text',
		'value' => eval_error_code(setting('display_errors')),
	);
	
	if(count($settings['error_logs']) > 0)
	{
		$error_logs = array();
		
		// display all rules
		$has_errors = false;
		foreach($settings['error_logs'] as $i => $log_config)
		{
			if($log_config['connection'] == 'ssh2')
			{
				$parsed_dsn = parseDSN($log_config['location']);
				$log_config['location'] = 'ssh://' . 
					$parsed_dsn['username'] . 
					(isset($parsed_dsn['password'])?':******@':'@') . 
					$parsed_dsn['hostspec'] . 
					(isset($parsed_dsn['port'])?$parsed_dsn['port']:'') . 
					$parsed_dsn['database'];
			}
			
			$error_logs['setting_error_log_' . $i . '[connection]'] = array(
				'type' => 'hidden',
				'value' => $log_config['connection'],
			);
			$error_logs['setting_error_log_' . $i . '[location]'] = array(
				'type' => 'hidden',
				'value' => $log_config['location'],
			);
			$error_logs['setting_error_log_' . $i . '[types]'] = array(
				'type' => 'hidden',
				'value' => $log_config['types'],
			);
			$error_logs['edit_error_log[' . $i . ']'] = array(
				'type' => 'submit',
				'value' => 'Edit',
				'name' => 'Location: ' . $log_config['location'] . ' Types: ' . $log_config['types'],
			);
			$error_logs['remove_error_log[' . $i . ']'] = array(
				'type' => 'submit',
				'value' => 'Remove',
			);
			
			if(!error_code_eval($log_config['types']) || ($log_config['connection'] == 'ssh2' && !dependency('ssh2_installed')))
				$has_errors = true;
		}

		
		if($has_errors)
		{
			$options['error_logs'] = array(
				'name' => 'Manage Error Logs',
				'status' => 'warn',
				'description' => array(
					'list' => array(
						'Manage the error logging rules.',
						'There are errors in the rules, see the Module Status.',
					),
				),
				'type' => 'set',
				'options' => $error_logs,
			);
		}
		else
		{
			$options['error_logs'] = array(
				'name' => 'Manage Error Logs',
				'status' => '',
				'description' => array(
					'list' => array(
						'Manage the error logging rules.',
					),
				),
				'type' => 'set',
				'options' => $error_logs,
			);
		}
	}
	else
	{
		$options['error_logs'] = array(
			'name' => 'Manage Error Logs',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'Use the form below to define some error logs.',
				),
			),
			'value' => 'No logs defined',
		);
	}
	
	$connection_types = array('local' => 'Local Filesystem');
	if(dependency('ssh2_installed'))
		$connection_types['ssh2'] = 'SSH to Log Server';
		
	$options['add_log'] = array(
		'name' => 'Add a New Log',
		'status' => '',
		'description' => array(
			'list' => array(
				'Select the method you would like to store your log, either on the local filesystem or through and SSH connection (required libssh2 to be installed and configured).',
				'Specify a location for the log to be stored either locally or remotely.',
				array('Valid types of errors include all default PHP errors, using the PHP error syntax:', 'list' => array('Example: E_ALL & ~E_NOTICE')),
				array('Valid error types also include the media server custom error specifications:', 'list' => array('Example: E_ALL & ~E_VERBOSE')),
				array('Errors can also be include/excluded by module/file name:', 'list' => array('Example: E_SELECT & ~E_TEMPLATE')),
			),
		),
		'type' => 'set',
		'options' => array(
			'add_log[connection]' => array(
				'type' => 'select',
				'options' => $connection_types,
				'name' => 'Connection Type',
				'description' => 'Type of connection to use to connect to error log',
				'value' => 'local',
			),
			'add_log[location]' => array(
				'type' => 'text',
				'name' => 'Location',
				'value' => '',
				'description' => 'Location to save error files',
				'value' => '/tmp/mediaserver.log',
			),
			'add_log[types]' => array(
				'type' => 'text',
				'name' => 'Error Types',
				'description' => array('Enter the types of errors you would like to store in the log:' . "\n", $error_types),
				'value' => 'E_SELECT & ~E_TEMPLATE',
			),
		),
	);

	return array('error' => array(
		'name' => 'Error Reporting',
		'type' => 'fieldset',
		'options' => $options
	));
}
