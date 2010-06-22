<?php


function register_live_list()
{
	return array(
		'name' => 'Live Lists',
	);
}


function theme_live_list()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Lists</h1>
			<span class="subText">Select the type of list you would like to view below.</span>
	<?php
	
	theme('errors_block');
	
	?><div class="titlePadding"></div><?php
	
	foreach($GLOBALS['lists'] as $type => $list)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url('module=list&list=' . $type); ?>" style="font-size:14px;"><?php print $list['name']; ?></a><br /><br />
			Format: <?php print $list['encoding']; ?><br />
			Extension: <?php print $type; ?>
			<br /><br />
		</div>
		<?php
	}
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_list_block()
{
	?>
	<div class="list_block">
	<?php 
	if($GLOBALS['templates']['vars']['user']['Username'] == 'guest') 
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
