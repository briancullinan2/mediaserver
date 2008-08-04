<?php

// load template
require_once '../include/common.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

include_once 'search.php';
include_once 'select.php';
include_once 'type.php';
include_once 'display.php';

$smarty->assign('templates', $templates);
if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display($templates['TEMPLATE_QUERY']);

?>
