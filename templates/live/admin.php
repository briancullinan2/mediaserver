<?php

function register_live_admin()
{
	return array(
		'name' => 'Administration Menu',
	);
}

function theme_live_admin()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Administration</h1>
			<span class="subText">Select the administration plugin you would like to use below.</span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	foreach($GLOBALS['plugins']['admin']['plugins'] as $name => $plugin)
	{
		if($plugin['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
			continue;
			
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print href('plugin=admin_' . $name); ?>" style="font-size:14px;"><?php print $plugin['name']; ?></a><br /><br />
			Description: <?php print $plugin['description']; ?>
			<br /><br />
		</div>
		<?php
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
	}
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}
