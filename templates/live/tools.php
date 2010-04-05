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
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	foreach($GLOBALS['plugins']['admin']['plugins']['tools']['plugins'] as $name => $tool)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print generate_href('plugin=admin_tools_' . $name); ?>" style="font-size:14px;"><?php print $tool['name']; ?></a><br />
			Description: <?php print $tool['description']; ?><br /><br />
			<?php
			foreach($tool['subtools'] as $i => $subtool)
			{
				?><a href="<?php print href('plugin=admin_tools_' . $name . '&subtool=' . $i); ?>" style="margin:5px;"><?php print $subtool['name']; ?></a><?php
			}
			?>
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

function theme_live_tools_filetools()
{
	theme('header');

	if(!isset($GLOBALS['templates']['vars']['subtool']))
	{
		?>
		<div class="contentSpacing">
				<h1 class="title">Tools: <?php print $GLOBALS['plugins']['admin']['plugins']['tools']['plugins']['filetools']['name']; ?></h1>
				<span class="subText"><?php print $GLOBALS['plugins']['admin']['plugins']['tools']['plugins']['filetools']['description']; ?></span>
		<?php
		
		theme('errors');
		
		?><div class="titlePadding"></div><?php

		foreach($GLOBALS['plugins']['admin_tools_filetools']['subtools'] as $i => $subtool)
		{
			if($subtool['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
				continue;
				
			?>
			<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
				<a href="<?php print href('plugin=admin_tools_filetools&subtool=' . $i); ?>" style="font-size:14px;"><?php print $subtool['name']; ?></a><br /><br />
				Description: <?php print $subtool['description']; ?>
				<br /><br />
			</div>
			<?php
		}
		
		?><div class="titlePadding"></div>
		</div><?php
	
		theme('footer');
			
	}
}


