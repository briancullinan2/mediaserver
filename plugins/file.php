<?php

// thing to consider:
// recognize category because that will determine what the id is refering to
// if the type can be handled by a browser then output it, otherwise disposition it

require_once dirname(__FILE__) . '/../include/common.php';

// load mysql to query the database
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// add category
if(!isset($_REQUEST['cat']) || !in_array($GLOBALS['modules']))
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
	
		$file = $mysql->get(db_file::DATABASE, array('WHERE' => 'Filepath = "' . $files[0]['Filepath'] . '"'));
		if(count($file) > 0)
		{
			$files[0] = array_merge($file[0], $files[0]);
		}
		
		// output file
		if(class_exists($_REQUEST['cat'] . '_browser') && call_user_func(array($_REQUEST['cat'], 'handles'), $files[0]['Filepath']))
		{
			// output that mime type and the file
			if($fp = fopen($files[0]['Filepath'], 'rb'))
			{
				header('Content-Type: ' . $files[0]['Filemime']);
				header('Content-Length: ' . $files[0]['Filesize']);
				
				while (!feof($fp)) {
					print fread($fp, BUFFER_SIZE);
				}				
				fclose($fp);
				
			}
			
		}
		else
		{
			// output file and displace
			if($fp = fopen($files[0]['Filepath'], 'rb'))
			{
				header('Content-Transfer-Encoding: binary');
				header('Content-Type: ' . $files[0]['Filemime']);
				header('Content-Length: ' . $files[0]['Filesize']);
				header('Content-Disposition: attachment; filename="' . $files[0]['Filename'] . '"'); 
				
				while (!feof($fp)) {
					print fread($fp, BUFFER_SIZE);
				}				
				fclose($fp);
			}
		}
	}
}

?>