<?php

define('QUERY_PRIV', 				1);

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < QUERY_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . QUERY_PRIV);
	
	exit();
}

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
