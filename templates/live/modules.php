<?php

function register_live_modules()
{
	return array(
		'name' => 'Live Modules',
	);
}

function theme_live_modules()
{
	$recommended = array('select', 'list', 'search');
	$required = array('core', 'index', 'login');
	
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Modules</h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_modules']['description']; ?></span>
	<?php
	
	theme('errors');
	
	?>
	<div class="titlePadding"></div>
	<form action="" method="post">
		<table border="0" cellpadding="0" cellspacing="0" class="install">
	<?php

	foreach($GLOBALS['modules'] as $key => $module)
	{
		?>
		<tr>
			<td class="title"><?php print $GLOBALS['modules'][$key]['name']; ?> (<?php print $key; ?>)</td>
			<td>
			<?php
			$module_en = true;
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
					<option value="true" <?php print ($module_en == true)?'selected="selected"':''; ?>>Enabled <?php print in_array($key, $recommended)?'(Recommended)':'(Optional)'; ?></option>
					<option value="false" <?php print ($module_en == false)?'selected="selected"':''; ?>>Disabled</option>
				</select>
			<?php
			}
			?>
		</td>
			<td class="desc">
			<ul>
				<li><?php print $GLOBALS['modules'][$key]['description']; ?></li>
				<li>Choose whether or not to enable the <?php print $GLOBALS['modules'][$key]['name']; ?> module.</li>
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
