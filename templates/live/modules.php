<?php


function theme_live_module()
{
	
	$title = 'Configuring: ' . get_module($GLOBALS['output']['configure_module'], 'name');
	if(module_implements('output', $GLOBALS['output']['configure_module']))
		$html_title = ' (<a href="' . url('module=' . $GLOBALS['output']['configure_module']) . '">View</a>)';

	theme('header',
		$title,
		get_module($GLOBALS['output']['configure_module'], 'description'),
		$title . (isset($html_title)?$html_title:'')
	);
	
	theme('admin_module_configure');
	
	theme('footer');
}

function theme_live_admin_module_configure()
{
	// if the status is avaiable print that out first
	if(isset($GLOBALS['output']['status']))
		theme('form_object', 'status', array(
			'type' => 'fieldset',
			'name' => 'Module Status',
			'collapsible' => true,
			'options' => $GLOBALS['output']['status']
		));
		
	theme('form_object', 'setting', array(
		'action' => url('admin/module/' . $GLOBALS['output']['configure_module'], true),
		'options' => $GLOBALS['output']['options'],
		'type' => 'form',
		'submit' => array(
			'name' => 'save_configuration',
			'value' => 'Save',
		),
		'reset' => array(
			'name' => 'reset_configuration',
			'value' => 'Reset to Defaults',
		),
	));
	
}

function theme_live_info_link($value)
{
	if(is_string($value))
	{
		?><a href="<?php print url($value); ?>"><?php print htmlspecialchars($value); ?></a><?php
	}
	elseif(is_array($value))
	{
		extract($value);
		if(isset($GLOBALS['output']['configure_module']) && $GLOBALS['output']['configure_module'] == 'module' && 
			substr($url, 0, 13) == 'admin/module/')
			theme('form_button', array('action' => 'window.location.href=\'' . url($url) . '\'', 'value' => $text));
		else
		{
			?><a <?php print isset($name)?('name="'.$name.'"'):''; ?> href="<?php print url($url); ?>"><?php theme('info_objects', $text); ?></a><?php
		}
	}
}

function theme_live_admin_status()
{
	
	theme('header',
		get_module('admin_status', 'name'),
		get_module('admin_status', 'description')
	);
	
	theme('form_object', 'status', array(
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
