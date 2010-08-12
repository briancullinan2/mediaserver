<?php

function theme__confirmation()
{
	theme('header');
	
	?>Thanks for signing up!<?php
	
	theme('footer');
}
function theme__login()
{
	theme('header');
	
	if($GLOBALS['output']['user']['Username'] != 'guest' || setting('database_enable') == false)
		theme('logout_block');
	else
		theme('login_block');
	
	theme('footer');
}

function theme__login_block()
{
	if($GLOBALS['output']['user']['Username'] != 'guest' || setting('database_enable') == false)
	{
		theme('logout_block');
		return;
	}
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>	
	<form action="<?php echo url('users/login?return=' . urlencode($return)); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme__logout_block()
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>
	<form action="<?php echo url('users/logout?return=' . urlencode($return)); ?>" method="post">
		<input type="submit" value="Logout" />
	</form>
	<?php
}


function theme__menu_block()
{
	if(isset($GLOBALS['output']['menus']))
	{
		?>
		Menu:<br />
		<ul>
		<?php
		
		foreach($GLOBALS['output']['menus'] as $path => $config)
		{
			// this path actually leads to output as is, no need to validate it by using url()
			if($config['type'] == MENU_NORMAL)
			{
				?><li><a href="<?php print $path; ?>"><?php echo $config['name']; ?></a></li><?php
			}
		}
		
		?></ul><?php
	}
}

function theme__module()
{
	theme('header');

	// if the status is avaiable print that out first
	if(isset($GLOBALS['output']['status']))
		print_form_object('status', array(
			'type' => 'fieldset',
			'options' => $GLOBALS['output']['status']
		));
		
	print_form_object('setting', array(
		'action' => url('admin/modules/' . $GLOBALS['output']['configure_module'], true),
		'options' => $GLOBALS['output']['options'],
		'type' => 'form',
	));
	
	theme('footer');
}

function theme__register()
{
	theme('header');
	
	?>	
	<form action="<?php echo url('users/register'); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" /><br />
		E-mail: <input type="text" name="email" value="<?php print isset($GLOBALS['output']['email'])?$GLOBALS['output']['email']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Register" /><input type="reset" value="Reset" />
		
	</form>
	<?php
	
	theme('footer');
}

function theme__scripts($scripts)
{
	if(!isset($scripts))
		return;
		
	if(is_string($scripts)) $scripts = array($scripts);
	
	foreach($scripts as $link)
	{
		?>
		<script language="javascript" type="text/javascript" src="<?php print url($link); ?>"></script>
		<?php
	}
}

function theme__styles($styles)
{
	if(!isset($styles))
		return;
		
	if(is_string($styles)) $styles = array($styles);
	
	foreach($styles as $link)
	{
		?>
		<link rel="stylesheet" href="<?php print url($link); ?>" type="text/css"/>
		<?php
	}
}


function theme__template_block()
{
	?>
	<br /><br />Select a Template:<br />
	<?php
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print url('template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><br /><?php
		}
	}
}

function theme__template()
{
	theme('header');
	
	theme('template_block');

	theme('footer');
}

function theme__users()
{
	switch($GLOBALS['output']['users'])
	{
		case 'login':
			theme('login');
		break;
	}
}
