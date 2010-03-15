<?php
// other stuff can be used here
if(!isset($_REQUEST['dir']))
	$_REQUEST['dir'] = '/';
if(!isset($_REQUEST['limit']))
	$_REQUEST['limit'] = 50;

$GLOBALS['templates']['TEMPLATE_INDEX'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'index.html';
$GLOBALS['templates']['TEMPLATE_SELECT'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'index.html';
$GLOBALS['templates']['TEMPLATE_SEARCH'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'search.html';
$GLOBALS['templates']['TEMPLATE_PAGES'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'pages.html';
$GLOBALS['templates']['TEMPLATE_HEADER'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'header.html';
$GLOBALS['templates']['TEMPLATE_FOOTER'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'footer.html';
$GLOBALS['templates']['TEMPLATE_INSTALL'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'install.php';
$GLOBALS['templates']['TEMPLATE_ADMIN_PLUGINS'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'plugins.php';

?>