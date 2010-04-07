<?php

function register_plain_index()
{
	return array(
		'name' => 'Plain Index',
	);
}

function theme_plain_list()
{
	?>
    <div id="type">
        Get the list:
        <br />
        <form action="<?php print href('plugin=list'); ?>" method="get">
            <input type="hidden" name="cat" value="<?php print $GLOBALS['templates']['html']['cat']; ?>" />
            Type <select name="list">
            	<?php
				foreach($GLOBALS['lists'] as $type => $list)
				{
					?><option value="<?php print $type; ?>"><?php print $list['name']; ?></option><?php
				}
				?>
            </select>
            <input type="submit" value="Go" />
        </form>
    </div>
	<?php
}

function plain_alter_file($file, $column_lengths = NULL)
{
	foreach($file as $column => $value)
	{
		if(isset($column_lengths[$column]))
			$file[$column] = sprintf('%-' . ($column_lengths[$column]+2) . 's', $value);
		if(isset($GLOBALS['templates']['vars']['search_regexp']) && 
			isset($GLOBALS['templates']['vars']['search_regexp'][$column]))
			$file[$column] = preg_replace($GLOBALS['templates']['vars']['search_regexp'][$column], '\'<strong style="background-color:#990;">\' . str_replace(\' \', \'&nbsp;\', htmlspecialchars(\'$0\')) . \'</strong>\'', $file[$column]);
		else
			$file[$column] = str_replace(' ', '&nbsp;', htmlspecialchars($file[$column]));
	}
	return $file;
}

function theme_plain_pages()
{
	$item_count = count($GLOBALS['templates']['vars']['files']);
	$page_int = $GLOBALS['templates']['vars']['start'] / $GLOBALS['templates']['vars']['limit'];
	$lower = $page_int - 8;
	$upper = $page_int + 8;
	$GLOBALS['templates']['vars']['total_count']--;
	$pages = floor($GLOBALS['templates']['vars']['total_count'] / $GLOBALS['templates']['vars']['limit']);
	$prev_page = $GLOBALS['templates']['vars']['start'] - $GLOBALS['templates']['vars']['limit'];
	if($pages > 0)
	{
		if($lower < 0)
		{
			$upper = $upper - $lower;
			$lower = 0;
		}
		if($upper > $pages)
		{
			$lower -= $upper - $pages;
			$upper = $pages;
		}
		
		if($lower < 0)
			$lower = 0;
		
		if($GLOBALS['templates']['vars']['start'] > 0)
		{
			if($GLOBALS['templates']['vars']['start'] > $GLOBALS['templates']['vars']['limit'])
			{
			?>
			<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>">First</a>
			<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $prev_page); ?>">Prev</a>
			<?php
			}
			else
			{
			?>
			<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>">First</a>
			<?php
			}
			?> | <?php
		}
		
		for($i = $lower; $i < $upper + 1; $i++)
		{
			if($i == $page_int)
			{
				?><b><?php print $page_int + 1; ?></b><?
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . ($i * $GLOBALS['templates']['vars']['limit'])); ?>"><?php print $i + 1; ?></a>
				<?php
			}
		}
		
		if($GLOBALS['templates']['vars']['start'] <= $GLOBALS['templates']['vars']['total_count'] - $GLOBALS['templates']['vars']['limit'])
		{
			?> | <?php
			$last_page = floor($GLOBALS['templates']['vars']['total_count'] / $GLOBALS['templates']['vars']['limit']) * $GLOBALS['templates']['vars']['limit'];
			$next_page = $GLOBALS['templates']['vars']['start'] + $GLOBALS['templates']['vars']['limit'];
			if($GLOBALS['templates']['vars']['start'] < $GLOBALS['templates']['vars']['total_count'] - 2 * $GLOBALS['templates']['vars']['limit'])
			{
				?>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $next_page); ?>">Next</a>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
		}
	}
}

function theme_plain_template_block()
{
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print href('template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><br /><?php
		}
	}
}

function theme_plain_template()
{
	theme('template_block');
}

function theme_plain_files()
{
	if(count($GLOBALS['templates']['vars']['files']) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		$column_lengths = array();
		if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
		{
			// go through files ahead of time and make them monospaced
			foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
			{
				// find the longest string for each column
				foreach($file as $column => $value)
				{
					if(!isset($column_lengths[$column]) || strlen($value) > $column_lengths[$column])
						$column_lengths[$column] = strlen($value);
				}
			}
			?><code><?php
			
			?><input type="checkbox" name="item" value="All" /> <?php
			print str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths['Filepath']+2) . 's', 'Filepath'));
			foreach($GLOBALS['templates']['vars']['settings']['columns'] as $i => $column)
			{
				print ' | ' . str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths[$column]+2) . 's', $column));
			}
			?> | Download<br /><?php
		}
		elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
		{
			?><table cellpadding="10" cellspacing="0" border="1"><tr><td><?php
		}
		
		foreach($GLOBALS['templates']['html']['files'] as $i => $file)
		{
			$file = plain_alter_file($file, $column_lengths);
			$GLOBALS['templates']['html']['files'][$i] = $file;
			// make links browsable
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'archive')) $cat = 'archive';
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'playlist')) $cat = 'playlist';
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'diskimage')) $cat = 'diskimage';
			else $cat = $GLOBALS['templates']['vars']['cat'];
			
			if($GLOBALS['templates']['vars']['cat'] != $cat || $GLOBALS['templates']['vars']['files'][$i]['Filetype'] == 'FOLDER') $new_cat = $cat;
			
			$link = isset($new_cat)?href('plugin=select&cat=' . $new_cat . '&dir=' . urlencode($GLOBALS['templates']['vars']['files'][$i]['Filepath'])):href('plugin=file&cat=' . $cat . '&id=' . $file['id'] . '&filename=' . urlencode($GLOBALS['templates']['vars']['files'][$i]['Filename']));
			?>
			<input type="checkbox" name="item[]" value="<?php print $file['id']; ?>" <?php print isset($GLOBALS['templates']['vars']['selected'])?(in_array($GLOBALS['templates']['vars']['files'][$i]['id'], $GLOBALS['templates']['vars']['selected'])?'checked="checked"':''):''; ?> />
			<a href="<?php print $link; ?>"><?php print trim($file['Filepath'], '&nbsp;'); ?></a><?php print substr($file['Filepath'], strlen(trim($file['Filepath'], '&nbsp;'))); ?>
			<?php
			
			foreach($GLOBALS['templates']['vars']['settings']['columns'] as $j => $column)
			{
				if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
					print ' | ';
				elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
					print '</td><td>';
				else
					print ' - ';
				
				if(isset($file[$column]))
				{
					print $file[$column];
				}
			}
			
			if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
			{
				print ' | ';
			}
			elseif($GLOBALS['templates']['vars']['settings']['view'] == 'table')
			{
				print '</td><td>';
			}
			else
			{
				?> - Download: <?php
			}
			?>
			<a href="<?php print href(array(
							'plugin' => 'zip',
							'cat' => $GLOBALS['templates']['vars']['cat'],
							'id' => $file['id'],
							'filename' => 'Files.zip'
						)); ?>">zip</a> :
			<a href="<?php print href(array(
							'plugin' => 'torrent',
							'cat' => $GLOBALS['templates']['vars']['cat'],
							'id' => $file['id'],
							'filename' => 'Files.torrent'
						)); ?>">torrent</a>
			<?php
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'video'))
			{
				?>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'mp4', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'mpg', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MPG</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'wmv', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMV</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'audio'))
			{
				?>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'mp4a', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'mp3', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP3</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'wma', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMA</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'image'))
			{
				?>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'jpg', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">JPG</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'gif', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">GIF</a>
				: <a href="<?php print href(array('plugin' => 'encode', 'encode' => 'png', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">PNG</a>
				<?php
			}
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'code'))
			{
				?>
				: <a href="<?php print href(array('plugin' => 'code', 'cat' => $GLOBALS['templates']['vars']['cat'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">view</a>
				<?php
			}
			
			if($GLOBALS['templates']['vars']['settings']['view'] == 'table')
			{
				if($i < count($GLOBALS['templates']['vars']['files']) - 1)
				{
					?></td></tr><tr><td><?php
				}
			}
			else
			{
				?><br /><?php
			}
		}
		if($GLOBALS['templates']['vars']['settings']['view'] == 'mono')
		{
			?></code><?php
		}
		if($GLOBALS['templates']['vars']['settings']['view'] == 'table')
		{
			?></td></tr></table><?php
		}
	}
}

function theme_plain_index()
{
	?>
	There are <?php print $GLOBALS['templates']['html']['total_count']; ?> result(s).<br />
	Displaying items <?php print $GLOBALS['templates']['html']['start']; ?> to <?php print $GLOBALS['templates']['html']['start'] + $GLOBALS['templates']['html']['limit']; ?>.
	<br />
	<?php
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><span style="color:#C00"><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			?><b><?php print $error->message; ?></b><br /><?php
		}
		?></span><?php
	}
	
	theme('pages');
	?>
	<br />
	<form name="select" action="{$get}" method="post">
		<input type="submit" name="select" value="All" />
		<input type="submit" name="select" value="None" />
		<p style="white-space:nowrap">
		Select<br />
		On : Off<br />
		<?php
		theme('files');
		?>
		<input type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php
		
	theme('pages');
	
	?>
	<br /><br />Select a Template:<br />
	<?php
	
	theme('template_block');
}
