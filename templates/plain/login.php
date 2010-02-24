<?php

// template for all logging in actions

if( $error != '' )
{
?>
	<span style="color:#990000; font-weight:bold;"><?php echo $error?></span><br />
<?php
}
?>

<form action="<?php echo HTML_ROOT . '?plugin=login' . (isset($_REQUEST['return'])?('&return=' . $_REQUEST['return']):''); ?>" method="post">

	Username: <input type="text" name="username" value="<?php echo (isset($_REQUEST['username'])?$_REQUEST['username']:"")?>" /><br />
	Password: <input type="password" name="password" value="" /><br />
	<input type="submit" value="Login" /><input type="reset" value="Reset" />
	
</form>
