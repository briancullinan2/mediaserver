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
	?><ul class=".menu_block"><?php
	
	// loop through top items and create a list
	foreach($GLOBALS['output']['menus'] as $path => $config)
	{
		if(strpos($path, '/') === false)
		{
			?><li class="top_menu"><a class="menu_link" href="<?php print url($path); ?>"><?php print $config['name']; ?></a><br /><?php
			
			// add sub items
			$first = true;
			foreach($GLOBALS['output']['menus'] as $subpath => $subconfig)
			{
				if(
					// only show menus that begin with the current menu
					substr($subpath, 0, strlen($path)) == $path &&
					// do not show the current menu item in this list
					$subpath != $path
				)
				{
					if($first)
					{
						$first = false;
						?><ul><?php
					}
					?><li><a class="menu_link" href="<?php print url($subpath); ?>"><?php print $subconfig['name']; ?></a></li><?php
				}
			}
			if(!$first)
			{
				?></ul><?php
			}
			
			?></li><?php
		}
	}
	
	?>
		<li class="top_menu">Categories<br />
			<ul>
			<?php
			foreach(get_handlers() as $handler => $config)
			{
				$name = $config['name'];
				?><li><a href="<?php print url('select/' . $handler); ?>"><?php echo $name; ?></a></li><?php
			}
			?>
			</ul>
		</li>
	</ul><?php
}


function theme_live_context_menu()
{
	?>
	<ul class="menu" id="menu">
		<li id="option_download"><a href="#">Download File</a></li>
		<li id="option_open"><a href="#">Open</a></li>
		<li id="option_play"><a href="#" onclick="play_selected(); return false;">Play Now</a></li>
		<li id="option_preview"><a href="#">Preview</a></li>
		<li><div class="sep"></div></li>
		<li><a href="#">Download Zip</a></li>
		<li><a href="#">Download Torrent</a></li>
		<li><a href="#">Add to Queue</a></li>
	</ul>
	<?php
}

