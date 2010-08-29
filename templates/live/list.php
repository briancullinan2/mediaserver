<?php


function theme_live_list()
{
	theme('header',
		'Lists',
		'Select the type of list you would like to view below.'
	);
	
	foreach($GLOBALS['lists'] as $type => $list)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url('list/' . $type); ?>" style="font-size:14px;"><?php print $list['name']; ?></a><br /><br />
			Format: <?php print $list['encoding']; ?><br />
			Extension: <?php print $type; ?>
			<br /><br />
		</div>
		<?php
	}
	
	theme('footer');
}

function theme_live_list_block()
{
	?>
	<div class="list_block">
	<?php 
	if($GLOBALS['output']['user']['Username'] == 'guest') 
	{
		theme('login_block');
	}
	else
	{
		?>
		User Directory
		<?php
	}
	?>
	</div>
	<?php
}
