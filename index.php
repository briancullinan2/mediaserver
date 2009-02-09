<?php

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	header('Location: ' . HTML_DOMAIN . HTML_ROOT);
	exit();
}

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty();
$smarty->compile_dir = LOCAL_ROOT . 'templates_c' . DIRECTORY_SEPARATOR;

$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

if(!isset($_SESSION['template']))
{
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
		$smarty->display($GLOBALS['templates']['TEMPLATE_INDEX']);
}
elseif($_SESSION['template'] == 'default' . DIRECTORY_SEPARATOR)
{
	include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'query.php';
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
		$smarty->display($GLOBALS['templates']['TEMPLATE_QUERY']);
}
else
{
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
		$smarty->display($GLOBALS['templates']['TEMPLATE_INDEX']);
}

?>
