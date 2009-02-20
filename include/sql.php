<?php

//$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'common.php';

// control lower level handling of each database
// things to consider:
// audio-database (for storing artist, album, track fields)
// file-database (used primarily by the virtualfs to storing file information)
// picture-database (for storing picture information)
// video-database (for storing all video information)
// watch-database (a list of directories that should be watched for media files)

// everything should fit into the main 3 mediums (music,pictures,videos) and everything else is just a file
// scalability (add a calendar handler? rss-handler?)

// database structure:
/*
File:
id
Filename (for quick reference)
Filepath (full filesystem path)
Filesize (size in bytes)
Filemime (mime-type, or file extension for unrecognized files, file extensions and mime-types in lowercase, FOLDER if actually a folder, FILE if no extension)
Filedate (the access date of the file)
Filetype (the connected information database MUSIC,PHOTO,VIDEO)
Fileinfo (the id of the file information from the connected database)

Audio:
id
Title
Artist
Album
Track
Year
Genre
Length
Comments
Other
Bitrate
Fileinfo (the id of the entry containing the file info)

Photo:
id

Video:

Watch: (mainly used by the cron.php to update all directories)
id
Filepath
Lastwatch (the last time the directory was searched, even partially)

*/

// pretty self explanator handler class for sql databases
class sql_global
{
	var $db_connect_id;
	var $rowset = array();
	var $num_queries = 0;
	var $table_prefix = DB_PREFIX;
	var $databases = array();

//=============================================
//  sql_db($SQL_server, $SQL_Username, $SQL_password, $SQL_database)
//=============================================
//  When the sql_db object is created it does a
//    few things
//  Variables for logging into the database are
//    passed through
//  Also switch to needed table if it is defined
//=============================================
	function sql_global()
	{
		$this->database = array();
		// loop through each module and compile a list of databases
		foreach($GLOBALS['modules'] as $i => $module)
		{
			if(defined($module . '::DATABASE'))
				$this->databases[] = constant($module . '::DATABASE');
		}
		
		// TODO: should this go here?
		$this->databases[] = 'alias';
		$this->databases[] = 'watch';
		$this->databases[] = 'watch_list';
	}
	
	// install function
	function install()
	{
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'watch (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filepath		TEXT NOT NULL,
				Lastwatch		DATETIME
			)') or print_r(mysql_error());
			
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'watch_list (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filepath		TEXT NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'alias (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Paths			TEXT NOT NULL,
				Alias			TEXT NOT NULL,
				Paths_regexp	TEXT NOT NULL,
				Alias_regexp	TEXT NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'files (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filename		TEXT NOT NULL,
				Filepath		TEXT NOT NULL,
				Filesize		BIGINT NOT NULL,
				Filemime		TEXT NOT NULL,
				Filedate		DATETIME,
				Filetype		TEXT NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'audio (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filepath		TEXT NOT NULL,
				Title			TEXT NOT NULL,
				Artist			TEXT NOT NULL,
				Album			TEXT NOT NULL,
				Track			INT NOT NULL,
				Year			INT NOT NULL,
				Genre			TEXT NOT NULL,
				Length			DOUBLE NOT NULL,
				Comments		TEXT NOT NULL,
				Bitrate			DOUBLE NOT NULL
			)') or print_r(mysql_error());

		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'image (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filepath		TEXT NOT NULL,
				Height			INT NOT NULL,
				Width			INT NOT NULL,
				Make			TEXT NOT NULL,
				Model			TEXT NOT NULL,
				Comments		TEXT NOT NULL,
				Keywords		TEXT NOT NULL,
				Title			TEXT NOT NULL,
				Author			TEXT NOT NULL,
				ExposureTime	TEXT NOT NULL,
				Thumbnail		BLOB NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'archive (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filename		TEXT NOT NULL,
				Filepath		TEXT NOT NULL,
				Compressed		BIGINT NOT NULL,
				Filesize		BIGINT NOT NULL,
				Filemime		TEXT NOT NULL,
				Filedate		DATETIME,
				Filetype		TEXT NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'diskimage (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filename		TEXT NOT NULL,
				Filepath		TEXT NOT NULL,
				Filesize		BIGINT NOT NULL,
				Filemime		TEXT NOT NULL,
				Filedate		DATETIME,
				Filetype		TEXT NOT NULL
			)') or print_r(mysql_error());
		
	}
	
	// compile the statmeent based on an abstract representation
	function statement_builder($props)
	{
		if(is_string($props))
		{
			return $props;
		}
		elseif(is_array($props))
		{
			if(!isset($props['WHERE'])) $where = '';
			elseif(is_array($props['WHERE'])) $where = 'WHERE ' . join(' AND ', $props['WHERE']);
			elseif(is_string($props['WHERE'])) $where = 'WHERE ' . $props['WHERE'];
				
			if(!isset($props['GROUP'])) $group = '';
			elseif(is_string($props['GROUP'])) $group = 'GROUP BY ' . $props['GROUP'];
				
			if(!isset($props['HAVING'])) $having = '';
			elseif(is_array($props['HAVING'])) $having = 'HAVING ' . join(' AND ', $props['HAVING']);
			elseif(is_string($props['HAVING'])) $having = 'HAVING ' . $props['HAVING'];
			
			if(!isset($props['ORDER'])) $order = '';
			elseif(is_string($props['ORDER'])) $order = 'ORDER BY ' . $props['ORDER'];
			
			if(!isset($props['LIMIT'])) $limit = '';
			elseif(is_string($props['LIMIT'])) $limit = 'LIMIT ' . $props['LIMIT'];
				
			if(isset($props['SELECT']))
			{
				if(!isset($props['COLUMNS'])) $columns = '*';
				elseif(is_array($props['COLUMNS'])) $columns = join(', ', $props['COLUMNS']);
				elseif(is_string($props['COLUMNS'])) $columns = $props['COLUMNS'];
				
				$select = 'SELECT ' . $columns . ' FROM ' . (in_array($props['SELECT'], $this->databases)?($this->table_prefix . $props['SELECT']):$props['SELECT']);
				
				$statement = $select . ' ' . $where . ' ' . $group . ' ' . $having . ' ' . $order . ' ' . $limit;
			}
			elseif(isset($props['UPDATE']))
			{
				$update = 'UPDATE ' . $this->table_prefix . $props['UPDATE'] . ' SET';
				
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
			elseif(isset($props['INSERT']))
			{
				$insert = 'INSERT INTO ' . $this->table_prefix . $props['INSERT'];
				
				if(!isset($props['COLUMNS']) && isset($props['VALUES']) && is_array($props['VALUES']))
				{
					$props['COLUMNS'] = array_keys($props['VALUES']);
					$props['VALUES'] = array_values($props['VALUES']);
				}
				
				if(!isset($props['COLUMNS'])) return false;
				elseif(is_array($props['COLUMNS'])) $columns = '(' . join(', ', $props['COLUMNS']) . ')';
				elseif(is_string($props['COLUMNS'])) $columns = '(' . $props['COLUMNS'] . ')';
				
				if(!isset($props['VALUES'])) return false;
				elseif(is_array($props['VALUES'])) $values = 'VALUES ("' . join('", "', $props['VALUES']) . '")';
				elseif(is_string($props['VALUES'])) $values = 'VALUES (' . $props['VALUES'] . ')';
	
				$statement = $insert . ' ' . $columns . ' ' . $values;
			}
			elseif(isset($props['DELETE']))
			{
				$delete = 'DELETE FROM ' . $this->table_prefix . $props['DELETE'];
	
				$statement = $delete . ' ' . $where . ' ' . $order . ' ' . $limit;
			}
			else
			{
				return $props;
			}
			
			return $statement;
		}
	}
	
	// function for making calls on the database, this is what is called by the rest of the site
	function query($props)
	{
		$query = $this->statement_builder($props);
//print $query . '<br />';
		$result = $this->db_query($query);
		
		if($result !== false && (isset($props['SELECT']) || isset($props['SHOW'])))
		{
			// this is used for queries too large for memory
			if(isset($props['CALLBACK']))
			{
				$this->result_callback($props['CALLBACK']['FUNCTION'], $props['CALLBACK']['ARGUMENTS']);
			}
			else
			{
				return $this->result();
			}
		}
		elseif($result !== false && (isset($props['INSERT']) || isset($props['UPDATE']) || isset($props['REPLACE']) || isset($props['DELETE'])))
		{
			return $this->affected();
		}
		else
		{
			return $result;
		}
	}
	
	
//=============================================
//  getid()
//=============================================
//  returns the id from the last insert operations
//=============================================
	function getid()
	{

	}

//=============================================
//  result()
//=============================================
//  returns the results from the previous query in associated array form
//=============================================
	function result()
	{

	}
	
//=============================================
//  count()
//=============================================
//  returns the number of rows selected from the database
//=============================================
	function numrows()
	{

	}
	
//=============================================
//  numrows()
//=============================================
//  returns the number of affected rows from the database
//=============================================
	function affected()
	{

	}

//=============================================
//  query($query = "")
//=============================================
//  Just a handler for database queries specific to the objects connect id
//=============================================
	function db_query($query = "")
	{
		
	}

	function error()
	{
		
	}
	
//=============================================
//  close()
//=============================================
//  close the connection
//=============================================
	function close()
	{

	}
	
}



?>