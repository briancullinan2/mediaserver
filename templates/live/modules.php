<?php

function register_live_modules()
{
	return array(
		'name' => 'Live Modules',
	);
}

function theme_live_modules()
{
	
	theme('header');

	if(isset($GLOBALS['modules'][$GLOBALS['templates']['vars']['configure_module']]))
	{
		?>
		<div class="contentSpacing">
				<h1 class="title">Configuring: <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['configure_module']]['name']; ?></h1>
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
	
	theme('errors');
	
	?>
	<div class="titlePadding"></div>
	<form action="<?php print url('module=admin_modules&configure_module=' . $GLOBALS['templates']['vars']['configure_module']); ?>" method="post">
		<table border="0" cellpadding="0" cellspacing="0" class="install">
		<?php
		foreach($GLOBALS['templates']['vars']['options'] as $name => $config)
		{
			?>
			<tr>
				<td class="title <?php print $config['status']; ?>"><?php print $config['name']; ?> (<?php print $name; ?>)</td>
				<td>
				<?php print_form_objects(array('setting_' . $name => $config)); ?>
				<?php
				if($GLOBALS['templates']['vars']['configure_module'] == 'admin_modules' &&
					isset($GLOBALS['modules'][substr($name, 0, -7)]['settings']) && 
					count($GLOBALS['modules'][substr($name, 0, -7)]['settings']) > 0)
				{
					?><input type="button" value="Configure" onclick="window.location.href='<?php print url('module=admin_modules&configure_module=' . substr($name, 0, -7)); ?>'" /><?php
				}
				elseif($GLOBALS['templates']['vars']['configure_module'] == 'admin_handlers' &&
					isset($GLOBALS['handlers'][substr($name, 0, -7)]['settings']) && 
					count($GLOBALS['handlers'][substr($name, 0, -7)]['settings']) > 0)
				{
					?><input type="button" value="Configure" onclick="window.location.href='<?php print url('module=admin_modules&configure_module=' . substr($name, 0, -7)); ?>'" /><?php
				}
				?>
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
		<input type="submit" name="install_reset" value="Reset to Defaults" class="button" />
		<input type="submit" name="install_save" value="Save" class="button" style="float:right;" />
	</form>
	<?php
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}


function theme_live_status()
{
	
	theme('header');

	?>
	<div class="contentSpacing">
			<h1 class="title"><?php print $GLOBALS['modules']['admin_status']['name']; ?></h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_status']['description']; ?></span>
	<?php
	
	theme('errors');
	
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