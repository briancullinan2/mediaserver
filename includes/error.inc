<?php

function preload_error()
{
	/**
	 * @name Error Levels
	 * Error codes so we know which errors to print to the user and which to print to debug
	 */
	//@{
	/** @enum E_DEBUG the DEBUG level error used for displaying errors in the debug template block */
	define('E_DEBUG',					2);
	define('E_VERBOSE',					4);
	/** @enum E_USER USER level errors are printed to the user by the templates */
	define('E_USER',					8);
	/** @enum E_WARN the WARN level error prints a different color in the error block, this is
	 * used by parts of the site that cause problems that may not be intentional */
	define('E_WARN',					16);
	/** @enum E_FATAL the FATAL errors are ones that cause the script to end at an unexpected point */
	define('E_FATAL',					32);
	/** @enum E_NOTE the NOTE error level is used for displaying positive information to users such as
	 * "account has been created" */
	define('E_NOTE',					64);
	//@}
		
	/** require pear for error handling */
	if(include_once 'PEAR.php')
	{
		//include_once 'MIME' . DIRECTORY_SEPARATOR . 'Type.php';
	}
	else
	{
		class PEAR_Error
		{
			var $code = 0;
			var $message = '';
			var $backtrace = array();
		}
		
		// bootstrap pear error handling but don't load any other pear dependencies
		class PEAR
		{
			static function raiseError($message, $code)
			{
				$error = new PEAR_Error();
				$error->code = $code;
				$error->message = $message;
				$error->backtrace = debug_backtrace();
				call_user_func_array(PEAR_ERROR_CALLBACK, array($error));
			}
			
			static function setErrorHandling($type, $error_func)
			{
				if(is_callable($error_func))
				{
					define('PEAR_ERROR_CALLBACK', $error_func);
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
	
	setup_error();
}

/**
 * Implementation of validate
 * @return false by default
 */
function validate_errors_only($request)
{
	return generic_validate_boolean_false($request, 'errors_only');
}


function setup_error()
{
	$verbose = setting('verbose');

	// do some extra error stuff since we made it to this point
	if($verbose === 2)
	{
		set_error_handler('phperror_wrapper', E_ALL | E_STRICT);
		error_reporting(E_ALL);
	}
	elseif($verbose === true)
	{
		set_error_handler('phperror_wrapper', E_ALL);
		error_reporting(E_ERROR);
	}
	else
		error_reporting(E_ERROR);
	
	set_exception_handler('exception_wrapper');
	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'error_callback');
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
		raise_error('DB ERROR:' . substr($error_str, 12), E_DEBUG);
	else
		raise_error('PHP ERROR: ' . $error_str . ' in ' . $error_file . ' on line ' . $error_line, E_DEBUG);

	return (setting('verbose') != 2);
}

function exception_wrapper($exception)
{
	raise_error('PHP EXCEPTION: ' . $exception->getMessage(), E_DEBUG);

	return (setting('verbose') != 2);
}

function raise_error($str, $code)
{
	$error = new StdClass;
	$error->code = $code;
	$error->message = $str;
	$error->backtrace = debug_backtrace();
	
	if(error_reporting() != 0 || setting('verbose') === 2)
		error_callback($error);
}


function add_error_callback($callback)
{
	if(is_callable($callback) && !in_array($callback, $GLOBALS['error_callback']))
	{
		$GLOBALS['error_callback'][] = $callback;
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
	
	// debug information
	if($error->code & E_DEBUG || 
		(
			(
				$error->code & E_VERBOSE || 
				$error->code & E_USER
			) && setting('verbose') === 2)
		)
	{
		if(count($GLOBALS['debug_errors']) < 200)
		{
			if(isset($GLOBALS['tm_start']))
				$error->time = array_sum(explode(' ', microtime())) - $GLOBALS['tm_start'];

			// add special error handling based on the origin of the error
			foreach($error->backtrace as $i => $stack)
			{
				if($stack['function'] == 'raise_error')
					break;
			}
			$i++;
			if(isset($error->backtrace[$i]['file']))
			{
				if(dirname($error->backtrace[$i]['file']) == 'modules' && basename($error->backtrace[$i]['file']) == 'template.php')
				{
					for($i = $i; $i < count($error->backtrace); $i++)
					{
						if(dirname($error->backtrace[$i]['file']) != 'modules' || basename($error->backtrace[$i]['file']) != 'template.php')
							break;
					}
				}
			
				$error->message .= ' in ' . $error->backtrace[$i]['file'] . ' on line ' . $error->backtrace[$i]['line'];
			}
			
			if($error->code & E_VERBOSE)
				$error->message = 'VERBOSE: ' . $error->message;
			
			// only show verbose errors if it is really verbose!
			if($error->code & E_DEBUG || setting('verbose'))
			{
				if(isset($GLOBALS['debug_errors'][md5($error->message)]))
					$error->count = isset($GLOBALS['debug_errors'][md5($error->message)]->count)?$GLOBALS['debug_errors'][md5($error->message)]->count++:2;
				//$GLOBALS['debug_errors'][] = $error;
				$GLOBALS['debug_errors'][md5($error->message)] = $error;
			}
		}
	}
		
	// calling all callbacks
	foreach($GLOBALS['error_callback'] as $i => $callback)
	{
		call_user_func_array($callback, array($error));
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default, set to true to record all notices
 */
function setting_verbose($settings)
{
	$verbose = generic_validate_boolean_false($settings, 'verbose');

	if(isset($settings['verbose']) && ($settings['verbose'] === "2" || $settings['verbose'] === 2))
		return 2;
	else
		return $verbose;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return false by default
 */
function setting_debug_mode($settings)
{
	return generic_validate_boolean_false($settings, 'debug_mode') || !setting_installed();
}


function configure_error($settings)
{
	$options = array();

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
	
	$options['setting_verbose'] = array(
		'name' => 'Verbosity',
		'status' => 'fail',
		'description' => array(
			'list' => array(
				'Set the level of errors and warnings to display in the debug bar.',
			),
		),
		'type' => 'select',
		'value' => setting('verbose'),
		'options' => array(
			false => 'Do not show any debug information.',
			true => 'Show important debug information.',
			2 => 'Show all notices and debug information.',
		),
	);

	return array('error' => array(
		'name' => 'Error Reporting',
		'type' => 'fieldset',
		'options' => $options
	));
}
