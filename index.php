<?php

// load template
require_once dirname(__FILE__) . '/include/common.php';

if(substr(selfURL(), 0, strlen(SITE_HTMLPATH)) != SITE_HTMLPATH)
{
	header('Location: ' . SITE_HTMLPATH);
	exit();
}

if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
	$smarty = new Smarty;

	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

if(!isset($_SESSION['template']))
{
	if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
		$smarty->display($templates['TEMPLATE_INDEX']);
}
elseif($_SESSION['template'] == 'default/')
{
	include_once SITE_LOCALROOT . 'plugins/query.php';
	if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
		$smarty->display($templates['TEMPLATE_QUERY']);
}
elseif($_SESSION['template'] == 'extjs/')
{
	if($_SERVER['SCRIPT_FILENAME'] == __FILE__)
		$smarty->display($templates['TEMPLATE_INDEX']);
}


?>
