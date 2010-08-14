<?php

function theme_live_select_block()
{
	?>
	<div class="files" style="border:1px solid #006; height:150px; width:84px; border-right:0px; background-color:#FFF;">
		<div class="file FOLDER small" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="0"><div class="notselected"></div>
			<a class="itemLink" href="<?php print url($GLOBALS['output']['get'] . '&dir=' . dirname($GLOBALS['output']['dir'])); ?>" onmouseout="this.parentNode.firstChild.className = 'notselected';" onmouseover="this.parentNode.firstChild.className = 'selected';"><span>Up 1 Level</span></a>
		</div>
		<div class="file FOLDER" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="0"><div class="notselected"></div>
			<table class="itemTable" cellpadding="0" cellspacing="0" onclick="location.href = '<?php print url($GLOBALS['output']['get'] . '&dir=/'); ?>';">
				<tr>
					<td>
						<div class="thumb file_ext_FOLDER file_type_">
							<img src="<?php print url('template/live/images/s.gif'); ?>" alt="FOLDER" height="48" width="48">
						</div>
					</td>
				</tr>
			</table>
			<a class="itemLink" href="<?php print url($GLOBALS['output']['get'] . '&dir=/'); ?>" onmouseout="this.parentNode.firstChild.className = 'notselected';" onmouseover="this.parentNode.firstChild.className = 'selected';"><span>Top Directory</span></a>
		</div>
	</div>
	<div class="files" id="files" style="border:1px solid #006; overflow:auto; height:150px; width:400px; float:left; background-color:#FFF;"><?php
	if(count($GLOBALS['output']['files']) == 0)
	{
		$link = (dirname($GLOBALS['output']['dir']) == '/')?url($GLOBALS['output']['get'] . '&dir=/'):url($GLOBALS['output']['get'] . '&dir=' . urlencode(dirname($GLOBALS['output']['dir']) . '/'));
		?>
		<b>There are no files to display</b><br />
		<div class="filesmall FOLDER" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="0"><div class="notselected"></div>
			<table class="itemTable" cellpadding="0" cellspacing="0" onclick="location.href = '<?php print $link; ?>';">
				<tr>
					<td>
						<div class="thumbsmall file_ext_FOLDER file_type_">
							<img src="<?php print url('template/live/images/s.gif'); ?>" alt="FOLDER" height="48" width="48">
						</div>
					</td>
				</tr>
			</table>
			<a class="itemLink" href="<?php print $link; ?>" onmouseout="this.parentNode.firstChild.className = 'notselected';" onmouseover="this.parentNode.firstChild.className = 'selected';"><span>&lt;- Back</span></a>
		</div>
		<?php
	}
	else
	{
		// get longest filename to base widths off of
		$length = 0;
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			if(strlen($file['Filename']) > $length)
				$length = strlen($file['Filename']);
		}
		
		?><table cellpadding="0" cellspacing="" border="0" style="height:130px;">
			<tr>
				<td style="vertical-align:top; width:<?php print ceil($length*.75);?>em;"><?php
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			if($i > 0 && $i % 6 == 0)
			{
				?></td><td style="vertical-align:top; width:<?php print ceil($length*.75);?>em;"><?php
			}
			
			// make links browsable
			if(handles($file['Filepath'], 'archive')) $handler = 'archive';
			elseif(handles($file['Filepath'], 'playlist')) $handler = 'playlist';
			elseif(handles($file['Filepath'], 'diskimage')) $handler = 'diskimage';
			else $handler = $GLOBALS['output']['handler'];
			
			if($GLOBALS['output']['handler'] != $handler || $file['Filetype'] == 'FOLDER') $new_handler = $handler;
			
			$link = isset($new_handler)?url($GLOBALS['output']['get'] . '&start=0&handler=' . $new_handler . '&dir=' . urlencode($file['Filepath'])):url($GLOBALS['output']['get'] . '&dir=&id=' . urlencode($file['id']) . '&filename=' . urlencode($file['Filename']));
			
			?>
			<div class="filesmall <?php print $file['Filetype']; ?>" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="<?php print $file['id']; ?>"><div class="notselected"></div>
				<table class="itemTable" cellpadding="0" cellspacing="0" onclick="location.href = '<?php print $link; ?>';">
					<tr>
						<td>
							<div class="thumbsmall file_ext_<?php print $file['Filetype']; ?> file_type_<?php print str_replace('/', ' file_type_', $file['Filemime']); ?>">
								<img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" height="16" width="16">
							</div>
						</td>
					</tr>
				</table>
				<a class="itemLink" href="<?php print $link; ?>" onmouseout="this.parentNode.firstChild.className = 'notselected';" onmouseover="this.parentNode.firstChild.className = 'selected';"><span><?php print $GLOBALS['templates']['html']['files'][$i]['Filename']; ?></span></a>
			</div>
			<?php
		}
		
		?></td></tr></table><?php
	}
	?></div>
	<div style="clear:both; width:500px"><?php
	
	theme('pages');
	
	?></div><?php
}

function theme_live_files()
{
	if(!isset($GLOBALS['output']['files']) || count($GLOBALS['output']['files']) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		?><div class="files" id="files"><?php
		foreach($GLOBALS['output']['files'] as $i => $file)
		{
			$GLOBALS['templates']['html']['files'][$i] = live_alter_file($file);
			
			// make links browsable
			if(handles($file['Filepath'], 'archive')) $handler = 'archive';
			elseif(handles($file['Filepath'], 'playlist')) $handler = 'playlist';
			elseif(handles($file['Filepath'], 'diskimage')) $handler = 'diskimage';
			else $handler = $GLOBALS['output']['handler'];
			
			if($GLOBALS['output']['handler'] != $handler || $file['Filetype'] == 'FOLDER')
			{
				if(substr($file['Filepath'], -1) != '/') $file['Filepath'] .= '/';
				$new_handler = $handler;
			}
			if(isset($new_handler))
			{
				$link = url('select/dir/' . $GLOBALS['templates']['html']['files'][$i]['Filepath'] . '?handler=' . $new_handler);
			}
			else
				$link = url('file/' . $handler . '/' . $file['id'] . '/' . $file['Filename']);
			unset($new_handler);
			?>
			<div class="file <?php print $file['Filetype']; ?>" onmousedown="deselectAll(event);fileSelect(this, true, event);return false;" oncontextmenu="showMenu(this);return false;" id="<?php print $file['id']; ?>"><div class="notselected"></div>
				<table class="itemTable" cellpadding="0" cellspacing="0" onclick="location.href = '<?php print $link; ?>';">
					<tr>
						<td>
							<div class="thumb file_ext_<?php print $file['Filetype']; ?> file_type_<?php print isset($file['Filemime'])?str_replace('/', ' file_type_', $file['Filemime']):''; ?>">
								<?php
								if(handles($file['Filepath'], 'image'))
								{
									?><img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" style="background-image:url(<?php print url('convert/png?cheight=56&cwidth=56&id=' . $file['id']); ?>);" height="48" width="48"><?php
								}
								else
								{
									?><img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" height="48" width="48"><?php
								}
								?>
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

function theme_live_file($file)
{
}

function theme_live_select()
{
	theme('header');
	
	$current = isset($GLOBALS['template']['html']['dir'])?basename($GLOBALS['templates']['html']['dir']):'';
	
	?>
	<div class="contentSpacing">
			<h1 class="title"><?php print ($current == '')?setting('html_name'):$current; ?></h1>
			<span class="subText"><?php print 'Click to browse files. Drag to select files, and right click for download options.'; ?></span>
	<?php
	if (count($GLOBALS['user_errors']) == 0 && count($GLOBALS['output']['files']) > 0)
	{
		?>
		<span class="subText">Displaying items
			<?php print $GLOBALS['templates']['html']['start']+1; ?>
			through <?php print $GLOBALS['output']['start'] + $GLOBALS['output']['limit']; ?>
			<?php print ($GLOBALS['output']['total_count'] > $GLOBALS['output']['limit'])?(' out of ' . $GLOBALS['templates']['html']['total_count']):' file(s)'; ?>.
		</span>
		<?php
	}
	
	theme('errors_block');
	
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
	if(!isset($GLOBALS['output']['files']))
		return;
	foreach($GLOBALS['output']['files'] as $i => $file)
	{
		$info_count = 0;
		foreach($GLOBALS['output']['columns'] as $j => $column)
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
								<div class="thumb file_ext_<?php print $file['Filetype']; ?> file_type_<?php print isset($file['Filemime'])?str_replace('/', ' file_type_', $file['Filemime']):''; ?>">
									<img src="<?php print url('template/live/images/s.gif'); ?>" height="48" width="48">
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
				foreach($GLOBALS['output']['columns'] as $j => $column)
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

