<?php

function register_live_search()
{
	return array(
		'name' => 'Live Search',
	);
}

function theme_live_search_block()
{
	$theme = live_get_theme_color();
	?>
	<table cellpadding="0" cellspacing="0" id="middleArea">
		<tr>
			<td class="searchParent"><?php print $GLOBALS['handlers'][$GLOBALS['templates']['vars']['cat']]['name']; ?> Search:
				<form action="<?php print $GLOBALS['templates']['vars']['get']; ?>" method="get" id="search">
					<span class="searchBorder" style="border-color:<?php print ($theme == 'audio')?'#BB8888 #AA6666 #995555':(($theme == 'image')?'#BBBBAA #AAAACC #9999BB':(($theme == 'video')?'#88DDBB #66CCAA #55BB99':'#88BBDD #66AACC #5599BB')); ?>;"><span class="innerSearchBorder" style="border-color:<?php print ($theme == 'audio')?'#883333 #883322 #772211':(($theme == 'image')?'#888844 #888833 #777722':(($theme == 'video')?'#668866 #448844 #447744':'#446688 #335588 #115577')); ?>;"><input type="text" name="search" value="<?php print isset($GLOBALS['templates']['vars']['search']['search'])?$GLOBALS['templates']['vars']['search']['search']:''; ?>" id="searchInput" /><span class="buttonBorder"><input type="submit" value="Search" id="searchButton" /></span></span></span>&nbsp;&nbsp; <a id="advancedSearch" href="<?php echo url('module=search' . (isset($GLOBALS['templates']['vars']['dir'])?('&dir=' . $GLOBALS['templates']['vars']['dir']):'')); ?>">Advanced Search</a></form>
			</td>
		</tr>
	</table>
	<?php
}

function theme_live_search()
{
	theme('header');
	
	?>
	<script language="javascript">
	noselect = true;
	
	var cat_columns = [];
	<?php
	foreach($GLOBALS['handlers'] as $handler => $config)
	{
		?>cat_columns['<?php print $handler; ?>'] = new Array('<?php print join('\', \'', columns($handler)); ?>');<?php
	}
	?>
	function makeVisible(cat)
	{
		fields = document.getElementById('fields').getElementsByTagName('div');
		for(i = 0; i < fields.length; i++)
		{
			fields[i].style.display = 'none';
			fields[i].style.visibility = 'hidden';
		}
		
		fields = cat_columns[cat];
		for(i = 0; i < fields.length; i++)
		{
			field = document.getElementById('search_' + fields[i]);
			field.style.display = 'block';
			field.style.visibility = 'visible';
		}
	}
	
	</script>
	<div class="contentSpacing">
		<h1 class="title">Advanced Search</h1>
		<span class="subText">Use this form to search for files.<br />
		Note: Various characters can be used to affect the results.<br />
		For searching an entire set of words surround the query with double quotes (").<br />
		To use regular expression, use a slash (/) on both sides of the query.<br />
		To look for fields that are equal to the search query use an equal sign (=) on both sides.<br />
		Finally, to include terms preceed the term with a plus sign (+) and to exclude use a minus sign (-).</span>
		<?php
		theme('errors_block');
		?>
		<div class="titlePadding"></div>
		<form action="<?php print url(''); ?>" method="get">
			<h3>Search All Available Fields:</h3>
			Search: <input type="text" name="search" size="40" value="<?php print isset($GLOBALS['templates']['vars']['search'])?$GLOBALS['templates']['vars']['search']:''; ?>" /><br /><br />
			Directory: <input type="text" name="dir" size="40" value="<?php print isset($GLOBALS['templates']['vars']['dir'])?$GLOBALS['templates']['vars']['dir']:''; ?>" />
			<h3>Search Individual Fields:</h3>
			Category: <select name="cat" onchange="makeVisible(this.value)">
			<?php
			foreach($GLOBALS['handlers'] as $handler => $config)
			{
				?><option value="<?php print $handler; ?>" <?php print ($GLOBALS['templates']['vars']['cat'] == $handler)?'selected="selected"':''; ?>><?php print $config['name']; ?></option><?php
			}
			?>
			</select><br /><br />
			<div id="fields">
			<?php
			foreach($GLOBALS['templates']['vars']['columns'] as $column)
			{
				?>
				<div id="search_<?php print $column; ?>"><?php print $column; ?>:
					<input type="text" name="search_<?php print $column; ?>" size="40" value="<?php print isset($GLOBALS['templates']['vars']['search']['search_' . $column])?$GLOBALS['templates']['html']['search']['search_' . $column]:''; ?>" />
					<br />
					<br />
				</div>
				<?php
			}
			?>
			<script language="javascript">
			makeVisible('<?php print $GLOBALS['templates']['html']['cat']; ?>');
			</script>
			</div>
			<input type="submit" value="Search" /><input type="reset" value="Reset" />
		</form>
	</div>
	<?php
	
	theme('footer');
}
