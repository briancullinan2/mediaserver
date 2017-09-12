<?php

function get_form($name, $request = array())
{
	if(function_exists('form_' . $name))
	{
		return call_user_func_array('form_' . $name, array($request));
	}
}


function get_output_form($form)
{
	return ob_theme('form', $form);
}

function escape($text, $type = 'not plain')
{
	if($text instanceof translation)
		if($type === T_NO_SPAN)
			return $text->translation;
		elseif($type === T_IN_ATTRIBUTE)
		{
			$in_attr = $text->in_attribute;
			$text->in_attribute = true;
			$return = '' . $text;
			$text->in_attribute = $in_attr;
			return $return;
		}
		else
			return '' . $text;
	elseif(is_array($text))
		return implode("\n", array_map('escape', $text));
	else
		return htmlspecialchars($text,ENT_QUOTES,'UTF-8');
}

function theme__form($form)
{
}


function theme__info_objects($infos)
{
	// don't use is_string because we want to account for translations
	if(!is_array($infos))
	{
		print escape($infos);
	}
	else
	{
		foreach($infos as $key => $value)
		{
			if(is_numeric($key))
			{
				// treat value like sub info object
				if(is_array($value))
					theme('info_objects', $value);
				else
					print escape($value) . '<br />';
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
	?><span><?php print escape($value); ?>: </span><?php
}

function theme__info_loading($value)
{
	?><img src="<?php print url('templates/live/images/large-loading.gif'); ?>" alt="loading" /><?php print escape($value); ?><?php
}

function theme__info_image($value)
{
	?><img src="<?php print escape($value); ?>" alt="image" /><?php
}

function theme__info_literal($value)
{
	print $value;
}

function theme__info_list($value)
{
	?><ul><?php
	if(is_array($value))
	{
		foreach($value as $key => $text)
		{
			?><li><?php theme('info_objects', array($key => $text)); ?></li><?php
		}
	}
	else
	{
		?><li><?php print escape($value); ?></li><?php
	}
	?></ul><?php
}

function theme__info_link($value)
{
	if(is_string($value))
	{
		?><a href="<?php print url($value); ?>"><?php print escape($value); ?></a><?php
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
	if(is_array($value))
		theme('info_objects', $value);
	else
		print escape($value);
}

function theme__form_object($field_name, $config)
{
	// do some checks for convenience

	// check that the field_name hasn't been used before
	// check that all global keys are machine readable
	// check options keys when they are used

	if(!isset($config['type']))
		$config['type'] = 'info';

	if(isset($config['name']) && $config['type'] != 'fieldset')
	{
		?><div class="fieldname <?php print escape(isset($config['status'])?$config['status']:''); ?> <?php print !isset($config['description']) ? 'expand' : ''; ?>"><?php
		theme('info_objects', $config['name']);
		?></div><?php
	}

	?><div class="field field_<?php print $config['type']; ?> <?php print !isset($config['description']) ? 'expand' : ''; ?>"><?php

	switch($config['type'])
	{
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
			theme('form_' . $config['value'], array('field' => $field_name) + $config);
		break;
		default:
			if(theme_implements('form_' . $config['type']))
			{
				theme('form_' .  $config['type'], array('field' => $field_name) + $config);
			}
			else
			{
				raise_error('Form type \'' . $config['type'] . '\' not found!', E_DEBUG);
			}
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

function theme__form_form($config)
{
	$config_html = traverse_array($config);
	?>
	<form name="<?php print escape($config['field']); ?>" action="<?php print isset($config_html['action'])?$config_html['action']:$GLOBALS['output']['get']; ?>" method="<?php print (isset($config_html['method'])?$config_html['method']:'post'); ?>">
		<?php
		theme('form_objects', $config['options']);

		if(isset($config['submit']))
			theme('form_submit', array('field' => $config['submit']['name']) + $config['submit']);
		if(isset($config['reset']))
			theme('form_submit', array('field' => $config['reset']['name']) + $config['reset']);

	?></form><?php
}

function theme__form_fieldset($config)
{
	extract($config);
	if(isset($name) || count($options) > 0)
	{
		?><fieldset id="fieldset_<?php print machine($field); ?>"><?php

			if(isset($name))
			{
				?><legend><?php
				if(isset($collapsible) && $collapsible)
				{
					$id = escape($field);
					$script = "if($('#fieldcontainer_" . $id . "').css('display')=='none'){" .
						"$('#fieldcontainer_" . $id . "').slideDown('fast');" .
					"}else{" .
						"$('#fieldcontainer_" . $id . "').slideUp('fast');" .
					"}return false;";
					?><a href="#" onclick="<?php print $script; ?>"><?php print escape($name); ?></a><?php
				}
				else
				{
					print escape($name);
				}
				?></legend><?php
			}

			?><div id="fieldcontainer_<?php print machine($field); ?>" <?php print (isset($collapsed) && $collapsed?'style="display:none;"':''); ?>><?php

				theme('form_fieldrows', $options);

			?></div><?php

		?></fieldset><?php
	}
}

function theme__form_fieldrows($options)
{
	foreach($options as $name => $field)
	{
		?><div class="field_row <?php print isset($field['status']) ? $field['status'] : ''; ?>" id="row_<?php print $name; ?>"><?php
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
	?><input type="hidden" name="<?php print machine($field); ?>" value="<?php print escape($value); ?>" /><?php
}

function theme__form_button($config)
{
	extract($config);
	?><input type="button" <?php print isset($action)?'onClick="' . $action . '"':''; ?> <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print isset($name)?machine($field):''; ?>" value="<?php print escape($value, T_IN_ATTRIBUTE); ?>" /><?php
}

function theme__form_submit($config)
{
	extract($config);
	?><input type="submit" <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>" value="<?php print escape($value); ?>" /><?php
}

function theme__form_filesize($config)
{
	extract($config);
	?>
		<select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>[value]" style="width:150px; display:inline; margin-right:0px;">
		<?php
		for($i = 0; $i < 10; $i++)
		{
			?><option value="<?php echo pow(2, $i); ?>" <?php print ($value / 1024 == pow(2, $i) || $value / 1048576 == pow(2, $i) || $value / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php print pow(2, $i); ?></option><?php
		}
		?>
		</select><select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>[multiplier]" style="width:50px; display:inline; margin-right:0px;">
			<option value="1024" <?php print ($value / 1024 >= 1 && $value / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
			<option value="1048576" <?php print ($value / 1048576 >= 1 && $value / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
			<option value="1073741824" <?php print ($value / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
		</select>
	<?php
}

function theme__form_multiselect($config)
{
	extract($config);
	?><select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>" size="5" multiple="multiple"><?php
	// check if array is associative or not
	if(!is_array($value))
		$value = array($value);
	foreach($options as $option => $text)
	{
		?><option value="<?php print htmlspecialchars($option); ?>" <?php print (in_array($option, $value))?'selected="selected"':''; ?>><?php print escape($text); ?></option><?php
	}
	?></select><?php
}

function theme__form_ordered($config)
{
	extract($config);
	$current = 0;
	?><input name="<?php print machine($field); ?>" type="hidden" value="<?php print implode(',', array_keys($options)); ?>" /><?php
	foreach($options as $option => $text)
	{
		?><div><?php print escape($text); ?>

		<?php
		if($current > 0)
		{
			$switched = array_keys($options);
			$temp = $switched[$current]; $switched[$current] = $switched[$current-1]; $switched[$current-1] = $temp;
			?><button type="submit" name="<?php print machine($field); ?>" value="<?php print implode(',', $switched); ?>" <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?>><?php print lang('Up', 'form up'); ?></button><?php
		}

		if($current < count($options)-1)
		{
			$switched = array_keys($options);
			$temp = $switched[$current]; $switched[$current] = $switched[$current+1]; $switched[$current+1] = $temp;
			?><button type="submit" name="<?php print machine($field); ?>" value="<?php print implode(',', $switched); ?>" <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?>><?php print lang('Down', 'form down'); ?></button><?php
		}

		?></div><?php

		$current++;
	}
}

function theme__form_boolean($config)
{
	extract($config);
	?>
	<select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>">
		<option value="true" <?php print ($value)?'selected="selected"':''; ?>><?php print isset($options[0])?escape($options[0]):'true'; ?></option>
		<option value="false" <?php print (!$value)?'selected="selected"':''; ?>><?php print isset($options[1])?escape($options[1]):'false'; ?></option>
	</select>
	<?php
}

function theme__form_time($config)
{
	extract($config);
	?>
	<select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>[value]" style="width:100px; display:inline; margin-right:0px;">
	<?php
	for($i = 1; $i < 60; $i++)
	{
		?><option value="<?php print $i; ?>" <?php print ($value == $i || $value / 60 == $i || $value / 360 == $i)?'selected="selected"':''; ?>><?php print $i; ?></option><?php
	}
	?>
	</select>
	<select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>[multiplier]" style="width:100px; display:inline; margin-right:0px;">
		<option value="1" <?php print ($value >= 1 && $value < 60)?'selected="selected"':''; ?>>Seconds</option>
		<option value="60" <?php print ($value / 60 >= 1 && $value / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
		<option value="360" <?php print ($value / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
	</select>
	<?php
}

function theme__form_select($config)
{
	extract($config);
	?><select <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> name="<?php print machine($field); ?>"><?php
	foreach($options as $option => $text)
	{
		?><option value="<?php print escape($option); ?>" <?php print ($value == $option)?'selected="selected"':''; ?>><?php print escape($text); ?></option><?php
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
	$counter = 0;
	foreach($options as $option => $text)
	{
		?><input <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> <?php print (is_string($value) && $value == $option) || (is_array($value) && in_array($option, $value)) ? 'checked="checked"' : ''; ?> type="<?php print $type; ?>" value="<?php print escape($option); ?>" name="<?php print machine($field) . (($config['type'] == 'checkbox')?'[]':''); ?>" id="<?php print machine($field) . '_' . $counter; ?>" />
			<label for="<?php print machine($field) . '_' . $counter; ?>"><?php print htmlspecialchars($text); ?></label><?php
		$counter++;
	}
}

function theme__form_text($config)
{
	extract($config);
	?><input <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> type="text" value="<?php print escape($value); ?>" name="<?php print machine($field); ?>" /><?php
}

function theme__form_file($config)
{
	extract($config);
	?><input <?php print (isset($disabled) && $disabled)?'disabled="disabled"':'';?> type="file" value="<?php print escape($value); ?>" name="<?php print machine($field); ?>" /><?php
}

function theme__form_label($config)
{
	?><label><?php print escape($config['value']); ?></label><?php
}
