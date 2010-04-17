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
	<form action="<?php print url('plugin=settings'); ?>" method="get">
	<input type="hidden" name="plugin" value="settings" />
	<?php

	// generate form based on config spec
	foreach($GLOBALS['templates']['plain']['settings'] as $field_name => $config)
	{
		if($config['type'] == 'radio' || $config['type'] == 'checkbox')
		{
			print $config['name'] . ':<br />';
			if(is_array($config['values']))
			{
				// check if array is associative or not
				if(array_keys($config['values']) === array_keys(array_keys($config['values'])))
				{
					// numeric keys
					foreach($config['values'] as $value)
					{
						?><input type="<?php print $config['type']; ?>" value="<?php print $value; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $value; ?><br /><?php
					}
				}
				else
				{
					// named keys
					foreach($config['values'] as $value => $text)
					{
						?><input type="<?php print $config['type']; ?>" value="<?php print $value; ?>" name="<?php print $field_name . (($config['type'] == 'checkbox')?'[]':''); ?>" /><?php print $text; ?><br /><?php
					}
				}
			}
		}
	}
	
	?>
	<input type="submit" name="settings" value="Save" /><input type="submit" name="settings" value="Reset" />
	</form>
	<div class="titlePadding"></div>
	</div>
	<?php

	theme('footer');
}