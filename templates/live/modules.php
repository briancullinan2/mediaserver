<?php

function register_live_modules()
{
	return array(
		'name' => 'Live Modules',
	);
}

function theme_live_admin_modules()
{
	
	theme('header');

	if(isset($GLOBALS['modules'][$GLOBALS['templates']['vars']['configure_module']]))
	{
		?>
		<div class="contentSpacing">
				<h1 class="title">Configuring: <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['configure_module']]['name']; ?>
				<?php
				if(function_exists('output_' . $GLOBALS['templates']['vars']['configure_module']))
				{
					?>(<a href="<?php print url('module=' . $GLOBALS['templates']['vars']['configure_module']); ?>">View</a>)<?php
				}
				?></h1>
				<span class="subText"><?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['configure_module']]['description']; ?></span>
		<?php
	}
	else
	{
		?>
		<div class="contentSpacing">
				<h1 class="title">Configuring: <?php print $GLOBALS['handlers'][$GLOBALS['templates']['vars']['configure_module']]['name']; ?></h1>
				<span class="subText"><?php print $GLOBALS['handlers'][$GLOBALS['templates']['vars']['configure_module']]['description']; ?></span>
		<?php
	}
	
	theme('errors_block');
	
	?><div class="titlePadding"></div><?php
	
	theme('admin_modules_configure');
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_admin_modules_configure()
{
	// if the status is avaiable print that out first
	if(isset($GLOBALS['templates']['vars']['status']))
		print_form_objects($GLOBALS['templates']['vars']['status']);
		
	print_form_objects(array(
		'setting' => array(
			'action' => url('module=admin_modules&configure_module=' . $GLOBALS['templates']['vars']['configure_module']),
			'options' => $GLOBALS['templates']['vars']['options'],
			'type' => 'form',
		),
	));
	
}

function theme_live_admin_status()
{
	
	theme('header');

	?>
	<div class="contentSpacing">
			<h1 class="title"><?php print $GLOBALS['modules']['admin_status']['name']; ?></h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_status']['description']; ?></span>
	<?php
	
	theme('errors_block');
	
	?>
	<div class="titlePadding"></div>
	<table border="0" cellpadding="0" cellspacing="0" class="install">
	<?php
	foreach($GLOBALS['templates']['vars']['status'] as $name => $config)
	{
		?>
		<tr>
			<td class="title <?php print $config['status']; ?>"><?php print $config['name']; ?> (<?php print $name; ?>)</td>
			<td>
			<?php print_form_objects(array($name => $config)); ?>
			</td>
			<td class="desc">
			<?php print_info_objects($config['description']); ?>
			</td>
		</tr>
		<?php
	}
	?>
	</table>
	<br />
	<br />
	<br />
	<input type="button" value="Refresh" class="button" style="float:right;" />
	<?php
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}