<?php

// attempt to pass all requests to the appropriate plugins

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	header('Location: ' . HTML_DOMAIN . HTML_ROOT);
	exit();
}

if(!isset($_SESSION['template']))
{
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	{
		if(getExt($GLOBALS['templates']['TEMPLATE_INDEX']) == 'php')
			@include $GLOBALS['templates']['TEMPLATE_INDEX'];
		else
			$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_INDEX']);
	}
}
// this means they haven't overrides the index template
// so instead just load query and show that template
elseif($GLOBALS['templates']['TEMPLATE_INDEX'] == LOCAL_ROOT . LOCAL_BASE . 'index.html')
{
	include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'query.php';
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	{
		if(getExt($GLOBALS['templates']['TEMPLATE_QUERY']) == 'php')
			@include $GLOBALS['templates']['TEMPLATE_QUERY'];
		else
			$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_QUERY']);
	}
}
else
{
	if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	{
		if(getExt($GLOBALS['templates']['TEMPLATE_INDEX']) == 'php')
			@include $GLOBALS['templates']['TEMPLATE_INDEX'];
		else
			$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_INDEX']);
	}
}

?>
