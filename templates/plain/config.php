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
		'files' => array('ampache', 'login', 'm3u'),
		'settings' => array(
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
		),
		'lists' => array('m3u', 'rss')
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
			
		break;
	}
}

?>