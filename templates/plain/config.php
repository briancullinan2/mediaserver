<?php

$tmp_columns = getAllColumns();
$columns = array();
foreach($tmp_columns as $i => $column)
{
	$columns[$column] = $column;
}

$GLOBALS['templates']['settings'] = array(
	'view' => array(
		'type' => 'radio',
		'values' => array('mono' => 'Monospace',
						  'table' => 'Table',
						  'dash' => 'Dash delimited')
	),
	'columns' => array(
		'type' => 'checkbox',
		'values' => $columns
	)
);
$GLOBALS['templates']['TEMPLATE_SELECT'] = LOCAL_ROOT . LOCAL_BASE . 'select.html';
$GLOBALS['templates']['TEMPLATE_DISPLAY'] = LOCAL_ROOT . LOCAL_BASE . 'display.html';
$GLOBALS['templates']['TEMPLATE_QUERY'] = LOCAL_ROOT . LOCAL_BASE . 'query.html';
$GLOBALS['templates']['TEMPLATE_SEARCH'] = LOCAL_ROOT . LOCAL_BASE . 'search.html';
$GLOBALS['templates']['TEMPLATE_TYPE'] = LOCAL_ROOT . LOCAL_BASE . 'type.html';
$GLOBALS['templates']['TEMPLATE_ADDRESS'] = LOCAL_ROOT . LOCAL_BASE . 'address.html';
$GLOBALS['templates']['TEMPLATE_PAGES'] = LOCAL_ROOT . LOCAL_BASE . 'pages.html';
$GLOBALS['templates']['TEMPLATE_INDEX'] = LOCAL_ROOT . LOCAL_BASE . 'select.html';
$GLOBALS['templates']['TEMPLATE_TEMPLATE'] = LOCAL_ROOT . LOCAL_BASE . 'template.html';
$GLOBALS['templates']['TEMPLATE_AMPACHE'] = LOCAL_ROOT . LOCAL_BASE . 'ampache.php';
$GLOBALS['templates']['TEMPLATE_LOGIN'] = LOCAL_ROOT . LOCAL_BASE . 'login.php';
$GLOBALS['templates']['TEMPLATE_LOGOUT'] = LOCAL_ROOT . LOCAL_BASE . 'login.php';
$GLOBALS['templates']['TEMPLATE_ADMIN_WATCH'] = LOCAL_ROOT . LOCAL_BASE . 'watch.php';
$GLOBALS['templates']['TEMPLATE_ADMIN_TOOLS'] = LOCAL_ROOT . LOCAL_BASE . 'tools.php';

?>