<?php

//set the detail for the template
if( !isset($_REQUEST['detail']) || !is_numeric($_REQUEST['detail']) )
	$_REQUEST['detail'] = 0;

$GLOBALS['templates']['TEMPLATE_SELECT'] = LOCAL_ROOT . LOCAL_BASE . 'select.html';
$GLOBALS['templates']['TEMPLATE_DISPLAY'] = LOCAL_ROOT . LOCAL_BASE . 'display.html';
$GLOBALS['templates']['TEMPLATE_QUERY'] = LOCAL_ROOT . LOCAL_BASE . 'query.html';
$GLOBALS['templates']['TEMPLATE_SEARCH'] = LOCAL_ROOT . LOCAL_BASE . 'search.html';
$GLOBALS['templates']['TEMPLATE_TYPE'] = LOCAL_ROOT . LOCAL_BASE . 'type.html';
$GLOBALS['templates']['TEMPLATE_ADDRESS'] = LOCAL_ROOT . LOCAL_BASE . 'address.html';
$GLOBALS['templates']['TEMPLATE_PAGES'] = LOCAL_ROOT . LOCAL_BASE . 'pages.html';
$GLOBALS['templates']['TEMPLATE_INDEX'] = LOCAL_ROOT . LOCAL_BASE . 'index.html';
$GLOBALS['templates']['TEMPLATE_TEMPLATE'] = LOCAL_ROOT . LOCAL_BASE . 'template.html';

?>