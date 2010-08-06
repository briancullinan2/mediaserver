<?php

function setup_live()
{
	// other stuff can be used here
	if(!isset($request['dir']))
		$request['dir'] = '/';
	if(!isset($request['limit']))
		$request['limit'] = 50;
		
	return $request;
}


function theme_live_default()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Module: <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name']; ?></h1>
			<span class="subText">This page requires special parameters that have not been set.  This default page is a placeholder.</span>
	<?php
	
	theme('errors_block');

	?></div><?php

	theme('footer');
}