<?php

function register_live()
{
	return array(
		'name' => 'Live',
		'description' => 'Live theme, based on Microsoft Live.',
		'privilage' => 1,
		'path' => __FILE__,
		'alter request' => true,
		'files' => array('header', 'index', 'search')
	);
}

function alter_request_live($request)
{
	// other stuff can be used here
	if(!isset($request['dir']))
		$request['dir'] = '/';
	if(!isset($request['limit']))
		$request['limit'] = 50;
		
	return $request;
}

function output_live()
{
	switch($GLOBALS['templates']['vars']['plugin'])
	{
		case 'index':
		case 'select':
			theme('index');
		break;
		case 'search':
			theme('search');
	}
}
