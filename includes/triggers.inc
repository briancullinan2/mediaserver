<?php

function preload_triggers()
{
	$GLOBALS['triggers'] = array(
		'session' => array(),
		'filter' => array(),
		'output' => array(),
		'validate' => array(),
	);
}

function trigger_key($trigger, $callback, $input, $key)
{
	$args = func_get_args();
	
	if(isset($GLOBALS['triggers'][$trigger][$key]) && count($GLOBALS['triggers'][$trigger][$key]) > 0)
	{
		foreach($GLOBALS['triggers'][$trigger][$key] as $module => $function)
		{
			unset($result);
			
			if(is_callable($function))
				$result = call_user_func_array($function, array(&$input, $key));
			else
				raise_error('Trigger \'' . $trigger . '\' functionality specified in \'' . $module . '\' but ' . $function . ' in not callable!', E_DEBUG);
			
			if(is_callable($callback))
				call_user_func_array($callback, array($module, $result, $args));
			elseif(isset($result))
			{
				$return = $result;
				// also set it here so it can be passed to next validator
				$input[$key] = $result;
			}
		}
		
		if(isset($return))
			return $return;
	}
	
	return;
}

function trigger($trigger, $callback = NULL, $input = array())
{
	if(isset($GLOBALS['triggers'][$trigger][NULL]))
	{
		// call triggers set to always go off
		foreach($GLOBALS['triggers'][$trigger][NULL] as $module => $function)
		{
			unset($result);
			
			// numeric indices on this level indicates always call
			if(is_callable($function))
			{
				$result = call_user_func_array($function, array($input));
				
				if(is_callable($callback))
					call_user_func_array($callback, array($module, $result, $input));
				elseif(isset($result))
					$input[$key] = $result;
			}
			else
				raise_error('Trigger \'' . $trigger . '\' function specified by \'' . $module . '\' but it is not callable.', E_DEBUG);
		}
	}
	
	// call triggers based on input
	foreach($input as $key => $value)
	{
		$result = trigger_key($trigger, $callback, $input, $key);
		if(isset($result))
			$input[$key] = $result;
	}
	
	return $input;
}

/**
 * Register a modules trigger
 * Triggers can be formated in the following ways:
 * @param trigger the trigger to register
 * @param config the config for the module
 * @param module the module name for reference
 */
function register_trigger($trigger, $config)
{
	// reorganize alter query triggers
	if(isset($config[$trigger]))
	{
		// the trigger is an array of values
		if(is_array($config[$trigger]))
		{
			foreach($config[$trigger] as $i => $var)
			{
				// the trigger should fire the default trigger function for specified key
				if(is_numeric($i))
					$GLOBALS['triggers'][$trigger][$var][$config['module']] = $trigger . '_' . $config['module'];
				// the trigger should fire a specified function for the specified key
				elseif(is_callable($var))
					$GLOBALS['triggers'][$trigger][$i][$config['module']] = $var;
			}
		}
		// the trigger should always fire default function name
		elseif(is_bool($config[$trigger]) || $config[$trigger] == '1')
		{
			$GLOBALS['triggers'][$trigger][NULL][$config['module']] = $trigger . '_' . $config['module'];
		}
		// the trigger should always fire specified function name
		elseif(is_callable($config[$trigger]))
		{
			$GLOBALS['triggers'][$trigger][NULL][$config['module']] = $config[$trigger];
		}
	}
	else
		raise_error('Trigger not set in config for \'' . $config['module'] . '\'.', E_VERBOSE);
}
