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

function cli_box($input, $top_line = true, $left_line = true, $bottom_line = true, $right_line = true)
{
	$lines = split("\n", $input);
	$max = cli_get_max_width($lines);
	$result = '';
	if($top_line)
		$result .= (($left_line)?'|':'') . '-' . sprintf('%\'--' . ($max+2) . 's', '-') . (($right_line)?'|':'') . "\n";
	foreach($lines as $i => $line)
	{
		$result .= (($left_line)?'|':'') . ' ' . sprintf('%-' . ($max+2) . 's', $line) . (($right_line)?'|':'') . "\n";
	}
	if($bottom_line)
		$result .= (($left_line)?'|':'') . '_' . sprintf('%\'_-' . ($max+2) . 's', '_') . (($right_line)?'|':'') . "\n";
	return $result;
}

function cli_merge_boxes($box1, $box2)
{
	$lines1 = split("\n", $box1);
	array_pop($lines1);
	$last1 = array_pop($lines1);
	$lines2 = split("\n", $box2);
	array_pop($lines2);
	$last2 = array_pop($lines2);
	$result = '';
	for($i = 0; $i < max(count($lines1), count($lines2)); $i++)
	{
		if(isset($lines1[$i]))
			$result .= $lines1[$i];
		else
			$result .= '|' . sprintf('%-' . (strlen($lines1[0])-2) . 's', ' ') . '|';
			
		if(isset($lines2[$i]))
			$result .= $lines2[$i] . "\n";
		else
			$result .= '|' . sprintf('%-' . (strlen($lines2[0])-2) . 's', ' ') . "|\n";
	}
	$result .= $last1 . $last2 . "\n";
	return $result;
}

function theme_cli_info_list($value)
{
	if(is_string($value))
	{
		print '*' . htmlspecialchars($value) . "\n";
	}
	elseif(is_array($value))
	{
		foreach($value as $key => $text)
		{
			print '*';
			theme('info_objects', array($key => $text));
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
		'Module: ' . $GLOBALS['modules'][$GLOBALS['output']['module']]['name'],
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
	switch($config['type'])
	{
		case 'form':
			$column_lengths = cli_get_columns_lengths($config['options']);
			theme('form_objects', $config['options'], $column_lengths['name']);
			break;
		case 'fieldset':
			if(isset($config['name']))
			{
				// box it up
				ob_start();
				$table_width = theme('form_tableset', $config['options'], $width);
				$contents = ob_get_contents();
				ob_end_clean();
				print '|-' . sprintf('%\'--' . max($width, $table_width) . 's', $config['name']) . "|\n";
				print $contents;
				print '|_' . sprintf('%\'_-' . max($width, $table_width) . 's', '_') . "|\n";
			}
			break;
	}
}

function theme_cli_info_objects($infos)
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
					print htmlspecialchars($value) . "\n";
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
							break;
						}
					default:
						if(function_exists('theme__info_' . $key))
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

function theme_cli_form_objects($form, $width = 0)
{
	// generate form based on config spec
	foreach($form as $field_name => $config)
	{
		// provide API for switching back to info objects
		if(!is_array($config))
		{
			theme('info_objects', $config);
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

function theme_cli_form_tableset($options, $width = 0)
{
	$column_lengths = cli_get_columns_lengths($options);
	
	$op_count = 0;
	foreach($options as $name => $field)
	{
		$op_count++;
		$box1 = cli_box(sprintf('%-' . max($width, $column_lengths['name']) . 's', $field['name']), false);
		// box it up
		ob_start();
		$desc_width = theme('info_objects', $field['description']);
		$box2 = cli_box(ob_get_contents(), false, false);
		ob_end_clean();
		print cli_merge_boxes($box1, $box2);
	}
	
	return $column_lengths['name']+2;
}

function theme_cli_select()
{
	theme('header',
		'Select Files',
		$GLOBALS['modules']['files']['description']
	);
	
	// go through files ahead of time and make them monospaced
	$column_lengths = cli_get_columns_lengths($GLOBALS['output']['files']);
	
	printf('%-' . ($column_lengths['Filepath']+2) . 's', 'Filepath');
	cli_print_monospace();
	foreach(get_columns('files') as $i => $column)
	{
		if($column == 'Filepath') continue;
		
		print ' | ' . sprintf('%-' . ($column_lengths[$column]+2) . 's', $column);
	}
	print "\n";
	
	foreach($GLOBALS['output']['files'] as $i => $file)
	{
		printf('%-' . ($column_lengths['Filepath']+2) . 's', $file['Filepath']);
		foreach(get_columns('files') as $j => $column)
		{
			if($column == 'Filepath')
				continue;
			
			print ' | ';
			if(isset($file[$column]))
			{
				printf('%-' . ($column_lengths[$column]+2) . 's', $file[$column]);
			}
		}
		print "\n";
	}
}

