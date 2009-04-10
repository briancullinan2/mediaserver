<?php

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'search.php';
include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'select.php';
include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'type.php';
include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'display.php';

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_QUERY']) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_QUERY'];
	else
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_QUERY']);
}

?>
