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

function cli_colorize($color)
{	
	array_push($GLOBALS['cli_colorize'], $color);
	print "\033[" . $color . "m";
}

function cli_decolorize()
{
	// remove current color
	array_pop($GLOBALS['cli_colorize']);
	
	// change to last color
	$color = array_pop($GLOBALS['cli_colorize']);
	print "\033[" . $color . "m";
	
	// save last color
	array_push($GLOBALS['cli_colorize'], $color);
}

function cli_get_max_width($lines)
{
	$max = 0;
	foreach($lines as $column => $value)
	{
		if(is_string($value))
			$max = max($max, strlen($value), strlen($column));
	}
	return $max;
}

function cli_box($input, $top_line = true, $left_line = true, $bottom_line = true, $right_line = true, $width = 0)
{
	$width = floor($width);
	$lines = explode("\n", $input);
	$max = cli_get_max_width(preg_replace('/\\033\[[0-9][0-9]?m/i', '', $lines));
	if($width > 0 && $width > $max)
		$max = $width;

	$result = '';
	if(is_string($top_line))
		$result .= (($left_line)?'.-':'') . sprintf('%\'--' . ($max) . 's', $top_line) . (($right_line)?'.':'') . "\n";
	elseif($top_line)
		$result .= (($left_line)?'.-':'') . sprintf('%\'--' . ($max) . 's', '-') . (($right_line)?'.':'') . "\n";
	foreach($lines as $i => $line)
	{
		$hidden_count = preg_match_all('/\\033\[[0-9][0-9]m/i', $line, $matches)*5+preg_match_all('/\\033\[[0-9]m/i', $line, $matches)*4;
		$result .= (($left_line)?'| ':'') . sprintf('%-' . ($max+$hidden_count) . 's', $line) . (($right_line)?'|':'') . "\n";
	}
	if($bottom_line)
		$result .= (($left_line)?'|_':'') . sprintf('%\'_-' . ($max) . 's', '_') . (($right_line)?'|':'') . "\n";
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
		if(!is_string($box))
		{
			unset($boxes[$i]);
			continue;
		}
		$lines[$i] = explode("\n", $box);
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
				$result .= $left_border . sprintf('%-' . (strlen(preg_replace('/\\033\[[0-9][0-9]?m/i', '', $lines[$j][0]))-strlen($left_border)-strlen($right_border)) . 's', ' ') . $right_border;
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
		return wordwrap($text, floor($width), "\n", true);
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
		{
			cli_colorize(31);
			print 'Error: ' . ob_theme('info_objects', $error)  . "\n";
			cli_decolorize();
		}
			
	if(count($GLOBALS['warn_errors']) > 0)
		foreach($GLOBALS['warn_errors'] as $i => $error)
		{
			cli_colorize(33);
			print 'Warning: ' . ob_theme('info_objects', $error) . "\n";
			cli_decolorize();
		}
		
	if(count($GLOBALS['note_errors']) > 0)
		foreach($GLOBALS['note_errors'] as $i => $error)
		{
			cli_colorize(34);
			print 'Note: ' . ob_theme('info_objects', $error) . "\n";
			cli_decolorize();
		}
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
	print "\033[0m";
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
	
	if(isset($config['status']) && $config['type'] != 'fieldset')
	{
		if($config['status'] == 'fail')
			cli_colorize(31);
		elseif($config['status'] == 'warn')
			cli_colorize(33);
		else
			cli_colorize(32);
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
			if(count($config['options']) > 0)
			{
				// box it up
				if(isset($config['name']))
					$title = '.-' . $config['name'];
				else
					$title = '';
				
				print cli_box(ob_theme('form_fieldrows', $config['options'], $width), $title, false, false, false, $width * .80);
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
	
	if(isset($config['description']))
		theme('info_objects', $config['description'], $width);
		
	if(isset($config['status']) && $config['type'] != 'fieldset')
		cli_decolorize();
}


function theme_cli_info_objects($infos, $width = 0)
{
	if($width == 0)
		$width = setting('cli_columns');
		
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
					case 'description':
					case 'status':
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

function theme_cli_info_text($value, $width)
{
	if(is_string($value))
	{
		print cli_wordwrap($value, $width);
	}
	elseif(is_array($value))
		theme('info_objects', $value, $width);
}

function theme_cli_info_link($value, $width)
{
	cli_colorize(34);
	if(is_string($value))
	{
		print cli_wordwrap('[' . $value . ']', $width);
	}
	elseif(is_array($value))
	{
		extract($value);
		print cli_wordwrap('[' . ob_theme('info_objects', $text) . ' = ' . $url . ']', $width);
	}
	cli_decolorize();
	print "\n";
}

function theme_cli_info_loading($value, $width)
{
	print cli_wordwrap($value, $width);
}

function theme_cli_info_image($config, $width)
{
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

function theme_cli_form_fieldrows($options, $width = 0)
{
	if($width == 0)
		$width = setting('cli_columns');
		
	$op_count = 0;
	foreach($options as $name => $field)
	{
		$op_count++;
		$box1 = cli_box(cli_wordwrap($field['name'], $width * .20 - 4), false, true, true, true, $width * .20);
		
		if(isset($field['description']))
			$inner_width = $width * .30;
		else
			$inner_width = $width * .60 + 2;
		
		if(is_array($field))
			$contents = ob_theme('form_objects', array($name => array('name' => NULL, 'description' => NULL, 'status' => NULL) + $field), $inner_width - 2);
		else
			$contents = ob_theme('form_objects', array($name => array('name' => NULL, 'description' => NULL, 'status' => NULL, 'value' => $field)), $inner_width - 2);
		$box2 = cli_box($contents, false, false, true, !isset($field['description']), $inner_width);

		if(isset($field['description']))
			$box3 = cli_box(ob_theme('info_objects', $field['description'], $width * .30 - 4), false, true, true, true, $width * .30);
		
		if(isset($field['status']))
		{
			if($field['status'] == 'fail')
				cli_colorize(31);
			elseif($field['status'] == 'warn')
				cli_colorize(33);
			else
				cli_colorize(32);
		}
		
		// box it up
		if(isset($field['description']))
			print cli_merge_boxes($box1, $box2, $box3);
		else
			print cli_merge_boxes($box1, $box2);
		
		if(isset($field['status']))
			cli_decolorize();
	}
}

function theme_cli_form_boolean($config, $width)
{
	extract($config);
	if($value)
		print cli_wordwrap('[' . (isset($options[0])?machine($name) . ' = ' . $options[0]:'true') . ']', $width) . "\n";
		
	if(!$value)
		print cli_wordwrap('[' . (isset($options[1])?machine($name) . ' = ' . $options[1]:'false') . ']', $width) . "\n";
}

function theme_cli_form_text($config, $width)
{
	extract($config);
	print cli_wordwrap('[' . machine($name) . ' = ' . $value . ']', $width);
	print "\n";
}

function theme_cli_form_button($config, $width)
{
	extract($config);
	print cli_wordwrap('[' . $name . ' = ' . $value . ']', $width);
	print "\n";
}

function theme_cli_form_submit($config, $width)
{
	extract($config);
	print cli_wordwrap('[' . $name . ' = ' . $value . ']', $width);
	print "\n";
}

function theme_cli_form_hidden($config, $width)
{
}


function theme_cli_form_select($config, $width)
{
	theme('form_multiselect', $config, $width);
}

function theme_cli_form_label($config, $width)
{
	print cli_wordwrap($config['value'], $width);
}

function theme_cli_form_multiselect($config, $width)
{
	extract($config);
	if(!is_array($value))
		$value = array($value);

	$box1 = '';
	// check if array is associative or not
	if(array_keys($options) === array_keys(array_keys($options)) && (!isset($force_numeric) || !$force_numeric))
	{
		// numeric keys
		foreach($options as $i => $option)
		{
			if(in_array($option, $value))
				$box1 .= cli_wordwrap('[' . machine($name) . ' = ' . $option . ']', $width-1) . "\n";
			else
				$box1 .= cli_wordwrap('[' . $option . ']', $width-1) . "\n";
		}
	}
	else
	{
		// named keys
		foreach($options as $option => $text)
		{
			if(in_array($option, $value))
				$box1 .= cli_wordwrap('[' . machine($name) . ' = ' . $text . ']', $width-1) . "\n";
			else
				$box1 .= cli_wordwrap('[' . $text . ']', $width-1) . "\n";
		}
	}
	print cli_box($box1, true, true, true, true, $width-2);
}


function theme_cli_menu()
{
	theme('header', 
		'Menu: ' . $GLOBALS['output']['current_menu']['name'],
		$GLOBALS['output']['current_menu']['description']
	);
	
	$width = setting('cli_columns');
	
	print cli_merge_boxes(
		cli_box('Name', true, true, true, true, $width * .30), 
		cli_box('Path', true, false, true, true, $width * .30), 
		cli_box('Description', true, false, true, true, $width * .30)
	);
	
	foreach($GLOBALS['output']['menu'] as $path => $config)
	{
		$box1 = cli_box(cli_wordwrap($config['name'], $width * .30), false, true, true, true, $width * .30);
		$box2 = cli_box(cli_wordwrap(url($path), $width * .30), false, false, true, true, $width * .30);
		$box3 = cli_box(cli_wordwrap($config['description'], $width * .30), false, false, true, true, $width * .30);

		print cli_merge_boxes($box1, $box2, $box3);
	}
	
	theme('footer');
}

function theme_cli_menu_block()
{
	if(isset($GLOBALS['output']['menus']))
	{
		print "Menu:\n";
		foreach($GLOBALS['output']['menus'] as $path => $config)
		{
			// this path actually leads to output as is, no need to validate it by using url()
			if($config['type'] == MENU_NORMAL || $config['type'] == MENU_LIST)
			{
				print $config['name'] . ' - ' . url($path) . "\n";
			}
		}
	}
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
		$box1 = cli_box('Filepath', true, true, true, true, $width * .30);
		$box2 = cli_box('File Info', true, false, true, true, $width * .60);
		print cli_merge_boxes($box1, $box2);
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			$box1 = cli_box(cli_wordwrap($file['Filepath'], $width * .30-2), false, true, true, true, $width * .30);
			$box2 = '';
			foreach(get_all_columns() as $j => $column)
			{
				if($column == 'Filepath')
					continue;
				
				if(isset($file[$column]))
					$box2 .= cli_wordwrap($column . ': ' . $file[$column], $width * .60-2) . "\n";
			}
			print cli_merge_boxes($box1, cli_box($box2, false, false, true, true, $width * .60));
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

function theme_cli_tool_info()
{
	global $cli_request_stack;
	theme('header',
		'Tools: ' . get_module($GLOBALS['output']['module'], 'name'),
		get_module($GLOBALS['output']['module'], 'description')
	);
	
	// output configuration if it is set
	if(isset($GLOBALS['output']['options']))
		theme('admin_modules_configure');
	
	theme('form_object', 'info', array(
		'action' => $GLOBALS['output']['html']['get'],
		'options' => $GLOBALS['output']['infos'],
		'type' => 'form',
	));

	// go through request queue and get more information since fetches can't be run without javascript
	while(count($cli_request_stack) > 0)
	{
		$request = url(array_pop($cli_request_stack), false, false, true);
		rewrite_vars($request, $get, $post);
		invoke_menu($request);
	}

	theme('footer');
}


function theme_cli_form_singular($config, $width)
{
	global $cli_request_stack;
	extract($config);
	theme('info_objects', array('type' => 'info') + $config, $width);
	
	array_push($cli_request_stack, $href);
}

