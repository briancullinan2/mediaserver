<?php

function register_plain_xml()
{
	return array(
		'name' => 'XML Info',
		'file' => __FILE__,
		'encoding' => 'XML'
	);
}

function theme_plain_xml()
{
	$ext_icons = array();
	$ext_icons['FOLDER'] = href('template=' . HTML_TEMPLATE . '&file=images/filetypes/folder_96x96.png');
	$ext_icons['FILE'] = href('template=' . HTML_TEMPLATE . '&file=images/filetypes/file_96x96.png');
	
	$type_icons = array();
	$type_icons['audio'] = href('template=' . HTML_TEMPLATE . '&file=images/filetypes/music_96x96.png');

	print '<?xml version="1.0" encoding="utf-8"?>';
	
	?><request><?php
	
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><success>false</success>
		<error><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			print $error->message . "\n";
		}
		?><error><?php
	}
	?><count><?php print $GLOBALS['templates']['html']['total_count']; ?></count><?php
	foreach($GLOBALS['templates']['html']['files'] as $i => $file)
	{
		?>
		<file>
			<index><?php print $GLOBALS['templates']['vars']['start'] + $i; ?></index>
			<id><?php print $file['id']; ?></id>
			<name><?php print $file['Filename']; ?></name>
			<text><?php print $file['Filename']; ?></text>
			<?php
			$type_arr = split('/', $file['Filemime']);
			$type = $type_arr[0];
			?><icon><?php print isset($ext_icons[$file['Filetype']])?$ext_icons[$file['Filetype']]:(isset($type_icons[$type])?$type_icons[$type]:$ext_icons['FILE']); ?></icon>
			<ext><?php print $file['Filetype']; ?></ext>
			<tip><?php
			foreach($GLOBALS['columns'] as $i => $column)
			{
				if(isset($file[$column]))
				{
					print $column . ': ' . $file[$column] . '&lt;br /&gt;';
				}
			}
			?></tip>
			<path><?php print $file['Filepath']; ?></path>
			<link><?php print href('plugin=file&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&filename=' . urlencode($file['Filename']), false, true); ?></link>
			<short><?php print htmlspecialchars(substr($GLOBALS['templates']['vars']['files'][$i]['Filename'], 0, 13)); ?>...</short>
			<?php
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'archive'))
			{
				?><cat>archive</cat><?php
			}
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'diskimage'))
			{
				?><cat>diskimage</cat><?php
			}
			else
			{
				?><cat><?php print $GLOBALS['templates']['html']['cat']; ?></cat><?php
			}
			
			foreach($GLOBALS['columns'] as $i => $column)
			{
					?><info-<?php print $column; ?>><?php print isset($file[$column])?$file[$column]:''; ?></info-<?php print $column; ?>><?php
			}
			?>
		</file>
		<?php
	}
	
	?></request><?php
}