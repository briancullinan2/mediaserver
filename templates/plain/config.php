<?php

function register_plain()
{
	$tmp_columns = getAllColumns();
	$columns = array();
	foreach($tmp_columns as $i => $column)
	{
		$columns[$column] = $column;
	}
	
	return array(
		'name' => 'Plain Text',
		'description' => 'Default plain text template with base files.',
		'privilage' => 1,
		'path' => __FILE__,
		'files' => array('ampache', 'index', 'm3u', 'users', 'watch'),
		'settings' => array(
			'view' => array(
				'type' => 'radio',
				'values' => array(
					'mono' => 'Monospace',
					'table' => 'Table',
					'dash' => 'Dash delimited'
				),
				'default' => 'mono'
			),
			'columns' => array(
				'type' => 'checkbox',
				'values' => $columns,
				'default' => array('Filename', 'Filesize')
			)
		),
		'lists' => array('m3u', 'rss', 'xml', 'wpl')
	);
}

function output_plain()
{
	switch($GLOBALS['templates']['vars']['plugin'])
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