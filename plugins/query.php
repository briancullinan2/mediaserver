<?php

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

include_once LOCAL_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'search.php';
include_once LOCAL_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'select.php';
include_once LOCAL_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'type.php';
include_once LOCAL_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'display.php';

$smarty->assign('templates', $templates);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty->display($templates['TEMPLATE_QUERY']);

?>
