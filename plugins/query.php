<?php

// load template
require_once '../include/common.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

include 'search.php';
include 'select.php';
include 'type.php';
include 'display.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display(SITE_TEMPLATE . 'query.html');

?>
