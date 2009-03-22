<?php

class sql extends sql_global
{
	var $query_result;
	var $callback_result;

	function sql($SQL_server, $SQL_username, $SQL_password, $SQL_db_name = "", $new = false)
	{
		$this->sql_global();
		
		$this->db_connect_id = mysql_connect($SQL_server, $SQL_username, $SQL_password, $new) or print_r(mysql_error());
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
	
	function numrows()
	{
		return mysql_num_rows($this->query_result);
	}

	function affected()
	{
		return mysql_affected_rows($this->db_connect_id);
	}
	
	function result()
	{
		$result = array();
		if(mysql_num_rows($this->query_result) > 0)
		{
			while($row = mysql_fetch_assoc($this->query_result))
			{
				$result[] = $row;
			}
		}
		@mysql_free_result($this->query_result);
		return $result;
	}
	
	function result_callback($function, $arguments)
	{
		if(mysql_num_rows($this->callback_result) > 0)
		{
			while($row = mysql_fetch_assoc($this->callback_result))
			{
				call_user_func_array($function, array($row, &$arguments));
			}
		}
		@mysql_free_result($this->callback_result);
	}
	
	function db_query($query = "")
	{
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
	
	function db_query_callback($query = "")
	{
		$result = $this->db_query($query);
		$this->callback_result = $result;
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