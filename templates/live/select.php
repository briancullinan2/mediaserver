<?php

function theme_live_select()
{
	$current = isset($GLOBALS['output']['html']['dir'])?ucwords(basename($GLOBALS['output']['html']['dir'])):'';
	
	$description = 'Click to browse files. Drag to select files, and right click for download options.';
	if(count($GLOBALS['user_errors']) == 0 && count($GLOBALS['output']['files']) > 0)
	{
		$description .= '<br />Displaying items ' . ($GLOBALS['output']['html']['start']+1) .
			' through ' . min($GLOBALS['output']['start'] + count($GLOBALS['output']['files']), $GLOBALS['output']['start'] + $GLOBALS['output']['limit']) . 
			' out of ' . $GLOBALS['output']['html']['total_count'] . ' file(s).';
	}
	
	theme('header',
		($current == '')?'Select Files':$current,
		$description
	);
	
	
	theme('pages');
	
	?>
	<div class="titlePadding"></div>
	<?php
	
	theme('files', $GLOBALS['output']['files']);

	theme('pages');
	
	theme('info', $GLOBALS['output']['files'], $GLOBALS['output']['columns']);

	theme('footer');
}

function theme_live_select_block()
{
	?>
	<div class="files" style="border:1px solid #006; height:150px; width:84px; border-right:0px; background-color:#FFF;">
		<div class="file FOLDER small" id="0">
			<a class="itemLink" href="<?php print url($GLOBALS['output']['get'] . '&dir=' . dirname($GLOBALS['output']['dir'])); ?>"><span>Up 1 Level</span></a>
		</div>
		<div class="file file_ext_FOLDER file_type_" id="0">
			<table class="itemTable" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<div class="thumb">
							<img src="<?php print url('template/live/images/s.gif'); ?>" alt="FOLDER" height="48" width="48">
						</div>
					</td>
				</tr>
			</table>
			<a class="itemLink" href="<?php print url($GLOBALS['output']['get'] . '&dir=/'); ?>"><span>Top Directory</span></a>
		</div>
	</div>
	<div class="files" id="files" style="border:1px solid #006; overflow:auto; height:150px; width:400px; float:left; background-color:#FFF;"><?php
	if(count($GLOBALS['output']['files']) == 0)
	{
		$link = (dirname($GLOBALS['output']['dir']) == '/')?url($GLOBALS['output']['get'] . '&dir=/'):url($GLOBALS['output']['get'] . '&dir=' . urlencode(dirname($GLOBALS['output']['dir']) . '/'));
		?>
		<b>There are no files to display</b><br />
		<div class="filesmall file_ext_FOLDER file_type_" id="0">
			<table class="itemTable" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<div class="thumbsmall">
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
			<div class="filesmall file_ext_<?php print $file['Filetype']; ?> file_type_<?php print str_replace('/', ' file_type_', $file['Filemime']); ?>" id="<?php print $file['id']; ?>">
				<table class="itemTable" cellpadding="0" cellspacing="0">
					<tr>
						<td>
							<div class="thumbsmall">
								<img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" height="16" width="16">
							</div>
						</td>
					</tr>
				</table>
				<a class="itemLink" href="<?php print $link; ?>"><span><?php print $GLOBALS['output']['html']['files'][$i]['Filename']; ?></span></a>
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

function theme_live_files($files = NULL)
{
	if(!isset($files) || count($files) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		$scheme = live_get_scheme();
		if($scheme == 'code' && is_module('code'))
		{
			?><div id="codepreview"><?php print $files[0]['HTML']; ?></div>
			<div class="filestrip">
			<div class="files" style="width:4160px;"><?php
			foreach($files as $i => $file)
			{
				// check if we should use an image with preview instead of usual file
				if(handles($file['Filepath'], 'code'))
				{
					theme('file_preview_code', $file);
				}
				else
				{
					theme('file', $file, $GLOBALS['output']['handler']);
				}
			}
			?></div></div><?php
		}
		elseif($scheme == 'image' && is_module('convert'))
		{
			?><img id="preview" src="<?php print url('convert/png?cheight=500&cwidth=500&id=' . $files[0]['id']); ?>" />
			<div class="filestrip">
			<div class="files" style="width:4160px;"><?php
			foreach($files as $i => $file)
			{
				// check if we should use an image with preview instead of usual file
				if(handles($file['Filepath'], 'image'))
				{
					theme('file_preview_image', $file);
				}
				else
				{
					theme('file', $file, $GLOBALS['output']['handler']);
				}
			}
			?></div></div><?php
		}
		else
		{
			?><div class="files"><?php
			foreach($files as $i => $file)
			{
				theme('file', $file, $GLOBALS['output']['handler']);
			}
			?></div><?php
		}
	}
}

function theme_live_file_preview_code($file)
{
	$html = live_alter_file($file);
	
	$link = "$('#codepreview').load('" . url('files/code/' . $html['id']) . "/" . urlencode($file['Filename']) . "')";
	
	?>
	<div class="file preview file_ext_<?php print $html['Filetype']; ?> file_type_<?php print isset($html['Filemime'])?str_replace('/', ' file_type_', $html['Filemime']):''; ?>" id="<?php print $html['id']; ?>">
		<table class="itemTable" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<div class="thumb">
						<img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" height="48" width="48">
					</div>
				</td>
			</tr>
		</table>
		<a class="itemLink" href="#" onclick="<?php print $link; ?>; return false;"><span><?php print $html['Filename']; ?></span></a>
	</div>
	<?php
}

function theme_live_file_preview_image($file)
{
	$html = live_alter_file($file);
	
	$link = "$('#preview').attr('src', '" . url('convert/png?cheight=500&cwidth=500&id=' . $html['id']) . "')";
	
	?>
	<div class="file preview file_ext_<?php print $html['Filetype']; ?> file_type_<?php print isset($html['Filemime'])?str_replace('/', ' file_type_', $html['Filemime']):''; ?>" id="<?php print $html['id']; ?>">
		<table class="itemTable" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<div class="thumb">
						<img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $html['Filetype']; ?>" style="background-image:url(<?php print url('convert/png?cheight=56&cwidth=56&id=' . $html['id']); ?>);" height="48" width="48">
					</div>
				</td>
			</tr>
		</table>
		<a class="itemLink" href="#" onclick="<?php print $link; ?>; return false;"><span><?php print $html['Filename']; ?></span></a>
	</div>
	<?php
}

function theme_live_file($file, $current_handler = 'files')
{
	$html = live_alter_file($file);
	
	// make links browsable
	if(handles($file['Filepath'], 'archive')) $handler = 'archive';
	elseif(handles($file['Filepath'], 'playlist')) $handler = 'playlist';
	elseif(handles($file['Filepath'], 'diskimage')) $handler = 'diskimage';
	else $handler = $current_handler;

	if($current_handler != $handler || $file['Filetype'] == 'FOLDER')
	{
		if(substr($file['Filepath'], -1) != '/') $file['Filepath'] .= '/';
		$new_handler = $handler;
	}
	
	if(isset($new_handler))
		$link = url('select/' . $new_handler . '/' . $html['Filepath']);
	elseif(handles($file['Filepath'], 'image'))
		$link = url('select/' . $handler . '?file=' . $html['Filepath']);
	else
		$link = url('files/' . $handler . '/' . $file['id'] . '/' . urlencode($file['Filename']));

	unset($new_handler);
	?>
	<div class="file file_ext_<?php print $file['Filetype']; ?> file_type_<?php print isset($file['Filemime'])?str_replace('/', ' file_type_', $file['Filemime']):''; ?>" id="<?php print $html['id']; ?>">
		<table class="itemTable" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<div class="thumb">
						<?php
						if(handles($file['Filepath'], 'image') && is_module('convert'))
						{
							?><img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" style="background-image:url(<?php print url('convert/png?cheight=56&cwidth=56&id=' . $html['id']); ?>);" height="48" width="48"><?php
						}
						elseif(handles($file['Filepath'], 'movies') && is_module('convert'))
						{
							?><img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" style="background-image:url(<?php print url('convert/png?handler=movies&cheight=56&cwidth=56&id=' . $html['id']); ?>);" height="48" width="48"><?php
						}
						elseif(handles($file['Filepath'], 'discogs') && is_module('convert'))
						{
							?><img src="<?php print url('template/live/images/s.gif'); ?>" alt="<?php print $file['Filetype']; ?>" style="background-image:url(<?php print url('convert/png?handler=discogs&cheight=56&cwidth=56&id=' . $html['id']); ?>);" height="48" width="48"><?php
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
		<a class="itemLink" href="<?php print $link; ?>"><span><?php print $html['Filename']; ?></span></a>
	</div>
	<?php
}


function theme_live_info($files = array(), $columns = array())
{
	$biggest = live_get_info_count();
	
	// hack header to add new row
	?>
	</td>
</tr>
<tr>
	<td id="infoBar" class="colors_bg infobar" style="height:<?php print max($biggest+3, 7); ?>em;">
	<?php
	foreach($files as $i => $file)
	{
		$html = live_alter_file($file);
					
		$info_count = 0;
		foreach($columns as $j => $column)
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
		<table cellpadding="0" cellspacing="0" border="0" class="fileInfo" id="info_<?php print $file['id']; ?>" style="display:none;">
			<tr>
				<td>
					<table cellpadding="0" cellspacing="0" border="0" class="fileThumb">
						<tr>
							<td>
								<div class="thumb file_ext_<?php print $html['Filetype']; ?> file_type_<?php print isset($html['Filemime'])?str_replace('/', ' file_type_', $html['Filemime']):''; ?>">
									<img src="<?php print url('template/live/images/s.gif'); ?>" height="48" width="48">
								</div>
							</td>
							<td class="infoCell">
								<span class="title"><?php print $html['Filename']; ?></span><br />
								<span><?php print $html['Filetype']; ?></span>
							</td>
						</tr>
					</table>
				</td>
				<td>
				<?php
				$count = 0;
				foreach($columns as $j => $column)
				{
					if(isset($file[$column]) && $file[$column] != '' && strlen($file[$column]) <= 200 &&
						substr($column, -3) != '_id' && $column != 'id' && $column != 'Hex' && 
						$column != 'Filename' && $column != 'Filetype'
					)
					{
						$count++;
						?>
						<span class="label colors_fg"><?php print htmlspecialchars($column); ?>:</span>
						<?php
						
						if($column == 'Filepath')
						{
							if(dirname(dirname($file['Filepath'])) != '/')
								print wordwrap('../../' . basename(dirname($html['Filepath'])) . '/' . basename($html['Filepath']), 45, "<br />", true);
							else
								print wordwrap($html['Filepath'], 45, "<br />", true);
						}
						else
							print $html[$column];
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

