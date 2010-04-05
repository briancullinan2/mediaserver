<?php

function register_live_index()
{
	return array(
		'name' => 'Live Index',
	);
}

function live_get_info_count()
{
	$biggest = 0;
	foreach($GLOBALS['templates']['vars']['files'] as $file)
	{
		$info_count = 0;
		foreach($GLOBALS['templates']['vars']['columns'] as $column)
		{
			if(isset($file[$column]) && $file[$column] != '' && strlen($file[$column]) <= 200 &&
				substr($column, -3) != '_id' && $column != 'id' && $column != 'Hex' && $column != 'Filename' && $column != 'Filetype')
			$info_count++;
		}
		
		$info_count = ceil($info_count/2);
		if($info_count > $biggest) $biggest = $info_count;
	}
	
	return $biggest;
}

function theme_live_pages()
{
	?>
	<table cellpadding="0" cellspacing="0" class="pageTable">
		<tr>
			<td align="center">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td>
	<?php
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
			<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">First</a>
			</div>
			<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $prev_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Prev</a>
			</div>
			<?php
			}
			else
			{
			?>
			<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
				<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">First</a>
			</div>
			<?php
			}
			?><div class="page">|</div><?php
		}
		
		for($i = $lower; $i < $upper + 1; $i++)
		{
			if($i == $page_int)
			{
				?><div class="page<?php print (strlen($i) > 2)?'W':''; ?>"><b><?php print $page_int + 1; ?></b></div><?
			}
			else
			{
				?>
				<div class="page<?php print (strlen($i) > 2)?'W':''; ?>"><div class="pageHighlight<?php print (strlen($i) > 2)?'W':''; ?>" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . ($i * $GLOBALS['templates']['vars']['limit'])); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;"><?php print $i + 1; ?></a>
				</div>
				<?php
			}
		}
		
		if($GLOBALS['templates']['vars']['start'] <= $GLOBALS['templates']['vars']['total_count'] - $GLOBALS['templates']['vars']['limit'])
		{
			?><div class="page">|</div><?php
			$last_page = floor($GLOBALS['templates']['vars']['total_count'] / $GLOBALS['templates']['vars']['limit']) * $GLOBALS['templates']['vars']['limit'];
			$next_page = $GLOBALS['templates']['vars']['start'] + $GLOBALS['templates']['vars']['limit'];
			if($GLOBALS['templates']['vars']['start'] < $GLOBALS['templates']['vars']['total_count'] - 2 * $GLOBALS['templates']['vars']['limit'])
			{
				?>
				<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $next_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Next</a>
				</div>
				<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Last</a>
				</div>
				<?php
			}
			else
			{
				?>
				<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print href($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Last</a>
				</div>
				<?php
			}
		}
	}
	?>
	
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
 <?php
}

function theme_live_files()
{
	if(count($GLOBALS['templates']['vars']['files']) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		?><div class="files" id="files"><?php
		foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
		{
			$GLOBALS['templates']['html']['files'][$i] = live_alter_file($file);
			
			// make links browsable
			if(handles($file['Filepath'], 'archive')) $cat = 'archive';
			elseif(handles($file['Filepath'], 'playlist')) $cat = 'playlist';
			elseif(handles($file['Filepath'], 'diskimage')) $cat = 'diskimage';
			else $cat = $GLOBALS['templates']['vars']['cat'];
			
			if($GLOBALS['templates']['vars']['cat'] != $cat || $file['Filetype'] == 'FOLDER') $new_cat = $cat;
			
			$link = isset($new_cat)?href('plugin=select&cat=' . $new_cat . '&dir=' . urlencode($file['Filepath'])):href('file/' . $cat . '/' . $file['id'] . '/' . $file['Filename']);
			
			?>
			<div class="file <?php print $file['Filetype']; ?>" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="<?php print $file['id']; ?>"><div class="notselected"></div>
				<table class="itemTable" cellpadding="0" cellspacing="0" onclick="location.href = '<?php print $link; ?>';">
					<tr>
						<td>
							<div class="thumb file_ext_<?php print $file['Filetype']; ?> file_type_<?php print str_replace('/', ' file_type_', $file['Filemime']); ?>">
								<img src="<?php print href('plugin=template&tfile=images/s.gif&template=' . HTML_TEMPLATE); ?>" alt="<?php print $file['Filetype']; ?>" height="48" width="48">
							</div>
						</td>
					</tr>
				</table>
				<a class="itemLink" href="<?php print $link; ?>" onmouseout="this.parentNode.firstChild.className = 'notselected'; if(!loaded){return false;} document.getElementById('info_<?php print $file['id']; ?>').style.display = 'none';document.getElementById('info_<?php print $file['id']; ?>').style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.className = 'selected'; if(!loaded){return false;} document.getElementById('info_<?php print $file['id']; ?>').style.display = '';document.getElementById('info_<?php print $file['id']; ?>').style.visibility = 'visible'; return true;"><span><?php print $GLOBALS['templates']['html']['files'][$i]['Filename']; ?></span></a>
			</div>
			<?php
		}
		?></div><?php
	}
}

function live_alter_file($file)
{
	foreach($file as $column => $value)
	{
		if(isset($GLOBALS['templates']['vars']['search_regexp']) && 
			isset($GLOBALS['templates']['vars']['search_regexp'][$column]))
			$file[$column] = preg_replace($GLOBALS['templates']['vars']['search_regexp'][$column], '\'<strong style="background-color:#990;">\' . htmlspecialchars(\'$0\') . \'</strong>\'', $file[$column]);
		//$file[$column] = preg_replace('/([^ ]{25})/i', '$1<br />', $file[$column]);
	}
	return $file;
}

function theme_live_info()
{
	$theme = live_get_theme_color();

	$biggest = live_get_info_count();
	
	// hack header to add new row
	?>
	</td>
</tr>
<tr>
	<td id="infoBar" style="background-color:<?php print ($theme == 'audio')?'#900':(($theme == 'image')?'#990':(($theme == 'video')?'#093':'#06A')); ?>; height:<?php print max($biggest+3, 7); ?>em;">
	<?php
	foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
	{
		$info_count = 0;
		foreach($GLOBALS['templates']['vars']['columns'] as $j => $column)
		{
			if(isset($file[$column]) && $file[$column] != '' && strlen($file[$column]) <= 200 &&
				substr($column, -3) != '_id' && $column != 'id' && $column != 'Hex' && $column != 'Filepath' && 
				$column != 'Filename' && $column != 'Filetype'
			)
			$info_count++;
		}
		
		$info_count = ceil($info_count / 2);
		// {if $info_count > $biggest}{assign var=biggest value=$info_count}{/if}
		
		//
		?>
		<table cellpadding="0" cellspacing="0" border="0" class="fileInfo" id="info_<?php print $file['id']; ?>" style="display:none; visibility:hidden;">
			<tr>
				<td>
					<table cellpadding="0" cellspacing="0" border="0" class="fileThumb">
						<tr>
							<td>
								<div class="thumb file_ext_<?php print $file['Filetype']; ?> file_type_<?php print str_replace('/', ' file_type_', $file['Filemime']); ?>">
									<img src="<?php print href('plugin=template&file=images/s.gif&template=' . HTML_TEMPLATE); ?>" height="48" width="48">
								</div>
							</td>
							<td class="infoCell">
								<span class="title"><?php print $GLOBALS['templates']['html']['files'][$i]['Filename']; ?></span><br />
								<span><?php print $GLOBALS['templates']['html']['files'][$i]['Filetype']; ?></span>
							</td>
						</tr>
					</table>
				</td>
				<td>
				<?php
				$count = 0;
				foreach($GLOBALS['templates']['vars']['columns'] as $j => $column)
				{
					if(isset($file[$column]) && $file[$column] != '' && strlen($file[$column]) <= 200 &&
						substr($column, -3) != '_id' && $column != 'id' && $column != 'Hex' && 
						$column != 'Filename' && $column != 'Filetype'
					)
					{
						$count++;
						?>
						<span class="label" style="color:<?php print ($theme == 'audio')?'#F66':(($theme == 'image')?'#FFA':(($theme == 'video')?'#6FA':'#6CF')); ?>;"><?php print $column; ?>:</span>
						<?php
						
						if($column == 'Filepath')
						{
							if(dirname(dirname($file['Filepath'])) != '/')
								print '../../' . basename(dirname($file['Filepath'])) . '/' . basename($file['Filepath']);
							else
								print $GLOBALS['templates']['html']['files'][$i]['Filepath'];
						}
						elseif($column == 'Filesize')
							print roundFileSize($file['Filesize']);
						elseif($column == 'Compressed')
							print roundFileSize($file['Compressed']);
						elseif($column == 'Bitrate')
							print round($file['Bitrate'] / 1000, 1) . ' kbs';
						elseif($column == 'Length')
							print floor($file['Length'] / 60) . ' minutes ' . floor($file['Length'] % 60) . ' seconds';
						else
							print $GLOBALS['templates']['html']['files'][$i][$column];
						?>
						<br />
						<?php
						if($count == $info_count && $info_count >= 3)
						{
							?>
							</td>
							<td>
							<?php
						}
					}
				}
				
				if($count < $info_count || $info_count < 3)
				{
					?></td><td>&nbsp;<?php
				}
				?>
				</td>
			</tr>
		</table>
		<?php
	}
}

function theme_live_errors()
{
	if(count($GLOBALS['warn_errors']) > 0)
	{
		?><div style="border:2px solid #CC0; background-color:#FF9;"><?php
		foreach($GLOBALS['warn_errors'] as $error)
		{
			?><b><?php print $error->message; ?></b><br /><?php
		}
		?></div><?php
	}
	
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><div style="border:2px solid #C00; background-color:#F99;"><?php
		foreach($GLOBALS['user_errors'] as $error)
		{
			?><b><?php print $error->message; ?></b><br /><?php
		}
		?></div><?php
	}
	restore_error_handler();
}

function theme_live_index()
{
	theme('header');
	
	$current = basename($GLOBALS['templates']['html']['dir']);
	
	?>
	<div class="contentSpacing">
			<h1 class="title"><?php print ($current == '')?HTML_NAME:$current; ?></h1>
			<span class="subText">Click to browse files. Drag to select files, and right click for download options.</span>
	<?php
	if (count($GLOBALS['user_errors']) == 0 && count($GLOBALS['templates']['vars']['files']) > 0)
	{
		?>
		<span class="subText">Displaying items
			<?php print $GLOBALS['templates']['html']['start']+1; ?>
			through <?php print $GLOBALS['templates']['vars']['start'] + $GLOBALS['templates']['vars']['limit']; ?>
			<?php print ($GLOBALS['templates']['vars']['total_count'] > $GLOBALS['templates']['vars']['limit'])?(' out of ' . $GLOBALS['templates']['html']['total_count']):' file(s)'; ?>.
		</span>
		<?php
	}
	
	theme('errors');
	
	theme('pages');
	
	?>
	<div class="titlePadding"></div>
	<?php
	
	theme('files');

	theme('pages');
	
	theme('info');

	?>
<script language="javascript">
loaded = true;
if(document.getElementById("debug")) {
	header_height = document.getElementById("header").clientHeight + document.getElementById("debug").clientHeight;
} else {
	header_height = document.getElementById("header").clientHeight;}
</script>
<?php
	theme('footer');
}

