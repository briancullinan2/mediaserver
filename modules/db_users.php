<?php

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';

// don't actually do anything in this class, this is just used for holding the database, and the get() method
class db_users extends db_file
{
	const DATABASE = 'users';
	
	const NAME = 'Users Paths from Database';
	
	const INTERNAL = true;
	
	// this expression is used to filter out usernames
	const USER_REG = '/[a-z0-9]*/i';

	static function columns()
	{
		return array('id', 'Username', 'Email', 'Settings', 'Privilage', 'PublicKey', 'LastLogin');
	}
	
	// return the structure of the database
	static function struct()
	{
		return array(
			'Username' 		=> 'TEXT',
			'Password' 		=> 'TEXT',
			'Email' 		=> 'TEXT',
			'Settings' 		=> 'TEXT',
			'Privilage'		=> 'INT',
			'PublicKey'		=> 'TEXT',
			'LastLogin'		=> 'DATETIME'
		);
	}
	
	static function handles($path)
	{
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		// handle directories found in the LOCAL_USERS directory
		//  automatically create a user entry in the database for those directories
		if(dirname($path) == LOCAL_USERS || dirname($path) == '')
		{
			if(preg_match(self::USER_REG, basename($path)) > 0)
			{
				return true;
			}
		}
		
		return false;
	}

	static function handle($path, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		if(self::handles($file))
		{
			$username = basename($path);
			
			// check if it is in the database
			$db_file = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'WHERE' => 'Username = "' . addslashes($username) . '"',
					'LIMIT' => 1
				)
			);
			
			if( count($db_file) == 0 )
			{
				// just set up the user with default information
			}
			elseif($force)
			{
				// not really anything to do here
			}
		}
		
		return false;
	}
	
	static function get($request, &$count, &$error)
	{
		$request['order_by'] = 'id';
		
		$GLOBALS['database']->validate($request, $props, get_class());
		
		$props['SELECT'] = self::DATABASE;
		
		$files = $GLOBALS['database']->query($props);
		
		return $files;
	}
	
	static function remove($file)
	{
	}
	
	static function cleanup()
	{
	}

}

?>