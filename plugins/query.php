<?php

// load template
require_once '../include/common.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

include 'search.php';
include 'select.php';
include 'type.php';
include 'display.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_LOCALROOT . SITE_TEMPLATE . 'query.html');

?>
