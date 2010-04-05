<?php

function register_live_plugins()
{
	return array(
		'name' => 'Live Plugins',
	);
}

function theme_live_plugins()
{
	$GLOBALS['templates']['vars']['title'] = 'Plugins';
	$GLOBALS['templates']['vars']['subtext'] = $GLOBALS['plugins']['admin_plugins']['description'];
	
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php print isset($GLOBALS['templates']['vars']['title'])?$GLOBALS['templates']['vars']['title']:HTML_NAME; ?></title>
	<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
	<?php theme('styles', $GLOBALS['templates']['vars']['styles']); ?>
	<?php theme('scripts', $GLOBALS['templates']['vars']['scripts']); ?>
	<link rel="stylesheet" href="<?php print href('plugin=admin_install&install_image=style'); ?>" type="text/css"/>
	</head>
	<?php
	
	theme('body');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Plugins</h1>
			<span class="subText"><?php print $GLOBALS['plugins']['admin_plugins']['description']; ?></span>
	<?php
	
	theme('errors');
	
	?>
	<div class="titlePadding"></div>
	<form action="" method="post">
		<table border="0" cellpadding="0" cellspacing="0">
	<?php

	
	?><div class="titlePadding"></div><?php
	
	$recommended = array('select', 'list', 'search');
	$required = array('core', 'index', 'login');
	foreach($GLOBALS['plugins'] as $key => $plugin)
	{
		?>
		<tr>
			<td class="title"><?php print $GLOBALS['plugins'][$key]['name']; ?> (<?php print $key; ?>)</td>
			<td>
			<?php
			$plugin_en = true;
			if(in_array($key, $required))
			{
			?>
			<select disabled="disabled">
					<option>Enabled (Required)</option>
				</select>
			<?php
			}
			else
			{
			?>
			<select name="<?php echo strtoupper($key); ?>_ENABLE">
					<option value="true" <?php print ($plugin_en == true)?'selected="selected"':''; ?>>Enabled <?php print in_array($key, $recommended)?'(Recommended)':'(Optional)'; ?></option>
					<option value="false" <?php print ($plugin_en == false)?'selected="selected"':''; ?>>Disabled</option>
				</select>
			<?php
			}
			?>
		</td>
			<td class="desc">
			<ul>
				<li><?php print $GLOBALS['plugins'][$key]['description']; ?></li>
				<li>Choose whether or not to select the <?php print $GLOBALS['plugins'][$key]['name']; ?> plugin.</li>
			</ul>
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
		<div class="titlePadding"></div>
	</div>
	<?php
	
	theme('footer');
}
