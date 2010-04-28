<?php

function register_live_settings()
{
	return array(
		'name' => 'Live Settings'
	);
}

function theme_live_settings()
{
	$GLOBALS['templates']['vars']['selector'] = false;
	
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Settings</h1>
			<span class="subText">Set display options below.</span>
	<?php
	
	theme('errors');
	
	?>
	<div class="titlePadding"></div>
	<form action="<?php print url('module=settings'); ?>" method="get">
	<input type="hidden" name="module" value="settings" />
	
	<?php print_form_objects($GLOBALS['templates']['plain']['settings']); ?>
	
	<input type="submit" name="settings" value="Save" /><input type="submit" name="settings" value="Reset" />
	</form>
	<div class="titlePadding"></div>
	</div>
	<?php

	theme('footer');
}