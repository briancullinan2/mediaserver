<?php

function register_live_plugins()
{
	return array(
		'name' => 'Live Plugins',
	);
}

function theme_live_plugins()
{
	$recommended = array('select', 'list', 'search');
	$required = array('core', 'index', 'login');
	
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Plugins</h1>
			<span class="subText"><?php print $GLOBALS['plugins']['admin_plugins']['description']; ?></span>
	<?php
	
	theme('errors_block');
	
	?>
	<div class="titlePadding"></div>
	<form action="" method="post">
		<table border="0" cellpadding="0" cellspacing="0" class="install">
	<?php

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
				<li>Choose whether or not to enable the <?php print $GLOBALS['plugins'][$key]['name']; ?> plugin.</li>
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
