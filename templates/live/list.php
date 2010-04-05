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
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php
	
	foreach($GLOBALS['lists'] as $type => $list)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print href('plugin=list&list=' . $type); ?>" style="font-size:14px;"><?php print $list['name']; ?></a><br /><br />
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
