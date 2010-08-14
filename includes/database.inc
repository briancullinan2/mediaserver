<?php

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
 * Implementation of register
 * @ingroup register
 */
function register_database()
{
	return array(
		'name' => 'Database',
		'description' => 'Wrapper module for displaying database configuration',
		'privilage' => 1,
		'settings' => array('db_connect', 'db_type', 'db_server', 'db_user', 'db_pass', 'db_name'),
		'depends on' => array('adodb_installed', 'valid_connection', 'cannot_read'),
		'package' => 'core',
	);
}

/**
 * Implementation of setup, this is the only function called and must call others
 * @ingroup setup
 */
function setup_database()
{
	// load database stuff
	define('ADODB_ERROR_HANDLER_TYPE', E_USER_WARNING);
	include_once include_path('adodb5' . DIRECTORY_SEPARATOR . 'adodb-errorhandler.inc.php');
	include_once include_path('adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php');

	if(function_exists('ADONewConnection'))
	{
		$GLOBALS['database'] = ADONewConnection(setting('db_connect'));  # no need for Connect()
			
		// set fetch mode if the database is set up
		if($GLOBALS['database'] !== false)
		{
			$GLOBALS['database']->SetFetchMode(ADODB_FETCH_ASSOC);
		
			// install tables
			foreach(get_handlers(false, true) as $module => $config)
			{
				if(function_exists('install_' . $module))
					invoke_module('install', $module);
				elseif(isset($config['database']) && is_array($config['database']))
					install_table($module, $config['database']);
			}
		}
		else
			unset($GLOBALS['database']);
	}
	
	if(!isset($GLOBALS['database']) || $GLOBALS['database'] === false)
	{
		raise_error('Something has gone wrong with the connection!', E_DEBUG|E_USER|E_FATAL);
	}
}

function install_table($handler, $struct, $callback = NULL)
{
	$query = 'CREATE TABLE IF NOT EXISTS ' . $handler . ' (';

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
	$result = @db_query($query);
	if($callback !== NULL)
	{
		call_user_func_array($callback, array($result, database($handler)));
	}
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if adodb is installed in the include directory
 */
function dependency_adodb_installed($settings)
{
	// the adodb set up also depends on PEAR for error handling make sure pear is installed as well
	return include_path('adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php') !== false;
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true if there is a valid connection and a database GLOBAL exists
 */
function dependency_valid_connection()
{
	if(!isset($GLOBALS['database']))
		return;
	
	return is_object($GLOBALS['database']);
}

function dependency_cannot_read()
{
	if(!isset($GLOBALS['database']))
		return;
		
	// check to see if filesystem is writable
	$result = @db_query('SELECT "test" INTO OUTFILE "/tmp/mediaserver.log"');
	return $result == false;
}

/**
 * Implementation of status
 */
function status_database($settings)
{
	$status = array();
	
	if(dependency('adodb_installed'))
	{
		$options['exists_adodb'] = array(
			'name' => 'ADOdb Library',
			'status' => '',
			'description' => array(
				'list' => array(
					'The system has detected that ADOdb is installed in the includes directory.',
					'ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.',
				),
			),
			'type' => 'label',
			'value' => 'ADOdb Detected',
		);
	}
	else
	{
		$options['exists_adodb'] = array(
			'name' => 'ADOdb Library Missing',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that ADOdb is NOT INSTALLED.',
					'The root of the ADOdb Library must be placed in &lt;site root&gt;/include/adodb5',
					'ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://adodb.sourceforge.net/',
					'text' => 'Get ADOdb',
				),
			),
		);
	}
	
	if(dependency('cannot_read') == false)
	{
		$options['cannot_read'] = array(
			'name' => 'Database Permissions',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'The system has detected that the supplied user account has FILE permissions.',
					'The database should never use file permissions.',
				),
			),
			'value' => array(
				'link' => array(
					'url' => 'http://dev.mysql.com/doc/refman/5.0/en/revoke.html',
					'text' => 'REVOKE syntax',
				),
			),
		);
	}
	
	return $status;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return 'mysql' by default
 */
function setting_db_type($settings)
{
	if(isset($settings['db_type']) && in_array($settings['db_type'], supported_databases()))
		return $settings['db_type'];
	else
		return 'mysql';
}

function supported_databases()
{
	return $supported_databases = array('access','ado','ado_access','ado_mssql','db2','odbc_db2','vfp','fbsql','ibase','firebird','borland_ibase','informix','informix72','ldap','mssql','mssqlpo','mysql','mysqli','mysqlt','maxsql','oci8','oci805','oci8po','odbc','odbc_mssql','odbc_oracle','odbtp','odbtp_unicode','oracle','netezza','pdo','postgres','postgres64','postgres7','postgres8','sapdb','sqlanywhere','sqlite','sqlitepo','sybase');
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
	$settings['db_type'] = setting('db_type');
	$settings['db_server'] = setting('db_server');
	$settings['db_user'] = setting('db_user');
	$settings['db_pass'] = setting('db_pass');
	$settings['db_name'] = setting('db_name');
	
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
 * stolen from PEAR, 
 * DSN parser for use internally
 * @return an associative array of parsed DSN information
 */
function parseDSN($dsn)
{
	$parsed = array();
	if (is_array($dsn)) {
		$dsn = array_merge($parsed, $dsn);
		if (!$dsn['dbsyntax']) {
			$dsn['dbsyntax'] = $dsn['phptype'];
		}
		return $dsn;
	}

	// Find phptype and dbsyntax
	if (($pos = strpos($dsn, '://')) !== false) {
		$str = substr($dsn, 0, $pos);
		$dsn = substr($dsn, $pos + 3);
	} else {
		$str = $dsn;
		$dsn = null;
	}

	// Get phptype and dbsyntax
	// $str => phptype(dbsyntax)
	if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
		$parsed['phptype']  = $arr[1];
		$parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
	} else {
		$parsed['phptype']  = $str;
		$parsed['dbsyntax'] = $str;
	}

	if (!count($dsn)) {
		return $parsed;
	}

	// Get (if found): username and password
	// $dsn => username:password@protocol+hostspec/database
	if (($at = strrpos($dsn,'@')) !== false) {
		$str = substr($dsn, 0, $at);
		$dsn = substr($dsn, $at + 1);
		if (($pos = strpos($str, ':')) !== false) {
			$parsed['username'] = rawurldecode(substr($str, 0, $pos));
			$parsed['password'] = rawurldecode(substr($str, $pos + 1));
		} else {
			$parsed['username'] = rawurldecode($str);
		}
	}

	// Find protocol and hostspec

	// $dsn => proto(proto_opts)/database
	if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
		$proto       = $match[1];
		$proto_opts  = $match[2] ? $match[2] : false;
		$dsn         = $match[3];

	// $dsn => protocol+hostspec/database (old format)
	} else {
		if (strpos($dsn, '+') !== false) {
			list($proto, $dsn) = explode('+', $dsn, 2);
		}
		if (   strpos($dsn, '//') === 0
			&& strpos($dsn, '/', 2) !== false
			&& $parsed['phptype'] == 'oci8'
		) {
			//oracle's "Easy Connect" syntax:
			//"username/password@[//]host[:port][/service_name]"
			//e.g. "scott/tiger@//mymachine:1521/oracle"
			$proto_opts = $dsn;
			$dsn = substr($proto_opts, strrpos($proto_opts, '/') + 1);
		} elseif (strpos($dsn, '/') !== false) {
			list($proto_opts, $dsn) = explode('/', $dsn, 2);
		} else {
			$proto_opts = $dsn;
			$dsn = null;
		}
	}

	// process the different protocol options
	$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
	$proto_opts = rawurldecode($proto_opts);
	if (strpos($proto_opts, ':') !== false) {
		list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
	}
	if ($parsed['protocol'] == 'tcp') {
		$parsed['hostspec'] = $proto_opts;
	} elseif ($parsed['protocol'] == 'unix') {
		$parsed['socket'] = $proto_opts;
	}

	// Get dabase if any
	// $dsn => database
	if ($dsn) {
		// /database
		if (($pos = strpos($dsn, '?')) === false) {
			$parsed['database'] = $dsn;
		// /database?param1=value1&param2=value2
		} else {
			$parsed['database'] = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 1);
			if (strpos($dsn, '&') !== false) {
				$opts = explode('&', $dsn);
			} else { // database?param1=value1
				$opts = array($dsn);
			}
			foreach ($opts as $opt) {
				list($key, $value) = explode('=', $opt);
				if (!isset($parsed[$key])) {
					// don't allow params overwrite
					$parsed[$key] = rawurldecode($value);
				}
			}
		}
	}

	return $parsed;
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
function configure_database($settings, $request)
{
	$settings['db_connect'] = setting('db_connect');
	$settings['dberror'] = validate($settings, 'dberror');
	
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
	
	return $options;
}

function db_assoc($query, $args = array(), $key = NULL)
{
	$result = db_query($query, $args);
	
	$output = array();
	
	if($result)
	{
		while (!$result->EOF)
		{
			if($key !== NULL && isset($result->fields[$key]))
				$output[$result->fields[$key]] = $result->fields;
			else
				$output[] = $result->fields;
			$result->MoveNext();
		}
	}
	
	return $output;
}

function db_list($query, $args = array(), $key = 'Filepath')
{
	$result = db_query($query, $args);
	
	$output = array();
	
	if($result)
	{
		while (!$result->EOF)
		{
			if(isset($result->fields[$key]))
				$output[] = $result->fields[$key];
			$result->MoveNext();
		}
	}
	
	return $output;
}

function db_insert($query, $args = array())
{
	$result = db_query($query, $args);
	
	if($result)
		return $GLOBALS['database']->Insert_ID();
	else
		return false;
}

function db_select($query, $args = array(), $callback)
{
	$result = db_query($query, $args);
	
	if($result && isset($callback))
	{
		while (!$result->EOF)
		{
			call_user_func_array($callback, array($result->fields));
			$result->MoveNext();
		}
	}
	
	return $result;
}

function db_query($query, $args = array())
{
	if(!isset($GLOBALS['database']) || !is_object($GLOBALS['database']))
	{
		raise_error('Database queried but is not set up!', E_DEBUG);
		return false;
	}
	//if(setting('verbose') == true)
		raise_error('DATABASE: ' . $query, E_DEBUG);
	
	return $GLOBALS['database']->Execute($query, $args);
}


function sql_insert($fileinfo)
{
	return ' (' . implode(',', array_keys($fileinfo)) . ') VALUES (' . implode(',', array_fill(0, count($fileinfo), '?')) . ')';
}

function sql_update($fileinfo)
{
	return ' SET ' . implode(', ', array_map(create_function('$key', 'return $key . "=?";'), array_keys($fileinfo)));
}
