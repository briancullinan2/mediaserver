<?php

// thing to consider:
// recognize category because that will determine what the id is refering to
// if the type can be handled by a browser then output it, otherwise disposition it

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = 'db_file';

if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
{
	// get the file path from the database
	$files = call_user_func(array($_REQUEST['cat'], 'get'), $mysql,
		array(
			'WHERE' => 'id = ' . $_REQUEST['id'],
		)
	);
	
	if(count($files) > 0)
	{
			
		// output file
		if(class_exists($_REQUEST['cat'] . '_browser') && call_user_func(array($_REQUEST['cat'], 'handles'), $files[0]['Filepath']))
		{
			// output that mime type and the file
			header('Content-Type: ' . $files[0]['Filemime']);
			header('Content-Length: ' . $files[0]['Filesize']);
			
			call_user_func(array($_REQUEST['cat'], 'out'), $mysql, $files[0]['Filepath'], STDOUT);
		}
		else
		{
			// output file and displace
			
			call_user_func(array($_REQUEST['cat'], 'out'), $mysql, $files[0]['Filepath'], 'php://output');
		}
	}
}

?>