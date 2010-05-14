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
		case 'admin_status':
			theme('status');
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
				foreach($infos['list'] as $i => $text)
				{
					?><li><?php print_info_objects($text); ?></li><?php
				}
			}
			?></ul><?php
		}
		if(isset($infos['link']))
		{
			if(is_string($infos['link']))
			{
				?><a href="<?php print url($infos['link']); ?>"><?php print htmlspecialchars($infos['link']); ?></a><?php
			}
			elseif(is_array($infos['link']))
			{
				?><a <?php print isset($infos['link']['name'])?$infos['link']['name']:''; ?> href="<?php print url($infos['link']['url']); ?>"><?php print_info_objects($infos['link']['text']); ?></a><?php
			}
		}
		if(isset($infos['code']))
		{
			?><code><?php print_info_objects($infos['code']); ?></code><?php
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
		if(!isset($config['type']))
		{
			print_info_objects($config['value']);
			continue;
		}
		
		switch($config['type'])
		{
			case 'radio':
			case 'checkbox':
				print $config['name'] . ':<br />';
				// check if array is associative or not
				if(array_keys($config['options']) === array_keys(array_keys($config['options'])))
				{
					// numeric keys
					foreach($config['options'] as $option)
					{
						?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> <?php print ($config['value'] == $option)?'checked="checked"':''; ?> type="<?php print $config['type']; ?>" value="<?php print $option; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $option; ?><br /><?php
					}
				}
				else
				{
					// named keys
					foreach($config['options'] as $option => $text)
					{
						?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> <?php print ($config['value'] == $option)?'checked="checked"':''; ?> type="<?php print $config['type']; ?>" value="<?php print $option; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $text; ?><br /><?php
					}
				}
			break;
			case 'text':
				?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> type="text" value="<?php print htmlspecialchars($config['value']); ?>" name="<?php print $field_name; ?>" /><?php
			break;
			case 'select':
				?><select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>"><?php
				// check if array is associative or not
				if(array_keys($config['options']) === array_keys(array_keys($config['options'])))
				{
					// numeric keys
					foreach($config['options'] as $option)
					{
						?><option value="<?php print $option; ?>" <?php print ($config['value'] == $option)?'selected="selected"':''; ?>><?php print $option; ?></option><?php
					}
				}
				else
				{
					// named keys
					foreach($config['options'] as $option => $text)
					{
						?><option value="<?php print $option; ?>" <?php print ($config['value'] == $option)?'selected="selected"':''; ?>><?php print $text; ?></option><?php
					}
				}
				?></select><?php
			break;
			case 'time':
				?>
				<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>[value]" style="width:100px; display:inline; margin-right:0px;">
				<?php
				for($i = 1; $i < 60; $i++)
				{
					?><option value="<?php print $i; ?>" <?php print ($config['value'] == $i || $config['value'] / 60 == $i || $config['value'] / 360 == $i)?'selected="selected"':''; ?>><?php print $i; ?></option><?php
				}
				?>
				</select>
				<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>[multiplier]" style="width:100px; display:inline; margin-right:0px;">
					<option value="1" <?php print ($config['value'] >= 1 && $config['value'] < 60)?'selected="selected"':''; ?>>Seconds</option>
					<option value="60" <?php print ($config['value'] / 60 >= 1 && $config['value'] / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
					<option value="360" <?php print ($config['value'] / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
				</select>
				<?php
			break;
			case 'boolean':
				?>
				<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>">
					<option value="true" <?php print ($config['value'] == true)?'selected="selected"':''; ?>><?php print isset($config['options'][0])?$config['options'][0]:'true'; ?></option>
					<option value="false" <?php print ($config['value'] == false)?'selected="selected"':''; ?>><?php print isset($config['options'][1])?$config['options'][1]:'false'; ?></option>
				</select>
				<?php
			break;
			case 'filesize':
			?>
				<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>[value]" style="width:150px; display:inline; margin-right:0px;">
				<?php
				for($i = 0; $i < 10; $i++)
				{
					?><option value="<?php echo pow(2, $i); ?>" <?php print ($config['value'] / 1024 == pow(2, $i) || $config['value'] / 1048576 == pow(2, $i) || $config['value'] / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php print pow(2, $i); ?></option><?php
				}
				?>
				</select><select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>[multiplier]" style="width:50px; display:inline; margin-right:0px;">
					<option value="1024" <?php print ($config['value'] / 1024 >= 1 && $config['value'] / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
					<option value="1048576" <?php print ($config['value'] / 1048576 >= 1 && $config['value'] / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
					<option value="1073741824" <?php print ($config['value'] / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
				</select>
			<?php
			break;
			case 'label':
				?><label><?php print $config['value']; ?></label><?php
			break;
			case 'submit':
				?><input type="submit" <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print $field_name; ?>" value="<?php print $config['value']; ?>" /><?php
			break;
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