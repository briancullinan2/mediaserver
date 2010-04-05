<?php

function theme_plain_login()
{
	?>	
	<form action="<?php echo generate_href('plugin=users&users=login' . (isset($GLOBALS['templates']['vars']['return'])?('&return=' . urlencode($GLOBALS['templates']['vars']['return'])):'')); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_plain_register()
{
	?>	
	<form action="<?php echo generate_href('plugin=users&users=register'); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" /><br />
		E-mail: <input type="text" name="email" value="<?php print isset($GLOBALS['templates']['vars']['email'])?$GLOBALS['templates']['vars']['email']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Register" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_plain_confirmation()
{
	?>Thanks for signing up!<?php
}