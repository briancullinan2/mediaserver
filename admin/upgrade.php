<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( loggedIn() )
{
}
else
{
	// redirect to login page
	header('Location: login.php?return=' . $_SERVER['REQUEST_URI']);
	
	exit();
}

// from version 0.40.0 to 0.40.1

// install new tables
$GLOBALS['database']->install();

// alter ids table

// go through each database and add the keys to db_ids
foreach($GLOBALS['tables'] as $i => $db)
{
	if($db != 'ids' && $db != 'amazon' && $db != 'files')
		$GLOBALS['database']->query(array(
			'SELECT' => $db,
			'COLUMNS' => 'Filepath,id',
			'CALLBACK' => array(
				'FUNCTION' => 'id_update',
				'ARGUMENTS' => array('db' => $db)
			)
		));
}

// remove duplicates
db_ids::cleanup();

function id_update($row, $args)
{
	$GLOBALS['database']->query(array(
		'UPDATE' 	=> 'ids',
		'COLUMNS' 	=> $args['db'] . '_id',
		'VALUES' 	=> $row['id'],
		'WHERE' 	=> 'Filepath = "' . addslashes($row['Filepath']) . '"'
	));
	if($GLOBALS['database']->affected() == 0)
	{
		$GLOBALS['database']->query(array(
			'INSERT' => 'ids',
			'VALUES' => array(
				$args['db'] . '_id' => $row['id'],
				'Filepath' 			=> addslashes($row['Filepath']),
				'Hex' 				=> bin2hex($row['Filepath'])
			)
		));
	}
}

?>