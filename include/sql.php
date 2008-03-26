<?php

require_once 'settings.php';

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
	var $query_result;
	var $rowset = array();
	var $num_queries = 0;
	var $table_prefix = DB_PREFIX;

//=============================================
//  sql_db($SQL_server, $SQL_Username, $SQL_password, $SQL_database)
//=============================================
//  When the sql_db object is created it does a
//    few things
//  Variables for logging into the database are
//    passed through
//  Also switch to needed table if it is defined
//=============================================
	function sql_global($SQL_server, $SQL_username, $SQL_password, $SQL_db_name = "")
	{

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
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'files (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Filename		TEXT NOT NULL,
				Filepath		TEXT NOT NULL,
				Filesize		BIGINT NOT NULL,
				Filemime		TEXT NOT NULL,
				Filedate		DATETIME,
				Filetype		TEXT NOT NULL,
				Fileinfo		INT NOT NULL
			)') or print_r(mysql_error());
		
		$this->query('CREATE TABLE IF NOT EXISTS ' . $this->table_prefix . 'audio (
				id 				INT NOT NULL AUTO_INCREMENT,
								PRIMARY KEY(id),
				Title			TEXT NOT NULL,
				Artist			TEXT NOT NULL,
				Album			TEXT NOT NULL,
				Track			INT NOT NULL,
				Year			INT NOT NULL,
				Genre			TEXT NOT NULL,
				Length			REAL NOT NULL,
				Comments		TEXT NOT NULL,
				Bitrate			INT NOT NULL,
				Fileinfo		INT NOT NULL
			)') or print_r(mysql_error());

		
	}
	
	// get the list of watched folders, just the paths
	// returns an array of strings of the paths
	function getWatched()
	{
		$this->query('SELECT Filepath FROM ' . $this->table_prefix . 'watch');
		
		return $this->result();
	}
	
	// get function that gets the listed colums and returns the assiciated array
	function get($table, $items, $where = NULL)
	{
		$select = '';
		
		foreach($items as $i => $item)
		{
			$select .= $item . ',';
		}
		// remove last comma
		$select = substr($select, 0, strlen($select)-1);
		
		if( $where == NULL )
		{
			// select
			$this->query('SELECT ' . $select . ' FROM ' . $this->table_prefix . $table);
		}
		else
		{
			$where_str = '';
			if( is_array($where) )
			{
				foreach($where as $key => $value)
				{
					$where_str .= ' ' . $key . ' = "' . $value . '" AND';
				}
				// remove last AND
				$where_str = substr($where_str, 0, strlen($where_str)-3);
			}
			elseif( is_string($where) )
			{
				$where_str = $where;
			}
			
			// select
			$this->query('SELECT ' . $select . ' FROM ' . $this->table_prefix . $table . ' WHERE ' . $where_str);
		}
		
		return $this->result();
		
	}
	
	// set function for settings values in the table
	function set($table, $values, $where = NULL)
	{
		// if the where is null then we are doing and insert
		if( $where == NULL )
		{
		
			// create strings to insert
			$fields = '';
			$value_str = '';
			
			foreach($values as $key => $value)
			{
				$fields .= $key . ',';
				$value_str .= '"' . $value . '",';
			}
			// remove last comma
			$fields = substr($fields, 0, strlen($fields)-1);
			$value_str = substr($value_str, 0, strlen($value_str)-1);
			
			// insert into table
			$this->query('INSERT INTO ' . $this->table_prefix . $table . ' (' . $fields . ') VALUES(' . $value_str . ')');
			
			// return id of last inserted item
			return $this->getid();
			
		}
		else
		{
			$where_str = '';
			if( is_array($where) )
			{
				foreach($where as $key => $value)
				{
					$where_str .= ' ' . $key . ' = "' . $value . '" AND';
				}
				// remove last comma
				$where_str = substr($where_str, 0, strlen($where_str)-3);
			}
			elseif( is_string($where) )
			{
				$where_str = $where;
			}
			
			// if the values is null then we are removing
			if( $values == NULL )
			{
				// remove from table
				$this->query('DELETE FROM ' . $this->table_prefix . $table . ' WHERE ' . $where_str);
			}
			else
			{
				// we are updating existing entries
				$set_str = '';
				foreach($values as $key => $value)
				{
					$set_str .= ' ' . $key . ' = "' . $value . '",';
				}
				// remove last comma
				$set_str = substr($set_str, 0, strlen($set_str)-1);
				
				// set in table
				$this->query('UPDATE ' . $this->table_prefix . $table . ' SET ' . $set_str . ' WHERE ' . $where_str);
			}
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
//  query($query = "")
//=============================================
//  Just a handler for SQL queries specific to the objects connect id
//=============================================
	function query($query = "")
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