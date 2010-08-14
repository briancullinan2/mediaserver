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
	
	setup_error();
}

function setup_error()
{
	$verbose = setting('verbose');

	// do some extra error stuff since we made it to this point
	if($verbose === 2)
	{
		set_error_handler('php_to_PEAR_Error', E_ALL | E_STRICT);
		error_reporting(E_ALL);
	}
	elseif($verbose === true)
	{
		set_error_handler('php_to_PEAR_Error', E_ALL);
		error_reporting(E_ERROR);
	}
	else
		error_reporting(E_ERROR);
	
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
function php_to_PEAR_Error($error_code, $error_str, $error_file, $error_line)
{
	$error_code = E_DEBUG;

	if(substr($error_str, 0, 12) == 'mysql error:')
		raise_error('DB ERROR:' . substr($error_str, 12), $error_code);
	else
		raise_error('PHP ERROR: ' . $error_str, $error_code);

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


/**
 * The callback function for the PEAR error handler to use
 * @param error the pear error object to add to the error stack
 */
function error_callback($error)
{
	if($error->code & E_USER)
		$GLOBALS['user_errors'][] = $error->message;
	if($error->code & E_WARN)
		$GLOBALS['warn_errors'][] = $error->message;
	if($error->code & E_NOTE)
		$GLOBALS['note_errors'][] = $error->message;
	if($error->code & E_DEBUG || 
		(
			(
				$error->code & E_VERBOSE || 
				$error->code & E_USER
			) && setting('verbose') === 2)
		)
	{
		if(count($GLOBALS['debug_errors']) > 200)
			return;

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
		
		// only show verbose errors if it is really verbose!
		if($error->code & E_DEBUG || setting('verbose'))
			$GLOBALS['debug_errors'][] = $error;
	}
}

