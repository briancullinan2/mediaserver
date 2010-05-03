<?php

//$no_setup = true;
//ini_set('include_path', ini_get('include_path') . ':' . dirname(__FILE__));
if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php'))
{
	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb-errorpear.inc.php';
	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php';
}
else
{
	// something has gone terribly wrong, disable database and notify administrator
	$GLOBALS['settings']['use_database'] = false;
	define('NOT_INSTALLED', true);
	PEAR::raiseError('Use database is turned on but adoDB is missing!', E_DEBUG|E_USER|E_FATAL);
}

/**
 * control lower level handling of each database
// things to consider:
// audio-database (for storing artist, album, track fields)
// file-database (used primarily by the virtualfs to storing file information)
// picture-database (for storing picture information)
// video-database (for storing all video information)
// watch-database (a list of directories that should be watched for media files)

// everything should fit into the main 3 mediums (music,pictures,videos) and everything else is just a file
// scalability (add a calendar handler? rss-handler?)

 */

/**
 * Set up the list of aliases from the database
 * @ingroup setup
 */
function setup_aliases()
{
	// get the aliases to use to replace parts of the filepath
	$GLOBALS['paths_regexp'] = array();
	$GLOBALS['alias_regexp'] = array();
	$GLOBALS['paths'] = array();
	$GLOBALS['alias'] = array();
	if($GLOBALS['settings']['use_database'] && $GLOBALS['settings']['use_alias'] == true)
	{
		$aliases = $GLOBALS['database']->query(array('SELECT' => 'alias'), false);
		
		if($aliases !== false)
		{
			foreach($aliases as $key => $alias_props)
			{
				$GLOBALS['paths_regexp'][] = $alias_props['Paths_regexp'];
				$GLOBALS['alias_regexp'][] = $alias_props['Alias_regexp'];
				$GLOBALS['paths'][] = $alias_props['Filepath'];
				$GLOBALS['alias'][] = $alias_props['Alias'];
			}
		}
	}
	
}

/**
 * Scan handlers directory and load all of the handlers that handle files
 * @ingroup setup
 */
function setup_handlers()
{
	
	// include the handlers
	$tmp_handlers = array();
	if ($dh = @opendir($GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR))
	{
		while (($file = readdir($dh)) !== false)
		{
			// filter out only the handlers for our $GLOBALS['settings']['use_database'] setting
			if ($file[0] != '.' && !is_dir($GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR . $file))
			{
				$class_name = substr($file, 0, strrpos($file, '.'));
				if(!defined(strtoupper($class_name) . '_ENABLED') || constant(strtoupper($class_name) . '_ENABLED') != false)
				{
					// include all the handlers
					include_once $GLOBALS['settings']['local_root'] . 'handlers' . DIRECTORY_SEPARATOR . $file;
					
					// only use the handler if it is properly defined
					if(class_exists($class_name))
					{
						if(substr($file, 0, 3) == ($GLOBALS['settings']['use_database']?'db_':'fs_'))
							$tmp_handlers[] = $class_name;
					}
				}
			}
		}
		closedir($dh);
	}
	
	$error_count = 0;
	$new_handlers = array();
	
	// reorganize handlers to reflect heirarchy
	while(count($tmp_handlers) > 0 && $error_count < 1000)
	{
		foreach($tmp_handlers as $i => $handler)
		{
			$tmp_override = get_parent_class($handler);
			if(in_array($tmp_override, $new_handlers) || $tmp_override == '')
			{
				$new_handlers[] = $handler;
				unset($tmp_handlers[$i]);
			}
		}
		$error_count++;
	}
	$GLOBALS['handlers'] = $new_handlers;
}


/**
 * Create a GLOBAL list of tables used by all the handlers
 * @ingroup setup
 */
function setup_tables()
{
	// loop through each handler and compile a list of databases
	$GLOBALS['tables'] = array();
	if($GLOBALS['settings']['use_database'])
	{
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			if(defined($handler . '::DATABASE'))
				$GLOBALS['tables'][] = constant($handler . '::DATABASE');
		}
		$GLOBALS['tables'] = array_values(array_unique($GLOBALS['tables']));
		
		// get watched and ignored directories because they are used a lot
		$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count);
		$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count);
		// always add user local to watch list
		$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', $GLOBALS['settings']['local_users']));
	}
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return true by default
 */
function setting_use_alias($settings)
{
	if(isset($settings['use_alias']))
	{
		if($settings['use_alias'] === true || $settings['use_alias'] === 'true')
			return true;
		elseif($settings['use_alias'] === false || $settings['use_alias'] === 'false')
			return false;
	}
	return true;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 'mysql' by default
 */
function setting_db_type($settings)
{
	if(isset($settings['db_type']) && in_array($settings['db_type'], database::supported_databases()))
		return $settings['db_type'];
	else
		return 'mysql';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 'localhost' by default
 */
function setting_db_server($settings)
{
	if(isset($settings['db_server']))
		return $settings['db_server'];
	else
		return 'localhost';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_db_user($settings)
{
	if(isset($settings['db_user']))
		return $settings['db_user'];
	else
		return '';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_db_pass($settings)
{
	if(isset($settings['db_pass']))
		return $settings['db_pass'];
	else
		return '';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_db_name($settings)
{
	if(isset($settings['db_name']))
		return $settings['db_name'];
	else
		return '';
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return blank by default
 */
function setting_db_connect($settings)
{
	$settings['db_type'] = setting_db_type($settings);
	$settings['db_server'] = setting_db_server($settings);
	$settings['db_user'] = setting_db_user($settings);
	$settings['db_pass'] = setting_db_pass($settings);
	$settings['db_name'] = setting_db_name($settings);
	
	if(isset($settings['db_connect']) && parseDSN($settings['db_connect']) !== NULL)
		return $settings['db_connect'];
	else
		return $settings['db_type'] . '://' . 
				$settings['db_user'] . ':' . 
				$settings['db_pass'] . '@' . 
				$settings['db_server'] . '/' . 
				$settings['db_name'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return false by default
 */
function validate_dberror($request)
{
	if(isset($request['dberror']))
		return $request['dberror'];
	else
		return false;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_database($settings)
{
	$settings['db_connect'] = setting_db_connect($settings);
	$settings['use_alias'] = setting_use_alias($settings);
	$settings['dberror'] = validate_dberror($settings);
	
	$options = array();
	
	$dsn = parseDSN($settings['db_connect']);
	
	$options['db_type'] = array(
		'name' => 'Database Type',
		'status' => '',
		'description' => array(
			'list' => array(
				'This site supports a variety of databases, select your database type.',
			),
		),
		'type' => 'select',
		'value' => $dsn['dbsyntax'],
		'options' => database::supported_databases(),
	);
	
	$options['db_server'] = array(
		'name' => 'Database Server',
		'status' => ($settings['dberror'] !== false && (strpos($settings['dberror'], 'Can\'t connect') !== false || strpos($settings['dberror'], 'Connection error') !== false))?'fail':(($settings['dberror'] !== false && strpos($settings['dberror'], 'Access denied') !== false)?'':'warn'),
		'description' => array(
			'list' => array(
				'Please specify an address of the database server to connect to.',
			),
		),
		'type' => 'text',
		'value' => $dsn['hostspec'],
	);
	
	if($settings['dberror'] == false)
	{
		$options['db_server']['description']['list'][] = 'WARNING: If this information is wrong, it could take up to 1 minute or more to detect these errors.';
	}
	elseif($settings['dberror'] !== false && strpos($settings['dberror'], 'Can\'t connect') !== false)
	{
		$options['db_server']['description']['list'][] = 'The server reported an error with the connection to the database, please check to make sure the address entered is correct and accessible.';
	}
	
	$options['db_user'] = array(
		'name' => 'Database User Name',
		'status' => ($settings['dberror'] !== false && strpos($settings['dberror'], 'Access denied') !== false)?'fail':'',
		'description' => array(
			'list' => array(
				'Please specify a username to log in to the database.',
			),
		),
		'type' => 'text',
		'value' => $dsn['username'],
	);
	
	if($settings['dberror'] !== false && strpos($settings['dberror'], 'Access denied') !== false)
	{
		$options['db_user']['description']['list'][] = 'The server reported that there were problems with your login information.';
	}
	
	$options['db_pass'] = array(
		'name' => 'Database Password',
		'status' => ($settings['dberror'] !== false && strpos($settings['dberror'], 'Access denied') !== false)?'fail':'',
		'description' => array(
			'list' => array(
				'Please specify a password to log in to the database.',
			),
		),
		'type' => 'text',
		'value' => $dsn['password'],
	);
	
	if($settings['dberror'] !== false && strpos($settings['dberror'], 'Access denied') !== false)
	{
		$options['db_pass']['description']['list'][] = 'The server reported that there were problems with your login information.';
	}
				
	$options['db_name'] = array(
		'name' => 'Database Name',
		'status' => '',
		'description' => array(
			'list' => array(
				'Please specify the name of the database to use.',
				'This database will not be created for you, it must be created ahead of time with the proper permission settings.',
			),
		),
		'type' => 'text',
		'value' => $dsn['database'],
	);
	
	if($settings['dberror'] !== false && strpos($settings['dberror'], 'already exists') !== false)
	{
		$options['drop'] = array(
			'name' => 'Tables Already Exist',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'It seems there are already tables in this database with the same name.',
					'If you drop these tables, it could cause an irreversable loss of database information.',
				),
			),
			'type' => 'submit',
			'value' => 'Drop Tables',
		);
	}
	elseif($settings['dberror'] == 'tables dropped')
	{
		$options['drop'] = array(
			'name' => 'Tables Dropped',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The tables have been successfully dropped.  You may now return to the install page.',
				),
			),
			'type' => 'label',
			'value' => 'Tables Dropped',
		);
	}
	
	$options['use_alias'] = array(
		'name' => 'Aliasing',
		'status' => '',
		'description' => array(
			'list' => array(
				'Path aliasing is used to disguise the location of files on your file system.  Aliases can be set up to convert a path such as /home/share/ to /Shared/.',
			),
		),
		'type' => 'boolean',
		'value' => $settings['use_alias'],
		'options' => array(
			'Use Aliased Paths',
			'Display Actual Path to Users',
		),
	);

	
	return $options;
}

/** pretty self explanator handler class for sql databases */
class database
{
	var $db_conn;
	var $rowset = array();
	var $num_queries = 0;
	
	static function supported_databases()
	{
		return $supported_databases = array('access','ado','ado_access','ado_mssql','db2','odbc_db2','vfp','fbsql','ibase','firebird','borland_ibase','informix','informix72','ldap','mssql','mssqlpo','mysql','mysqli','mysqlt','maxsql','oci8','oci805','oci8po','odbc','odbc_mssql','odbc_oracle','odbtp','odbtp_unicode','oracle','netezza','pdo','postgres','postgres64','postgres7','postgres8','sapdb','sqlanywhere','sqlite','sqlitepo','sybase');
	}
//=============================================
//  sql_db($SQL_server, $SQL_Username, $SQL_password, $SQL_database)
//=============================================
//  When the sql_db object is created it does a
//    few things
//  Variables for logging into the database are
//    passed through
//  Also switch to needed table if it is defined
//=============================================
	
	function database($connect_str)
	{
		if(function_exists('ADONewConnection'))
		{
			$this->db_conn = ADONewConnection($connect_str);  # no need for Connect()
			if($this->db_conn !== false) $this->db_conn->SetFetchMode(ADODB_FETCH_ASSOC);
		}
		
		if(!isset($this->db_conn) || $this->db_conn === false)
		{
			$GLOBALS['settings']['use_database'] = false;
			define('NOT_INSTALLED', true);
			PEAR::raiseError('Something has gone wrong with the connection!', E_DEBUG|E_USER|E_FATAL);
		}
	}
	
	function dropAll()
	{
		// loop through each handler and compile a list of databases
		$GLOBALS['tables'] = array();
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			if(defined($handler . '::DATABASE'))
				$GLOBALS['tables'][] = constant($handler . '::DATABASE');
		}
		$GLOBALS['tables'] = array_values(array_unique($GLOBALS['tables']));
		
		// create handler tables
		foreach($GLOBALS['tables'] as $i => $table)
		{
			$query = 'DROP TABLE ' . $table;
			$result = $this->db_conn->Execute($query);
		}
	}
	
	function installFirstTimeUsers($secret)
	{
		
		$db_user = $this->query(array(
				'SELECT' => 'users',
				'WHERE' => 'Username = "guest"',
				'LIMIT' => 1
			)
		, false);
		
		if( count($db_user) == 0 )
		{
			// create guest user
			$result = $this->query(array('INSERT' => 'users', 'VALUES' => array(
						'id' => -2,
						'Username' => 'guest',
						'Password' => '',
						'Email' => 'guest@bjcullinan.com',
						'Settings' => serialize(array()),
						'Privilage' => 1,
						'PrivateKey' => md5(microtime())
					)
				)
			, false);
		}
		
		$db_user = $this->query(array(
			'SELECT' => 'users',
			'WHERE' => 'Username = "admin"',
			'LIMIT' => 1
		)
		, false);
		
		if( count($db_user) == 0 )
		{
			// create default administrator
			$this->query(array('INSERT' => 'users', 'VALUES' => array(
					'id' => -1,
					'Username' => 'admin',
					'Password' => md5($secret . 'tmppass'),
					'Email' => 'admin@bjcullinan.com',
					'Settings' => serialize(array()),
					'Privilage' => 10,
					'PrivateKey' => md5(microtime())
				)
			)
			, false);
		}
		else
		{
			// update admin with new secret
			$result = $this->query(array(
				'UPDATE' => 'users',
				'VALUES' => array(
					'Password' => md5($secret . 'tmppass')
				)
			)
			, false);
			
			return true;
		}
		
		return false;
	}
	
	// install function
	function install($callback = NULL)
	{
		// create handler tables
		$tables_created = array();
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			$query = 'CREATE TABLE ' . constant($handler . '::DATABASE') . ' (';
			$struct = call_user_func($handler . '::struct');
			if(is_array($struct) && !in_array(constant($handler . '::DATABASE'), $tables_created))
			{
				$tables_created[] = constant($handler . '::DATABASE');
				if(!isset($struct['id']))
					$query .= 'id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id),';
				foreach($struct as $column => $type)
				{
					if(strpos($type, ' ') === false)
						$query .= ' ' . $column . ' ' . $type . ' NOT NULL,';
					else
						$query .= ' ' . $column . ' ' . $type . ',';
				}
				// remove last comma
				$query[strlen($query)-1] = ')';
				
				// query database
				$result = $this->db_conn->Execute($query);
				if($callback !== NULL)
				{
					call_user_func_array($callback, array($result, constant($handler . '::DATABASE')));
				}
			}
		}
	}
	
	function upgrade()
	{
		$tables_updated = array();
		foreach($GLOBALS['handlers'] as $i => $handler)
		{
			$struct = call_user_func($handler . '::struct');
			if(is_array($struct) && !in_array(constant($handler . '::DATABASE'), $tables_updated))
			{
				$tables_updated[] = constant($handler . '::DATABASE');
				
				// first insert a row with id 0 to use for reading
				$ids = $this->query(array('INSERT' => constant($handler . '::DATABASE'), 'VALUES' => array('Filepath' => ''))) or print_r(mysql_error());
				
				// alter table to match the struct
				$files = $this->query(array('SELECT' => constant($handler . '::DATABASE'), 'WHERE' => 'Filepath=""')) or print_r(mysql_error());
				
				if(count($files) > 0)
				{
					$columns = array_keys($files[0]);
						
					// find missing columns
					$query = 'ALTER TABLE ' . constant($handler . '::DATABASE');
					foreach($struct as $column => $type)
					{
						if(!in_array($column, $columns))
						{
							// alter the table
							if(strpos($type, ' ') === false)
								$this->query('ALTER TABLE ' . constant($handler . '::DATABASE') . ' ADD ' . $column . ' ' . $type . ' NOT NULL') or print_r(mysql_error());
							else
								$this->query('ALTER TABLE ' . constant($handler . '::DATABASE') . ' ADD ' . $column . ' ' . $type) or print_r(mysql_error());
						}
						
						if($column != 'id')
						{
							if(strpos($type, ' ') === false)
								$query .= ' MODIFY ' . $column . ' ' . $type . ' NOT NULL,';
							else
								$query .= ' MODIFY ' . $column . ' ' . $type . ',';
						}
					}
					// remove last comma
					$query = substr($query, 0, strlen($query)-1);
					
					// remove unnessicary columns
					if(!isset($struct['id']))
						$struct['id'] = 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)';
					foreach($columns as $i => $key)
					{
						if(!isset($struct[$key]))
						{
							$this->query('ALTER TABLE ' . constant($handler . '::DATABASE') . ' DROP ' . $key) or print_r(mysql_error());
						}
					}
				
					// finally modify the table types
					$this->query($query) or print_r(mysql_error());
				
					// remove id 0
					$files = $this->query(array('DELETE' => constant($handler . '::DATABASE'), 'WHERE' => 'Filepath=""')) or print_r(mysql_error());
				}
			}
		}
	}
	
	
	// compile the statmeent based on an abstract representation
	static function statement_builder($props, $require_permit)
	{
		if($require_permit)
		{
			$where_security = 'LEFT(Filepath, ' . strlen(setting('local_users')) . ') != "' . addslashes(setting('local_users')) . '" OR ' . 
								'Filepath = "' . addslashes(setting('local_users')) . '" OR ' . 
								'(LEFT(Filepath, ' . strlen(setting('local_users')) . ') = "' . addslashes(setting('local_users')) . '" AND LOCATE("/", Filepath, ' . (strlen(setting('local_users')) + 1) . ') = LENGTH(Filepath)) OR ' . 
								'LEFT(Filepath, ' . strlen(setting('local_users') . $_SESSION['user']['Username'] . '/') . ') = "' . addslashes(setting('local_users') . $_SESSION['user']['Username'] . '/') . '" OR ' . 
								'SUBSTR(Filepath, ' . strlen(setting('local_users')) . ' + LOCATE("/", SUBSTR(Filepath, ' . (strlen(setting('local_users')) + 1) . ')), 8) = "/public/"';
			if(isset($_SESSION['settings']['keys_usernames']) && count($_SESSION['settings']['keys_usernames']) > 0)
			{
				foreach($_SESSION['settings']['keys_usernames'] as $i => $username)
				{
					$where_security .= ' OR LEFT(Filepath, ' . strlen(setting('local_users') . $username . '/') . ') = "' . addslashes(setting('local_users') . $username . '/') . '"';
				}
			}
			if(is_string($props['WHERE']))
				$props['WHERE'] = array($props['WHERE']);
			$props['WHERE'][] = $where_security;
		}

		if(!isset($props['WHERE'])) $where = '';
		elseif(is_array($props['WHERE'])) $where = 'WHERE (' . join(') AND (', $props['WHERE']) . ')';
		elseif(is_string($props['WHERE'])) $where = 'WHERE ' . $props['WHERE'];
		
		if(!isset($props['GROUP'])) $group = '';
		elseif(is_string($props['GROUP'])) $group = 'GROUP BY ' . $props['GROUP'];
			
		if(!isset($props['HAVING'])) $having = '';
		elseif(is_array($props['HAVING'])) $having = 'HAVING ' . join(' AND ', $props['HAVING']);
		elseif(is_string($props['HAVING'])) $having = 'HAVING ' . $props['HAVING'];
		
		if(!isset($props['ORDER'])) $order = '';
		elseif(is_string($props['ORDER'])) $order = 'ORDER BY ' . $props['ORDER'];
		
		if(!isset($props['LIMIT'])) $limit = '';
		elseif(is_numeric($props['LIMIT'])) $limit = 'LIMIT ' . $props['LIMIT'];
		elseif(is_string($props['LIMIT'])) $limit = 'LIMIT ' . $props['LIMIT'];
			
		if(isset($props['INSERT']))
		{
			$insert = 'INSERT INTO ' . $props['INSERT'];
			
			if(!isset($props['COLUMNS']) && isset($props['VALUES']) && is_array($props['VALUES']))
			{
				$props['COLUMNS'] = array_keys($props['VALUES']);
				$props['VALUES'] = array_values($props['VALUES']);
			}
			
			if(!isset($props['COLUMNS'])) return false;
			elseif(is_array($props['COLUMNS'])) $columns = '(' . join(', ', $props['COLUMNS']) . ')';
			elseif(is_string($props['COLUMNS'])) $columns = '(' . $props['COLUMNS'] . ')';
			
			if(!isset($props['VALUES']) && !isset($props['SELECT'])) return false;
			
			if(!isset($props['VALUES'])) $values = $props['SELECT'];
			elseif(is_array($props['VALUES'])) $values = 'VALUES ("' . join('", "', $props['VALUES']) . '")';
			elseif(is_string($props['VALUES'])) $values = 'VALUES (' . $props['VALUES'] . ')';

			$statement = $insert . ' ' . $columns . ' ' . $values;
		}
		elseif(isset($props['SELECT']))
		{
			if(!isset($props['COLUMNS'])) $columns = '*';
			elseif(is_array($props['COLUMNS'])) $columns = join(', ', $props['COLUMNS']);
			elseif(is_string($props['COLUMNS'])) $columns = $props['COLUMNS'];
			
			$select = 'SELECT ' . $columns . ' FROM ' . $props['SELECT'];

			$statement = $select . ' ' . $where . ' ' . $group . ' ' . $having . ' ' . $order . ' ' . $limit;
		}
		elseif(isset($props['UPDATE']))
		{
			$update = 'UPDATE ' . $props['UPDATE'] . ' SET';
			
			if(!isset($props['COLUMNS']) && isset($props['VALUES']) && is_array($props['VALUES']))
			{
				$props['COLUMNS'] = array_keys($props['VALUES']);
				$props['VALUES'] = array_values($props['VALUES']);
			}
			
			if(!isset($props['COLUMNS'])) return false;
			elseif(is_array($props['COLUMNS'])) $columns = $props['COLUMNS'];
			elseif(is_string($props['COLUMNS'])) $columns = split(',', $props['COLUMNS']);

			if(!isset($props['VALUES'])) return false;
			elseif(is_array($props['VALUES'])) $values = $props['VALUES'];
			elseif(is_string($props['VALUES'])) $values = split(',', $props['VALUES']);
			
			$set = array();
			foreach($columns as $i => $key)
			{
				$set[] = $key . ' = "' . $values[$i] . '"';
			}

			$statement = $update . ' ' . join(', ', $set) . ' ' . $where . ' ' . $order . ' ' . $limit;
		}
		elseif(isset($props['DELETE']))
		{
			$delete = 'DELETE FROM ' . $props['DELETE'];

			$statement = $delete . ' ' . $where . ' ' . $order . ' ' . $limit;
		}
			
		return $statement;
	}
	
	// function for making calls on the database, this is what is called by the rest of the site
	//   for_users tells the script whether or not these results will eventually be used by modules and template and be printed out
	//   this allows the script to add user permissions filters to the query easily
	function query($props, $require_permit)
	{
		$query = DATABASE::statement_builder($props, $require_permit);
		
		PEAR::raiseError('DATABASE: ' . $query, E_DEBUG);
		
		if(isset($props['CALLBACK']))
		{
			$result = $this->db_query_callback($query);
		}
		else
		{
			$result = $this->db_conn->Execute($query);
		}
		
		if($result !== false && is_array($props) && (isset($props['SELECT']) || isset($props['SHOW'])))
		{
			$output = array();
			while (!$result->EOF)
			{
				if(isset($props['CALLBACK']))
				{
					// this is used for queries too large for memory
					call_user_func_array($props['CALLBACK']['FUNCTION'], array($result->fields, &$props['CALLBACK']['ARGUMENTS']));
				}
				else
				{
					$output[] = $result->fields;
					$result->MoveNext();
				}
			}
			
			if(!isset($props['CALLBACK']))
			{
				return $output;
			}
		}
		elseif($result !== false && is_array($props) && (isset($props['INSERT']) || isset($props['UPDATE']) || isset($props['REPLACE']) || isset($props['DELETE'])))
		{
			if(isset($props['INSERT']))
			{
				$result = $this->db_conn->Execute('SELECT MAX(id) as id FROM ' . $props['INSERT'] . ' LIMIT 1');
				return $result->fields['id'];
			}
			else
			{
				$result = $this->db_conn->Affected_Rows();
				return $result;
			}
		}
		else
		{
			return $result;
		}
	}	
}


