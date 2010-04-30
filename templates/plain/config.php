<?php

function register_plain()
{
	$tmp_columns = getAllColumns();
	$columns = array();
	foreach($tmp_columns as $i => $column)
	{
		$columns[] = $column;
	}
	
	return array(
		'name' => 'Plain Text',
		'description' => 'Default plain text template with base files.',
		'privilage' => 1,
		'path' => __FILE__,
		'files' => array('ampache', 'index', 'm3u', 'users', 'watch'),
		'settings' => array(
			'view' => array(
				'name' => 'View Options',
				'type' => 'radio',
				'options' => array(
					'mono' => 'Monospace',
					'table' => 'Table',
					'dash' => 'Dash delimited'
				),
				'default' => 'table'
			),
			'columns' => array(
				'name' => 'Visible Columns',
				'type' => 'checkbox',
				'options' => $columns,
				'default' => array('Filename', 'Filesize')
			)
		),
		'lists' => array('m3u', 'rss', 'xml', 'wpl')
	);
}

function output_plain()
{
	switch($GLOBALS['templates']['vars']['module'])
	{
		case 'ampache':
			theme('ampache');
		break;
		case 'list':
			theme('list');
		break;
		case 'select':
		case 'index':
			theme('index');
		break;
		case 'login':
		break;
	}
}

?>