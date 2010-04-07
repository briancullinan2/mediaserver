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

