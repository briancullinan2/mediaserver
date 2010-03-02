<?php

// template for all logging in actions
if( count($GLOBALS['templates']['vars']['user_errors']) > 0 )
{
?>
	<span style="color:#990000; font-weight:bold;"><?php foreach($GLOBALS['templates']['vars']['user_errors'] as $i => $error) { echo $error->message . '<br />'; } ?></span><br />
<?php
}
?>

<form action="<?php echo generate_href((isset($GLOBALS['templates']['vars']['return'])?('return=' . $GLOBALS['templates']['vars']['return']):''), 'login'); ?>" method="post">

	Username: <input type="text" name="username" value="<?php echo $GLOBALS['templates']['vars']['username']; ?>" /><br />
	Password: <input type="password" name="password" value="" /><br />
	<input type="submit" value="Login" /><input type="reset" value="Reset" />
	
</form>
