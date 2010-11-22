<?php

function theme_live_search_block()
{
	?>
	<table cellpadding="0" cellspacing="0" id="middleArea">
		<tr>
			<td class="searchParent"><?php print get_module($GLOBALS['output']['handler'], 'name'); ?> Search:
				<form action="<?php print $GLOBALS['output']['html']['get']; ?>" method="get" id="search">
					<span class="searchBorder colors_outer"><span class="innerSearchBorder colors_inner"><input type="text" name="search" value="<?php print isset($GLOBALS['output']['html']['search']['search'])?$GLOBALS['output']['html']['search']['search']:''; ?>" id="searchInput" /><span class="buttonBorder"><input type="submit" value="Search" id="searchButton" /></span></span></span>&nbsp;&nbsp; <a id="advancedSearch" href="<?php echo url('search' . (isset($GLOBALS['output']['dir'])?('?dir=' . $GLOBALS['output']['dir']):'')); ?>">Advanced Search</a></form>
			</td>
		</tr>
	</table>
	<?php
}

function theme_live_search()
{
	theme('header',
		'Advanced Search',
		'Use this form to search for files.<br />
		Note: Various characters can be used to affect the results.<br />
		For searching an entire set of words surround the query with double quotes (").<br />
		To use regular expression, use a slash (/) on both sides of the query.<br />
		To look for fields that are equal to the search query use an equal sign (=) on both sides.<br />
		Finally, to include terms preceed the term with a plus sign (+) and to exclude use a minus sign (-).'
	);
	
	?>
	<script language="javascript">
	noselect = true;
	
	var cat_columns = [];
	<?php
	foreach(get_handlers() as $handler => $config)
	{
		?>cat_columns['<?php print $handler; ?>'] = new Array('<?php print join('\', \'', get_columns($handler)); ?>');
	<?php
	}
	?>
	
	function makeVisible(cat)
	{
		$('#fields div').hide();
		
		var fields = cat_columns[cat];
		for(i = 0; i < fields.length; i++)
		{
			field = document.getElementById('search_' + fields[i]);
			field.style.display = 'block';
			field.style.visibility = 'visible';
		}
	}
	
	</script>
	<form action="<?php print url('select'); ?>" method="get">
		<h3>Search All Available Fields:</h3>
		Search: <input type="text" name="search" size="40" value="<?php print isset($GLOBALS['output']['html']['search'])?$GLOBALS['output']['html']['search']:''; ?>" /><br /><br />
		Directory: <input type="text" name="dir" size="40" value="<?php print isset($GLOBALS['output']['html']['dir'])?$GLOBALS['output']['html']['dir']:''; ?>" />
		<h3>Search Individual Fields:</h3>
		Category: <select name="handler" onchange="makeVisible(this.value)">
		<?php
		foreach(get_handlers() as $handler => $config)
		{
			?><option value="<?php print $handler; ?>" <?php print ($GLOBALS['output']['handler'] == $handler)?'selected="selected"':''; ?>><?php print $config['name']; ?></option><?php
		}
		?>
		</select><br /><br />
		<div id="fields">
		<?php
		foreach($GLOBALS['output']['columns'] as $column)
		{
			?>
			<div id="search_<?php print $column; ?>"><?php print $column; ?>:
				<input type="text" name="search_<?php print $column; ?>" size="40" value="<?php print isset($GLOBALS['output']['html']['search']['search_' . $column])?$GLOBALS['output']['html']['search']['search_' . $column]:''; ?>" />
				<br />
				<br />
			</div>
			<?php
		}
		?>
		<script language="javascript">
		makeVisible('<?php print $GLOBALS['output']['html']['handler']; ?>');
		</script>
		</div>
		<input type="submit" value="Search" /><input type="reset" value="Reset" />
	</form>
	<?php
	
	theme('footer');
}
