<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// from version 0.40.0 to 0.40.1

// install new tables
$GLOBALS['database']->install();

// alter ids table

// go through each database and add the keys to db_ids
foreach($GLOBALS['databases'] as $i => $db)
{
	
}


?>