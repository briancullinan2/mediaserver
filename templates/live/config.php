<?php

function register_live()
{
	return array(
		'name' => 'Live',
		'description' => 'Live theme, based on Microsoft Live.',
		'privilage' => 1,
		'path' => __FILE__,
		'alter request' => true,
		'files' => array('admin', 'encode', 'footer', 'header', 'index', 'install', 'list', 'modules', 'search', 'select', 'settings', 'tools', 'users')
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
		print 'hit';
	switch($GLOBALS['templates']['vars']['module'])
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
		case 'admin_modules':
			theme('modules');
		break;
		case 'admin_template':
			theme('template');
		break;
		case 'admin_tools':
			theme('tools');
		break;
		case 'admin_tools_statistics':
		case 'admin_tools_filetools':
			theme('tools_subtools');
		break;
		default:
			theme('default');
	}
}

function print_info_objects($infos)
{
	if(is_string($infos))
	{
		print $infos;
	}
	else
	{
		?><div class="<?php print isset($infos['type'])?$infos['type']:'info'; ?>"><?php
		if(isset($infos['label']))
		{
			?><span><?php print $infos['label']; ?>: </span><?php
		}
		if(isset($infos['list']))
		{
			?><ul><?php
			if(is_string($infos['list']))
			{
				?><li><?php print $infos['list']; ?></li><?php
			}
			elseif(is_array($infos['list']))
			{
				?><li><?php print implode('</li><li>', $infos['list']); ?></li><?php
			}
			?></ul><?php
		}
		if(isset($infos['text']))
		{
			if(is_string($infos['text']))
			{
				print $infos['text'];
			}
			elseif(is_array($infos['text']))
				print_info_objects($infos['text']);
		}
		
		foreach($infos as $key => $value)
		{
			if(is_numeric($key))
			{
				if(is_string($value))
				{
					print $value; ?><br /><?php
				}
				// treat value like sub info object
				elseif(is_array($value))
					print_info_objects($value);
			}
		}
		?></div><?php
	}
}

function print_form_objects($form)
{
	// generate form based on config spec
	foreach($form as $field_name => $config)
	{
		if($config['type'] == 'radio' || $config['type'] == 'checkbox')
		{
			print $config['name'] . ':<br />';
			// check if array is associative or not
			if(array_keys($config['values']) === array_keys(array_keys($config['values'])))
			{
				// numeric keys
				foreach($config['values'] as $value)
				{
					?><input type="<?php print $config['type']; ?>" value="<?php print $value; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $value; ?><br /><?php
				}
			}
			else
			{
				// named keys
				foreach($config['values'] as $value => $text)
				{
					?><input type="<?php print $config['type']; ?>" value="<?php print $value; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $text; ?><br /><?php
				}
			}
		}
		elseif($config['type'] == 'text')
		{
			?><input type="<?php print $config['type']; ?>" value="<?php print htmlspecialchars($config['value']); ?>" name="<?php print $field_name; ?>" /><?php
		}
		elseif($config['type'] == 'select')
		{
			?><select name="<?php print $field_name; ?>"><?php
			// check if array is associative or not
			if(array_keys($config['values']) === array_keys(array_keys($config['values'])))
			{
				// numeric keys
				foreach($config['values'] as $value)
				{
					?><option value="<?php print $value; ?>"><?php print $value; ?></option><?php
				}
			}
			else
			{
				// named keys
				foreach($config['values'] as $value => $text)
				{
					?><option value="<?php print $value; ?>"><?php print $text; ?></option><?php
				}
			}
			?></select><?php
		}
	}
}

function theme_live_default()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Module: <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name']; ?></h1>
			<span class="subText">This page requires special parameters that have not been set.  This default page is a placeholder.</span>
	<?php
	
	theme('errors');

	?></div><?php

	theme('footer');
}