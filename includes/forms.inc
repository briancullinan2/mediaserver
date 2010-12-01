<?php


function theme__info_objects($infos)
{
	if(is_string($infos))
	{
		print $infos;
	}
	else
	{
		foreach($infos as $key => $value)
		{
			if(is_numeric($key))
			{
				if(is_string($value))
				{
					print htmlspecialchars($value); ?><br /><?php
				}
				// treat value like sub info object
				elseif(is_array($value))
					theme('info_objects', $value);
			}
			else
			{
				switch($key)
				{
					case 'type':
						if($infos['type'] != 'info')
						{
							theme('form_objects', array($infos));
						}
						break;
					case 'name':
						break;
					default:
						if(theme_implements('info_' . $key))
						{
							theme('info_' . $key, $value);
						}
						else
							raise_error('Info type \'' . $key . '\' not found!', E_DEBUG);
				}
			}
		}
	}
}

function theme__info_label($value)
{
	?><span><?php print htmlspecialchars($value); ?>: </span><?php
}

function theme__info_loading($value)
{
	?><img src="<?php print url('templates/live/images/large-loading.gif'); ?>" alt="loading" /><?php print htmlspecialchars($value); ?><?php
}

function theme__info_image($value)
{
	?><img src="<?php print htmlspecialchars($value); ?>" alt="image" /><?php
}

function theme__info_literal($value)
{
	print $value;
}

function theme__info_list($value)
{
	?><ul><?php
	if(is_string($value))
	{
		?><li><?php print htmlspecialchars($value); ?></li><?php
	}
	elseif(is_array($value))
	{
		foreach($value as $key => $text)
		{
			?><li><?php theme('info_objects', array($key => $text)); ?></li><?php
		}
	}
	?></ul><?php
}

function theme__info_link($value)
{
	if(is_string($value))
	{
		?><a href="<?php print url($value); ?>"><?php print htmlspecialchars($value); ?></a><?php
	}
	elseif(is_array($value))
	{
		extract($value);
		?><a <?php print isset($name)?('name="'.$name.'"'):''; ?> href="<?php print url($url); ?>"><?php theme('info_objects', $text); ?></a><?php
	}
}

function theme__info_code($value)
{
	?><code><?php theme('info_objects', $value); ?></code><?php
}

function theme__info_text($value)
{
	if(is_string($value))
	{
		print htmlspecialchars($value);
	}
	elseif(is_array($value))
		theme('info_objects', $value);
}

function theme__form_object($field_name, $config)
{
	// do some checks for convenience
	
	// check that the field_name hasn't been used before
	// check that all global keys are machine readable
	// check options keys when they are used
	
	
	// encode the settings
	$config_html = traverse_array($config);
	
	if(!isset($config['type']))
		$config['type'] = 'info';
	
	if(isset($config['name']) && $config['type'] != 'fieldset')
	{
		?><div class="fieldname <?php print isset($config['status'])?$config['status']:''; ?>"><?php
		theme('info_objects', $config['name']);
		?></div><?php
	}
	
	?><div class="field field_<?php print $config['type']; ?>"><?php
	
	switch($config['type'])
	{
		case 'form':
			?>
			<form name="<?php print htmlspecialchars($field_name, ENT_QUOTES); ?>" action="<?php print isset($config_html['action'])?$config_html['action']:$GLOBALS['output']['get']; ?>" method="<?php print isset($config_html['method'])?$config_html['method']:'post'; ?>">
				<?php
				theme('form_objects', $config['options']);
				
				if(isset($config['submit']))
					theme('form_submit', $config['submit']);
				if(isset($config['reset']))
					theme('form_submit', $config['reset']);
			
			?></form><?php
		break;
		case 'fieldset':
			if(isset($config['name']) || count($config['options']) > 0)
			{
				?><fieldset id="fieldset_<?php print machine($field_name); ?>"><?php
				
				if(isset($config['name']))
				{
					?><legend><?php
					if(isset($config['collapsible']) && $config['collapsible'] == true)
					{
						$id = htmlspecialchars($field_name, ENT_QUOTES);
						$script = "if($('#fieldcontainer_" . $id . "').css('display')=='none'){" . 
							"$('#fieldcontainer_" . $id . "').slideDown('fast');" . 
						"}else{" .
							"$('#fieldcontainer_" . $id . "').slideUp('fast');" . 
						"}return false;";
						?><a href="#" onclick="<?php print $script; ?>"><?php print htmlspecialchars($config['name']); ?></a><?php
					}
					else
					{
						print $config['name'];
					}
					?></legend><?php
				}
				
				?><div id="fieldcontainer_<?php print machine($field_name); ?>" <?php print (isset($config['collapsed']) && $config['collapsed'] == true)?'style="display:none;"':''; ?>><?php
				
					theme('form_fieldrows', $config['options']);
				
				?></div><?php
				
				?></fieldset><?php
			}
		break;
		case 'set':
			// This provides an API for submitting multiple fields to an associative array
			theme('form_objects', $config['options']);
		break;
		case 'info':
			if(!isset($config['value']))
				theme('info_objects', $config);
			else
				theme('info_objects', $config['value']);
		break;
		case 'theme':
			theme('form_' . $config['value'], array('name' => $field_name) + $config);
		break;
		default:
			if(theme_implements('form_' . $config['type']))
			{
				theme('form_' .  $config['type'], array('name' => $field_name) + $config);
			}
			else
				raise_error('Form type \'' . $config['type'] . '\' not found!', E_DEBUG);
	}
	?></div><?php
	
	// print description
	if(isset($config['description']))
	{
		?><div class="fielddesc"><?php
		theme('info_objects', $config['description']);
		?></div><?php
	}
	
}

function theme__form_fieldrows($options)
{
	foreach($options as $name => $field)
	{
		?><div class="field_row" id="row_<?php print $name; ?>"><?php
		theme('form_object', $name, $field);
		?></div><?php
	}
}

function theme__form_objects($form)
{
	// generate form based on config spec
	foreach($form as $field_name => $config)
	{
		// provide API for switching back to info objects
		if(!is_array($config))
		{
			theme('info_objects', $config);
		}
		else
		{
			theme('form_object', $field_name, $config);
		}
	}
}

function theme__form_hidden($config)
{
	extract($config);
	?><input type="hidden" name="<?php print machine($name); ?>" value="<?php print htmlspecialchars($value); ?>" /><?php
}

function theme__form_button($config)
{
	extract($config);
	?><input type="button" <?php print isset($action)?'onClick="' . $action . '"':''; ?> <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print isset($name)?machine($name):''; ?>" value="<?php print htmlspecialchars($value); ?>" /><?php
}

function theme__form_submit($config)
{
	extract($config);
	?><input type="submit" <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>" value="<?php print htmlspecialchars($value); ?>" /><?php
}

function theme__form_filesize($config)
{
	extract($config);
	?>
		<select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>[value]" style="width:150px; display:inline; margin-right:0px;">
		<?php
		for($i = 0; $i < 10; $i++)
		{
			?><option value="<?php echo pow(2, $i); ?>" <?php print ($value / 1024 == pow(2, $i) || $value / 1048576 == pow(2, $i) || $value / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php print pow(2, $i); ?></option><?php
		}
		?>
		</select><select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>[multiplier]" style="width:50px; display:inline; margin-right:0px;">
			<option value="1024" <?php print ($value / 1024 >= 1 && $value / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
			<option value="1048576" <?php print ($value / 1048576 >= 1 && $value / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
			<option value="1073741824" <?php print ($value / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
		</select>
	<?php
}

function theme__form_multiselect($config)
{
	extract($config);
	?><select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>" size="5" multiple="multiple"><?php
	// check if array is associative or not
	if(!is_array($value))
		$value = array($value);
	if(array_keys($options) === array_keys(array_keys($options)) && (!isset($force_numeric) || $force_numeric == false))
	{
		// numeric keys
		foreach($options as $i => $option)
		{
			?><option value="<?php print htmlspecialchars($option); ?>" <?php print (in_array($option, $value))?'selected="selected"':''; ?>><?php print htmlspecialchars($option); ?></option><?php
		}
	}
	else
	{
		// named keys
		foreach($options as $option => $text)
		{
			?><option value="<?php print htmlspecialchars($option); ?>" <?php print (in_array($option, $value))?'selected="selected"':''; ?>><?php print htmlspecialchars($text); ?></option><?php
		}
	}
	?></select><?php
}

function theme__form_boolean($config)
{
	extract($config);
	?>
	<select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>">
		<option value="true" <?php print ($value == true)?'selected="selected"':''; ?>><?php print isset($options[0])?htmlspecialchars($options[0]):'true'; ?></option>
		<option value="false" <?php print ($value == false)?'selected="selected"':''; ?>><?php print isset($options[1])?htmlspecialchars($options[1]):'false'; ?></option>
	</select>
	<?php
}

function theme__form_time($config)
{
	extract($config);
	?>
	<select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>[value]" style="width:100px; display:inline; margin-right:0px;">
	<?php
	for($i = 1; $i < 60; $i++)
	{
		?><option value="<?php print $i; ?>" <?php print ($value == $i || $value / 60 == $i || $value / 360 == $i)?'selected="selected"':''; ?>><?php print $i; ?></option><?php
	}
	?>
	</select>
	<select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>[multiplier]" style="width:100px; display:inline; margin-right:0px;">
		<option value="1" <?php print ($value >= 1 && $value < 60)?'selected="selected"':''; ?>>Seconds</option>
		<option value="60" <?php print ($value / 60 >= 1 && $value / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
		<option value="360" <?php print ($value / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
	</select>
	<?php
}

function theme__form_select($config)
{
	extract($config);
	?><select <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> name="<?php print machine($name); ?>"><?php
	// check if array is associative or not
	if(array_keys($options) === array_keys(array_keys($options)) && (!isset($force_numeric) || $force_numeric == false))
	{
		// numeric keys
		foreach($options as $i => $option)
		{
			?><option value="<?php print htmlspecialchars($option); ?>" <?php print ($value == $option)?'selected="selected"':''; ?>><?php print htmlspecialchars($option); ?></option><?php
		}
	}
	else
	{
		// named keys
		foreach($options as $option => $text)
		{
			?><option value="<?php print htmlspecialchars($option); ?>" <?php print ($value == $option)?'selected="selected"':''; ?>><?php print htmlspecialchars($text); ?></option><?php
		}
	}
	?></select><?php
}

function theme__form_radio($config)
{
	theme('form_checkbox', $config);
}

function theme__form_checkbox($config)
{
	extract($config);
	// check if array is associative or not
	if(array_keys($options) === array_keys(array_keys($options)) && (!isset($force_numeric) || $force_numeric == false))
	{
		// numeric keys
		foreach($options as $i => $option)
		{
			?><input <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> <?php print ($value == $option)?'checked="checked"':''; ?> type="<?php print $type; ?>" value="<?php print htmlspecialchars($option); ?>" name="<?php print machine($name) . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print htmlspecialchars($option); ?><br /><?php
		}
	}
	else
	{
		// named keys
		foreach($options as $option => $text)
		{
			?><input <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> <?php print ($value == $option)?'checked="checked"':''; ?> type="<?php print $type; ?>" value="<?php print htmlspecialchars($option); ?>" name="<?php print machine($name) . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print htmlspecialchars($text); ?><br /><?php
		}
	}
}

function theme__form_text($config)
{
	extract($config);
	?><input <?php print (isset($disabled) && $disabled == true)?'disabled="disabled"':'';?> type="text" value="<?php print htmlspecialchars($value); ?>" name="<?php print machine($name); ?>" /><?php
}

function theme__form_label($config)
{
	?><label><?php print htmlspecialchars($config['value']); ?></label><?php
}
