<?php

// load template
require_once 'include/common.php';

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;


if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty->display($templates['TEMPLATE_INDEX']);

?>
