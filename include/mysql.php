<?php

class sql extends sql_global
{

	function sql($SQL_server, $SQL_username, $SQL_password, $SQL_db_name = "")
	{
		$this->db_connect_id = mysql_connect($SQL_server, $SQL_username, $SQL_password) or print_r(mysql_error());
		if ($SQL_db_name != "")
		{
			mysql_select_db($SQL_db_name, $this->db_connect_id)or print_r("Function Error: " . mysql_error());
		}
	}
	
	function getid()
	{
		// use result id to get the row just inserted and assume there is an id to return
		return mysql_insert_id();
	}

	function result()
	{
		$result = array();
		while($row = mysql_fetch_assoc($this->query_result))
		{
			$result[] = $row;
		}
		return $result;
	}
	
	function result_callback($function, $arguments)
	{
		while($row = mysql_fetch_assoc($this->query_result))
		{
			call_user_func_array($function, array(&$row, &$arguments));
		}
	}
	
	function query($query = "")
	{
			
//print_r($query . "\n");
		
		// Remove any pre-existing queries
		unset($this->query_result);
		if($query != "")
		{
			$this->num_queries++;

			$this->query_result = mysql_query($query, $this->db_connect_id) or $error = true;
			if(isset($error)) return false;
		}
		if($this->query_result)
		{
			return $this->query_result;
		}
		else
		{
			return false;
		}
	}
	
	function error()
	{
		return mysql_error($this->db_connect_id);
	}

	function close()
	{
		mysql_close($this->db_connect_id);
	}
	
}


?>