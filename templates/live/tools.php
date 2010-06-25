<?php

function register_live_tools()
{
	return array(
		'name' => 'Live Tools'
	);
}

function theme_live_tools()
{
	theme('header');

	?>
	<div class="contentSpacing">
			<h1 class="title">Tools</h1>
			<span class="subText">Select the tool you would like to use below.</span>
	<?php
	
	theme('errors_block');
	
	?><div class="titlePadding"></div><?php

	foreach($GLOBALS['modules']['admin']['modules']['tools']['modules'] as $name => $tool)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url('module=admin_tools_' . $name); ?>" style="font-size:14px;"><?php print $tool['name']; ?></a><br />
			Description: <?php print $tool['description']; ?><br /><br />
			<?php
			if(isset($tool['subtools']))
			{
				foreach($tool['subtools'] as $i => $subtool)
				{
					?><a href="<?php print url('module=admin_tools_' . $name . '&subtool=' . $i); ?>" style="margin:5px;"><?php print $subtool['name']; ?></a><?php
				}
				?><br /><br /><?php
			}
			?></div><?php
	}
	
	?><div class="titlePadding"></div>
	</div><?php

	theme('footer');
}

function theme_live_tools_subtools()
{
	theme('header');

	if(!isset($GLOBALS['templates']['vars']['subtool']))
	{
		?>
		<div class="contentSpacing">
				<h1 class="title">Tools: <?php print $GLOBALS['modules'][$GLOBALS['module']]['name']; ?></h1>
				<span class="subText"><?php print $GLOBALS['modules'][$GLOBALS['module']]['description']; ?></span>
		<?php
		
		theme('errors_block');
		
		// output configuration if it is set
		if(isset($GLOBALS['templates']['vars']['options']))
			theme('admin_modules_configure');
			
		?><div class="titlePadding"></div><?php

		foreach($GLOBALS['modules'][$GLOBALS['module']]['subtools'] as $i => $subtool)
		{
			if($subtool['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
				continue;
				
			?>
			<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
				<a href="<?php print url('module=' . $GLOBALS['module'] . '&subtool=' . $i); ?>" style="font-size:14px;"><?php print $subtool['name']; ?></a><br /><br />
				Description: <?php print $subtool['description']; ?>
				<br /><br />
			</div>
			<?php
		}
	}
	else
	{
		// save this for XML output
		// output info objects
		/*$tool = preg_replace('/\<warning label="([^"]*)"\>/i', '<div class="warning"><span>$1: </span>', $tool);
		$tool = preg_replace('/\<\/warning\>/i', '</div>', $tool);
		
		$tool = preg_replace('/\<info label="([^"]*)"\>/i', '<div class="info"><span>$1: </span>', $tool);
		$tool = preg_replace('/\<\/info\>/i', '</div>', $tool);
		
		$tool = preg_replace('/\<section label="([^"]*)"\>/i', '<div class="section"><span>$1: </span>', $tool);
		$tool = preg_replace('/\<\/section\>/i', '</div>', $tool);
		
		$tool = preg_replace('/\<text\>/i', '<p>', $tool);
		$tool = preg_replace('/\<\/text\>/i', '</p>', $tool);
		
		$tool = preg_replace('/\<note\>/i', '<div class="note">', $tool);
		$tool = preg_replace('/\<\/note\>/i', '</div>', $tool);
		print $tool;
		?></div><br /><?php*/
		?>
		<div class="contentSpacing">
				<h1 class="title">Tools: <?php print $GLOBALS['modules'][$GLOBALS['module']]['subtools'][$GLOBALS['templates']['vars']['subtool']]['name']; ?></h1>
				<span class="subText"><?php print $GLOBALS['modules'][$GLOBALS['module']]['subtools'][$GLOBALS['templates']['vars']['subtool']]['description']; ?></span>
		<?php
		
		theme('errors_block');
		
		// output configuration if it is set
		if(isset($GLOBALS['templates']['vars']['options']))
			theme('admin_modules_configure');
		
		?><div class="titlePadding"></div>
		<script language="javascript" type="application/javascript">
			var singular_cancel = false;
		</script>
		<form action="<?php print $GLOBALS['templates']['html']['get']; ?>" method="post">
			<table border="0" cellpadding="0" cellspacing="0" class="install">
			<?php
			
			// print options
			theme_live_tools_singular();
			
			?>
			</table>
			<br />
			<br />
			<br />
			<input type="button" name="reload" value="Reload" onclick="window.location.reload();" class="button" style="float:right;" />
		</form>
		<?php
	}
	
	?><div class="titlePadding"></div>
	</div><?php

	theme('footer');
		
}

function theme_live_tools_singular()
{
	$GLOBALS['debug_errors'] = array();
	// print options
	foreach($GLOBALS['templates']['vars']['infos'] as $name => $config)
	{
		?>
		<tr id="row_<?php print $name; ?>">
			<td class="title <?php print $config['status']; ?>"><?php print htmlspecialchars($config['name'], ENT_QUOTES); ?> (<?php print $name; ?>)</td>
			<td>
			<?php print_info_objects($config); ?>
			</td>
			<td class="desc">
			<?php print_info_objects($config['description']); ?>
			</td>
		</tr>
		<?php
	}
	
	?><script language="javascript"><?php
	// print out singular stuff
	foreach($GLOBALS['templates']['vars']['infos'] as $name => $config)
	{
		if(isset($config['singular']))
		{
			?>
			if(!singular_cancel)
			{
				$.get('<?php print $config['singular']; ?>',function(data){
					$('#row_<?php print $name; ?>').replaceWith(data);
				}, 'text');
			}
			<?php
		}
	}
	?></script><?php
}
