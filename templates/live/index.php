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
	if(!isset($GLOBALS['templates']['vars']['files'])) return $biggest;
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
	// set some variables if they are missing so we can avoid errors
	if(!isset($GLOBALS['templates']['vars']['start']))
		$GLOBALS['templates']['vars']['start'] = 0;
	if(!isset($GLOBALS['templates']['vars']['total_count']))
		$GLOBALS['templates']['vars']['total_count'] = 0;
	if(!isset($GLOBALS['templates']['vars']['limit']))
		$GLOBALS['templates']['vars']['limit'] = 50;

	?>
	<table cellpadding="0" cellspacing="0" class="pageTable">
		<tr>
			<td align="center">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td>
	<?php
	if(!isset($GLOBALS['templates']['vars']['files']))
		$item_count = 0;
	else
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
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">First</a>
			</div>
			<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $prev_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Prev</a>
			</div>
			<?php
			}
			else
			{
			?>
			<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
				<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=0'); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">First</a>
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
					<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . ($i * $GLOBALS['templates']['vars']['limit'])); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;"><?php print $i + 1; ?></a>
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
					<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $next_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Next</a>
				</div>
				<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Last</a>
				</div>
				<?php
			}
			else
			{
				?>
				<div class="pageW"><div class="pageHighlightW" style="visibility:hidden"></div>
					<a class="pageLink" href="<?php print url($GLOBALS['templates']['vars']['get'] . '&start=' . $last_page); ?>" onmouseout="this.parentNode.firstChild.style.visibility = 'hidden'; return true;" onmouseover="this.parentNode.firstChild.style.visibility = 'visible'; return true;">Last</a>
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

function theme_live_errors_block()
{
	$error_list = array('warn_errors', 'user_errors', 'note_errors');
	
	foreach($error_list as $i => $errors)
	{
		if(count($GLOBALS[$errors]) > 0)
		{
			?><div id="<?php print (isset($GLOBALS['templates']['vars']['errors_only']) && 
				$GLOBALS['templates']['vars']['errors_only'] == true)?($errors . '_only'):$errors; ?>"><?php
			foreach($GLOBALS[$errors] as $i => $error)
			{
				if($i == 5)
				{
					?><div id="inner_<?php print $errors; ?>" class="error hide"><?php
				}
				?><b><?php print $error; ?></b><br /><?php
			}
			if(count($GLOBALS[$errors]) > 5)
			{
				?></div>
				And <?php print count($GLOBALS[$errors]) - 5; ?> more: <a href="javascript:return true;" onClick="if(this.hidden == false) { document.getElementById('inner_<?php print $errors; ?>').className='error hide'; this.hidden=true; this.innerHTML = 'Un Hide'; } else { document.getElementById('inner_<?php print $errors; ?>').className='error'; this.hidden=false; this.innerHTML = 'Hide'; }">Un Hide</a>
				<?php
			}
			?></div><?php
		}
	}

	// clear current list because it was just outputted
	$GLOBALS['warn_errors'] = array();
	$GLOBALS['user_errors'] = array();
	$GLOBALS['note_errors'] = array();

	if(!isset($GLOBALS['templates']['vars']['errors_only']) || $GLOBALS['templates']['vars']['errors_only'] == false)
	{
		?>
		<div id="tmp_errors"></div>
		<script language="javascript">
			$(document).ready(function() {
				$.get('<?php print url('module=core&errors_only=true', true); ?>',function(data, status, xhr){
					$('#tmp_errors').html(data);
					
					$('#warn_errors').append($('#warn_errors_only'));
					$('#user_errors').append($('#user_errors_only'));
					$('#note_errors').append($('#note_errors_only'));
					
					$('#tmp_errors').remove();
				}, 'html');
			});
		</script>
		<?php
	}
}

function theme_live_index()
{
	theme_live_select();
}

