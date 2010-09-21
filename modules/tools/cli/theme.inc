<?php

function cli_get_columns_lengths($input)
{
	$column_lengths = array();
	foreach($input as $i => $file)
	{
		// find the longest string for each column
		foreach($file as $column => $value)
		{
			if(!is_array($value))
				$column_lengths[$column] = max(isset($column_lengths[$column])?$column_lengths[$column]:0, strlen($value), strlen($column));
		}
	}
	
	return $column_lengths;
}

function cli_get_max_width($input)
{
	$max = 0;
	foreach($input as $column => $value)
	{
		if(is_string($value))
			$max = max($max, strlen($value), strlen($column));
	}
	return $max;
}

function cli_box($input, $top_line = true, $left_line = true, $bottom_line = true, $right_line = true, $width = 0)
{
	$lines = split("\n", $input);
	$max = cli_get_max_width($lines);
	if($width > 0 && $width > $max)
		$max = $width;
	$result = '';
	if($top_line)
		$result .= (($left_line)?'.-':'') . sprintf('%\'--' . ($max+2) . 's', '-') . (($right_line)?'.':'') . "\n";
	foreach($lines as $i => $line)
	{
		$result .= (($left_line)?'| ':'') . sprintf('%-' . ($max+2) . 's', $line) . (($right_line)?'|':'') . "\n";
	}
	if($bottom_line)
		$result .= (($left_line)?'|_':'') . sprintf('%\'_-' . ($max+2) . 's', '_') . (($right_line)?'|':'') . "\n";
	return $result;
}

function cli_merge_boxes()
{
	$boxes = func_get_args();
	$lines = array();
	$lasts = array();
	$max_lines = 0;
	foreach($boxes as $i => $box)
	{
		$lines[$i] = split("\n", $box);
		array_pop($lines[$i]);
		$lasts[$i] = array_pop($lines[$i]);
		$max_lines = max(count($lines[$i]), $max_lines);
	}
	$result = '';
	for($i = 0; $i < $max_lines; $i++)
	{
		foreach($lines as $j => $box)
		{
			if(isset($box[$i]))
				$result .= $box[$i];
			else
			{
				$left_border = ((substr($lines[$j][0], 0, 1) == '|')?'|':'');
				$right_border = ((substr($lines[$j][0], -1) == '|')?'|':'');
				$result .= $left_border . sprintf('%-' . (strlen($lines[$j][0])-strlen($left_border)-strlen($right_border)) . 's', ' ') . $right_border;
			}
		}
		$result .= "\n";
	}
	
	foreach($lines as $j => $box)
	{
		$result .= $lasts[$j];
	}
	
	$result .= "\n";
	return $result;
}

function cli_wordwrap($text, $width)
{
	if(strlen($text) == 0)
		return '';
	else
		return wordwrap($text, $width, "\n", true);
}

function theme_cli_info_list($value, $width)
{
	if(is_string($value))
	{
		print '*' . cli_wordwrap($value, $width) . "\n";
	}
	elseif(is_array($value))
	{
		foreach($value as $key => $text)
		{
			print '*';
			theme('info_objects', array($key => $text), $width);
		}
	}
}

function theme_cli_errors_block()
{
	if(count($GLOBALS['user_errors']) > 0)
		foreach($GLOBALS['user_errors'] as $i => $error)
			print 'Error: ' . $error . "\n";
			
	if(count($GLOBALS['warn_errors']) > 0)
		foreach($GLOBALS['warn_errors'] as $i => $error)
			print 'Warning: ' . $error . "\n";
			
	if(count($GLOBALS['note_errors']) > 0)
		foreach($GLOBALS['note_errors'] as $i => $error)
			print 'Note: ' . $error . "\n";
}

function theme_cli_errors()
{
	theme('header',
		'Module: ' . get_module($GLOBALS['output']['module'], 'name'),
		'There were errors while processing your request.'
	);

	theme('footer');
}

function theme_cli_footer()
{
}

function theme_cli_header($title = NULL, $description = NULL)
{
	print $title . "\n";
	print 'Description: ' . $description . "\n";
	theme('errors_block');
}

function theme_cli_form_object($field_name, $config, $width = 0)
{
	if($width == 0)
		$width = setting('cli_columns');
	
	if(isset($config['name']) && $config['type'] != 'fieldset')
	{
		theme('info_objects', $config['name'] . ":\n", $width);
	}
		
	switch($config['type'])
	{
		case 'form':
			theme('form_objects', $config['options'], $width);
			if(isset($config['submit']))
				theme('form_submit', $config['submit'], $width);
			if(isset($config['reset']))
				theme('form_submit', $config['reset'], $width);
			break;
		case 'fieldset':
			if(isset($config['name']))
			{
				// box it up
				ob_start();
				theme('form_tableset', $config['options'], $width - 4);
				$contents = ob_get_contents();
				ob_end_clean();
				$title = '.-' . $config['name'];
				$box1 = cli_box($contents);
				print $title . substr($box1, strlen($title)) . "\n";
			}
			break;
		case 'set':
			// This provides an API for submitting multiple fields to an associative array
			theme('form_objects', $config['options'], $width);
			break;
		case 'info':
			if(!isset($config['value']))
				theme('info_objects', $config, $width);
			else
				theme('info_objects', $config['value'], $width);
			break;
		case 'theme':
			theme('form_' . $config['value'], array('name' => $field_name) + $config, $width);
			break;
		default:
			if(theme_implements('form_' . $config['type'], 'cli'))
			{
				theme('form_' .  $config['type'], array('name' => $field_name) + $config, $width);
			}
			else
				raise_error('Form type \'' . $config['type'] . '\' not found!', E_DEBUG);
	}
}

function theme_cli_info_objects($infos, $width)
{
	if(is_string($infos))
	{
		if(strlen($infos) < 256)
			print cli_wordwrap(str_replace('<br />', "\n", $infos), $width);
		else
			print cli_wordwrap(str_replace('<br />', "\n", substr($infos, 0, 256) . "...\n"), $width);
	}
	else
	{
		foreach($infos as $key => $value)
		{
			if(is_numeric($key))
			{
				if(is_string($value))
				{
					print cli_wordwrap($value, $width) . "\n";
				}
				// treat value like sub info object
				elseif(is_array($value))
					theme('info_objects', $value, $width);
			}
			else
			{
				switch($key)
				{
					case 'type':
						if($infos['type'] != 'info')
						{
							theme('form_objects', array($infos), $width);
							break;
						}
						break;
					case 'name':
						break;
					default:
						if(theme_implements('info_' . $key, 'cli'))
						{
							theme('info_' . $key, $value, $width);
						}
						else
							raise_error('Info type \'' . $key . '\' not found!', E_DEBUG);
				}
			}
		}
	}
}

function theme_cli_form_objects($form, $width)
{
	// generate form based on config spec
	foreach($form as $field_name => $config)
	{
		// provide API for switching back to info objects
		if(!is_array($config))
		{
			theme('info_objects', $config, $width);
		}
		elseif(!isset($config['type']))
		{
			theme('form_object', $field_name, array('type' => 'info') + $config, $width);
		}
		else
		{
			theme('form_object', $field_name, $config, $width);
		}
	}
}

function theme_cli_form_tableset($options, $width)
{
	$op_count = 0;
	foreach($options as $name => $field)
	{
		$op_count++;
		$box1 = cli_box(cli_wordwrap($field['name'], floor($width * .20) - 4), false, false, true, true, floor($width * .20));
		
		ob_start();
		if(is_array($field))
			theme('form_objects', array($name => array('name' => NULL, 'description' => NULL) + $field), floor($width * .30) - 2);
		else
			theme('form_objects', array($name => array('name' => NULL, 'description' => NULL, 'value' => $field)), floor($width * .30) - 2);
		$box2 = cli_box(ob_get_contents(), false, false, true, false, floor($width * .30));
		ob_end_clean();
	
		// box it up
		ob_start();
		theme('info_objects', $field['description'], floor($width * .30) - 2);
		$box3 = cli_box(ob_get_contents(), false, true, true, false, floor($width * .30));
		ob_end_clean();
		print cli_merge_boxes($box1, $box2, $box3);
	}
}

function theme_cli_form_boolean($config, $width)
{
	extract($config);
	if($value == true)
		print '[' . (isset($options[0])?cli_wordwrap(machine($name) . ' = ' . $options[0], $width):'true') . ']';
	else
		print isset($options[0])?cli_wordwrap($options[0], $width):'true';

	print "\n";
		
	if($value == false)
		print '[' . (isset($options[1])?wordwrap(machine($name) . ' = ' . $options[1], $width):'false') . ']';
	else
		print isset($options[1])?cli_wordwrap($options[1], $width):'false';
		
	print "\n";
}

function theme_cli_form_text($config, $width)
{
	extract($config);
	print '[' . cli_wordwrap(machine($name) . ' = ' . $value, $width) . ']';
	print "\n";
}

function theme_cli_form_button($config, $width)
{
	extract($config);
	print '[' . cli_wordwrap($name . ' = ' . $value, $width) . ']';
	print "\n";
}

function theme_cli_form_submit($config, $width)
{
	extract($config);
	print '[' . cli_wordwrap($name . ' = ' . $value, $width) . ']';
	print "\n";
}

function theme_cli_form_hidden($config, $width)
{
}

function theme_cli_info_image($config, $width)
{
}


function theme_cli_form_multiselect($config, $width)
{
	extract($config);
	if(!is_array($value))
		$value = array($value);

	$box1 = '';
	// check if array is associative or not
	if(array_keys($options) === array_keys(array_keys($options)) && (!isset($force_numeric) || $force_numeric == false))
	{
		// numeric keys
		foreach($options as $i => $option)
		{
			if(in_array($option, $value))
				$box1 .= '[' . cli_wordwrap(machine($name) . ' = ' . $option, $width-1) . "]\n";
			else
				$box1 .= '[' . cli_wordwrap($option, $width-1) . "]\n";
		}
	}
	else
	{
		// named keys
		foreach($options as $option => $text)
		{
			if(in_array($option, $value))
				$box1 .= '[' . cli_wordwrap(machine($name) . ' = ' . $text, $width-1) . "]\n";
			else
				$box1 .= '[' . cli_wordwrap($text, $width-1) . "]\n";
		}
	}
	print cli_box($box1, true, true, true, true, $width-2);
}

function theme_cli_select()
{
	theme('header',
		'Select Files',
		get_module('files', 'description')
	);
	
	$width = setting('cli_columns');
	
	// go through files ahead of time and make them monospaced
	$column_lengths = cli_get_columns_lengths($GLOBALS['output']['files']);
	
	if(array_sum($column_lengths) > $width)
	{
		$box1 = cli_box('Filepath', true, true, true, true, floor($width * .30));
		$box2 = cli_box('File Info', true, false, true, true, floor($width * .60));
		print cli_merge_boxes($box1, $box2);
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			$box1 = cli_box(cli_wordwrap($file['Filepath'], floor($width * .30)-2), false, true, true, true, floor($width * .30));
			$box2 = '';
			foreach(get_all_columns() as $j => $column)
			{
				if($column == 'Filepath')
					continue;
				
				if(isset($file[$column]))
					$box2 .= cli_wordwrap($column . ': ' . $file[$column], floor($width * .60)-2) . "\n";
			}
			print cli_merge_boxes($box1, cli_box($box2, false, false, true, true, floor($width * .60)));
		}
	}
	else
	{
		$headings = array();
		$headings[] = cli_box('Filepath', true, true, true, true, $column_lengths['Filepath']);
		foreach(get_columns('files') as $i => $column)
		{
			if($column == 'Filepath') continue;
			$headings[] = cli_box($column, true, false, true, true, $column_lengths[$column]);
		}
		print call_user_func_array('cli_merge_boxes', $headings);
		
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			$file_boxes = array();
			$file_boxes[] = cli_box($file['Filepath'], false, true, true, true, $column_lengths['Filepath']);
			foreach(get_columns('files') as $j => $column)
			{
				if($column == 'Filepath')
					continue;
				
				$file_boxes[] = cli_box($file[$column], false, false, true, true, $column_lengths[$column]);
			}
			print call_user_func_array('cli_merge_boxes', $file_boxes);
		}
	}
}

