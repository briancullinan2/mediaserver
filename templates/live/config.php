<?php
// other stuff can be used here
if(!isset($_REQUEST['dir']))
	$_REQUEST['dir'] = '/';
if(!isset($_REQUEST['limit']))
	$_REQUEST['limit'] = 50;

$GLOBALS['templates']['TEMPLATE_QUERY'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'query.html';
$GLOBALS['templates']['TEMPLATE_SEARCH'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'search.html';
$GLOBALS['templates']['TEMPLATE_PAGES'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'pages.html';

?>