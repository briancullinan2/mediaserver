<?php


function theme_live_module()
{
	
	$title = 'Configuring: ' . $GLOBALS['modules'][$GLOBALS['output']['configure_module']]['name'];
	if(function_exists('output_' . $GLOBALS['output']['configure_module']))
		$html_title = ' (<a href="' . url('module=' . $GLOBALS['output']['configure_module']) . '">View</a>)';

	theme('header',
		$title,
		$GLOBALS['modules'][$GLOBALS['output']['configure_module']]['description'],
		$title . $html_title
	);
	
	theme('admin_module_configure');
	
	theme('footer');
}

function theme_live_admin_module_configure()
{
	// if the status is avaiable print that out first
	if(isset($GLOBALS['output']['status']))
		print_form_object('status', array(
			'type' => 'fieldset',
			'options' => $GLOBALS['output']['status']
		));
		
	print_form_object('setting', array(
		'action' => url('module=modules&configure_module=' . $GLOBALS['output']['configure_module'], true),
		'options' => $GLOBALS['output']['options'],
		'type' => 'form',
	));
	
}

function theme_live_admin_status()
{
	
	theme('header',
		$GLOBALS['modules']['admin_status']['name'],
		$GLOBALS['modules']['admin_status']['description']
	);
	
	print_form_object('status', array(
		'type' => 'fieldset',
		'options' => $GLOBALS['output']['status']
	));
	?>
	<br />
	<br />
	<br />
	<input type="button" value="Refresh" class="button" style="float:right;" />
	<?php
	
	theme('footer');
}
