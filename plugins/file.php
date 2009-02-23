<?php

// thing to consider:
// recognize category because that will determine what the id is refering to
// if the type can be handled by a browser then output it, otherwise disposition it

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load mysql to query the database
if(USE_DATABASE) $database = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
else $database = NULL;

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']))
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

if(isset($_REQUEST['id']))
{
	// get the file path from the database
	$files = call_user_func_array($_REQUEST['cat'] . '::get', array($database, array('id' => $_REQUEST['id']), &$count, &$error));

	if($error == '')
	{
		if(count($files) > 0)
		{
			// output file
			call_user_func_array($_REQUEST['cat'] . '::out', array($database, $files[0]['Filepath'], 'php://output'));
		}
	}
	else
	{
		print $error;
	}
}

?>