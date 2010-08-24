<?php

function theme_live_menu()
{
	theme('header', 
		'Menu: ' . $GLOBALS['output']['current_menu']['name'],
		$GLOBALS['output']['current_menu']['description']
	);
	
	foreach($GLOBALS['output']['menu'] as $path => $config)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url($path); ?>" style="font-size:14px;"><?php print $config['name']; ?></a><br /><br />
			Description: <?php print $config['description']; ?>
			<br /><br />
		</div>
		<?php
	}

	theme('footer');
}

function theme_live_menu_block()
{
	
}