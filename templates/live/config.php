<?php

function register_live()
{
	return array(
		'name' => 'Live',
		'description' => 'Live theme, based on Microsoft Live.',
		'privilage' => 1,
		'path' => __FILE__,
		'alter request' => true,
		'files' => array('admin', 'encode', 'footer', 'header', 'index', 'install', 'list', 'plugins', 'search', 'select', 'settings', 'tools', 'users')
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
		case 'ampache':
			theme('ampache');
		break;
		case 'index':
		case 'select':
			theme('index');
		break;
		case 'list':
			theme('list');
		break;
		case 'search':
			theme('search');
		break;
		case 'settings':
			theme('settings');
		break;
		case 'users':
			theme('users');
		break;
		case 'admin':
			theme('admin');
		break;
		case 'admin_alias':
			theme('alias');
		break;
		case 'admin_watch':
			theme('watch');
		break;
		case 'admin_plugins':
			theme('plugins');
		break;
		case 'admin_template':
			theme('template');
		break;
		case 'admin_tools':
			theme('tools');
		break;
		case 'admin_tools_filetools':
			theme('tools_filetools');
		break;
		default:
			theme('default');
	}
}

function theme_live_default()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Plugin: <?php print $GLOBALS['plugins'][$GLOBALS['templates']['vars']['plugin']]['name']; ?></h1>
			<span class="subText">This page requires special parameters that have not been set.  This default page is a placeholder.</span>
	<?php
	
	theme('errors');

	?></div><?php

	theme('footer');
}