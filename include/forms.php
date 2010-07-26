<?php


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
		if(isset($infos['type']) && $infos['type'] != 'info')
		{
			print_form_objects(array($infos));
		}
		if(isset($infos['loading']))
		{
			?><img src="<?php print url('module=template&template=live&tfile=images/large-loading.gif'); ?>" alt="loading" /><?php print $infos['loading']; ?><?php
		}
		if(isset($infos['image']))
		{
			?><img src="<?php print $infos['image']; ?>" alt="image" /><?php
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

function print_form_object($field_name, $config)
{
	// do some checks for convenience
	
	// check that the field_name hasn't been used before
	// check that all global keys are machine readable
	// check options keys when they are used
	
	
	// encode the settings
	$config_html = traverse_array($config);
	
	if(isset($config['name']) && $config['type'] != 'fieldset')
	{
		print_info_objects($config['name']);
		?><br /><?php
	}
	
	switch($config['type'])
	{
		case 'form':
			?>
			<form name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" action="<?php print isset($config_html['action'])?$config_html['action']:$GLOBALS['templates']['vars']['get']; ?>" method="<?php print isset($config_html['method'])?$config_html['method']:'post'; ?>">
				<?php
				print_form_objects($config['options']);
				?>
				<div class="submit">
					<input type="submit" name="reset_configuration" value="Reset to Defaults" class="button" />
					<input type="submit" name="save_configuration" value="Save" class="button" style="float:right;" />
				</div>
			</form>
			<?php
		break;
		case 'fieldset':
			?><fieldset><?php
			
			if(isset($config['name']))
			{
				?><legend><?php
				if(isset($config['collapsible']) && $config['collapsible'] == true)
				{
					$id = htmlspecialchars($field_name, ENT_QUOTES);
					$script = "if($('#fieldset_" . $id . "').css('display')=='none'){" . 
						"$('#fieldset_" . $id . "').slideDown('fast');" . 
					"}else{" .
						"$('#fieldset_" . $id . "').slideUp('fast');" . 
					"}return false;";
					?><a href="#" onclick="<?php print $script; ?>"><?php print $config['name']; ?></a><?php
				}
				else
				{
					print $config['name'];
				}
				?></legend><?php
			}
			
			?><table id="fieldset_<?php print $id; ?>" <?php print (isset($config['collapsed']) && $config['collapsed'] == true)?'style="display:none;"':''; ?> border="0" cellpadding="0" cellspacing="0"><?php

			foreach($config['options'] as $name => $field)
			{
				?>
				<tr class="form-row <?php print $field['status']; ?>" id="row_<?php print $name; ?>">
					<td class="form-title"><?php print $field['name']; ?></td>
					<td class="form-field">
					<?php print_form_objects(array($name => array('name' => NULL, 'description' => NULL) + $field)); ?>
					</td>
					<td class="form-desc">
					<?php print_info_objects($field['description']); ?>
					</td>
				</tr>
				<?php
			}
			
			?></table></fieldset><?php
		break;
		case 'set':
			// This provides an API for submitting multiple fields to an associative array
			print_form_objects($config['options']);
		break;
		case 'radio':
		case 'checkbox':
			// check if array is associative or not
			if(array_keys($config['options']) === array_keys(array_keys($config['options'])) && (!isset($config['force_numeric']) || $config['force_numeric'] == false))
			{
				// numeric keys
				foreach($config['options'] as $i => $option)
				{
					?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> <?php print ($config['value'] == $option)?'checked="checked"':''; ?> type="<?php print $config_html['type']; ?>" value="<?php print $config_html['options'][$i]; ?>" name="<?php print htmlspecialchars($field_name, ENT_QUOTES) . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $config_html['options'][$i]; ?><br /><?php
				}
			}
			else
			{
				// named keys
				foreach($config['options'] as $option => $text)
				{
					?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> <?php print ($config['value'] == $option)?'checked="checked"':''; ?> type="<?php print $config_html['type']; ?>" value="<?php print htmlspecialchars($option, ENT_QUOTES); ?>" name="<?php print htmlspecialchars($field_name, ENT_QUOTES) . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $config_html['options'][$option]; ?><br /><?php
				}
			}
		break;
		case 'text':
			?><input <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> type="text" value="<?php print $config_html['value']; ?>" name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" /><?php
		break;
		case 'select':
			?><select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>"><?php
			// check if array is associative or not
			if(array_keys($config['options']) === array_keys(array_keys($config['options'])) && (!isset($config['force_numeric']) || $config['force_numeric'] == false))
			{
				// numeric keys
				foreach($config['options'] as $i => $option)
				{
					?><option value="<?php print $config_html['options'][$i]; ?>" <?php print ($config['value'] == $option)?'selected="selected"':''; ?>><?php print $config_html['options'][$i]; ?></option><?php
				}
			}
			else
			{
				// named keys
				foreach($config['options'] as $option => $text)
				{
					?><option value="<?php print htmlspecialchars($option, ENT_QUOTES); ?>" <?php print ($config['value'] == $option)?'selected="selected"':''; ?>><?php print $config_html['options'][$option]; ?></option><?php
				}
			}
			?></select><?php
		break;
		case 'multiselect':
			?><select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" size="5" multiple="multiple"><?php
			// check if array is associative or not
			if(!is_array($config['value']))
				$config['value'] = array($config['value']);
			if(array_keys($config['options']) === array_keys(array_keys($config['options'])) && (!isset($config['force_numeric']) || $config['force_numeric'] == false))
			{
				// numeric keys
				foreach($config['options'] as $i => $option)
				{
					?><option value="<?php print $config_html['options'][$i]; ?>" <?php print (in_array($option, $config['value']))?'selected="selected"':''; ?>><?php print $config_html['options'][$i]; ?></option><?php
				}
			}
			else
			{
				// named keys
				foreach($config['options'] as $option => $text)
				{
					?><option value="<?php print htmlspecialchars($option, ENT_QUOTES); ?>" <?php print (in_array($option, $config['value']))?'selected="selected"':''; ?>><?php print $config_html['options'][$option]; ?></option><?php
				}
			}
			?></select><?php
		break;
		case 'time':
			?>
			<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>[value]" style="width:100px; display:inline; margin-right:0px;">
			<?php
			for($i = 1; $i < 60; $i++)
			{
				?><option value="<?php print $i; ?>" <?php print ($config['value'] == $i || $config['value'] / 60 == $i || $config['value'] / 360 == $i)?'selected="selected"':''; ?>><?php print $i; ?></option><?php
			}
			?>
			</select>
			<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>[multiplier]" style="width:100px; display:inline; margin-right:0px;">
				<option value="1" <?php print ($config['value'] >= 1 && $config['value'] < 60)?'selected="selected"':''; ?>>Seconds</option>
				<option value="60" <?php print ($config['value'] / 60 >= 1 && $config['value'] / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
				<option value="360" <?php print ($config['value'] / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
			</select>
			<?php
		break;
		case 'boolean':
			?>
			<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>">
				<option value="true" <?php print ($config['value'] == true)?'selected="selected"':''; ?>><?php print isset($config['options'][0])?$config['options'][0]:'true'; ?></option>
				<option value="false" <?php print ($config['value'] == false)?'selected="selected"':''; ?>><?php print isset($config['options'][1])?$config['options'][1]:'false'; ?></option>
			</select>
			<?php
		break;
		case 'filesize':
		?>
			<select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>[value]" style="width:150px; display:inline; margin-right:0px;">
			<?php
			for($i = 0; $i < 10; $i++)
			{
				?><option value="<?php echo pow(2, $i); ?>" <?php print ($config['value'] / 1024 == pow(2, $i) || $config['value'] / 1048576 == pow(2, $i) || $config['value'] / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php print pow(2, $i); ?></option><?php
			}
			?>
			</select><select <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>[multiplier]" style="width:50px; display:inline; margin-right:0px;">
				<option value="1024" <?php print ($config['value'] / 1024 >= 1 && $config['value'] / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
				<option value="1048576" <?php print ($config['value'] / 1048576 >= 1 && $config['value'] / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
				<option value="1073741824" <?php print ($config['value'] / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
			</select>
		<?php
		break;
		case 'label':
			?><label><?php print $config_html['value']; ?></label><?php
		break;
		case 'submit':
			?><input type="submit" <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" value="<?php print $config_html['value']; ?>" /><?php
		break;
		case 'button':
			?><input type="button" <?php print (isset($config['action'])?('onClick="' . $config['action'] . '"'):''); ?> <?php print (isset($config['disabled']) && $config['disabled'] == true)?'disabled="disabled"':'';?> name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" value="<?php print $config_html['value']; ?>" /><?php
		break;
		case 'hidden':
			?><input type="hidden" name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" value="<?php print $config_html['value']; ?>" /><?php
		break;
		case 'info':
			if(!isset($config['value']))
				print_info_objects($config);
			else
				print_info_objects($config['value']);
		break;
		case 'theme':
			theme($config['value']);
	}
	
	// print description
	if(isset($config['description']))
	{
		print_info_objects($config['description']);
	}
}


function print_form_objects($form)
{
	// generate form based on config spec
	foreach($form as $field_name => $config)
	{
		// provide API for switching back to info objects
		if(!is_array($config))
		{
			print_info_objects($config);
		}
		elseif(!isset($config['type']))
		{
			print_form_object($field_name, $config + array('type' => 'info'));
		}
		else
		{
			print_form_object($field_name, $config);
		}
	}
}
