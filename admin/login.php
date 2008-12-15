<?php

// a simple login script for the admin section

require_once dirname(__FILE__) . '/../include/common.php';

$error = '';

if( loggedIn() )
{
	if( isset($_REQUEST['return']) )
	{
		header('Location: ' . $_REQUEST['return']);
		exit();
	}
		
	$error = 'Already logged in as admin.';
}
else
{

	if( isset($_REQUEST['username']) && isset($_REQUEST['password']) )
	{
		if( $_REQUEST['username'] == ADMIN_USER && $_REQUEST['password'] == ADMIN_PASS )
		{
			$_SESSION['username'] = $_REQUEST['username'];
			$_SESSION['password'] = $_REQUEST['password'];
			
			if( isset($_REQUEST['return']) )
			{
				header('Location: ' . $_REQUEST['return']);
				exit();
			}
			
			$error = 'Already logged in as admin.';
		}
		else
		{
			$error = 'Wrong username or password.';
		}
	}
	else
	{
		$error = 'You must enter a username and password to access this section.';
	}
	
}

if( $error != '' )
{
?>
	<span style="color:#990000; font-weight:bold;"><?=$error?></span><br />
<?
}
?>

<form action="" method="post">

	Username: <input type="text" name="username" value="<?=(isset($_REQUEST['username'])?$_REQUEST['username']:"")?>" /><br />
	Password: <input type="password" name="password" value="" /><br />
	<input type="submit" value="Login" /><input type="reset" value="Reset" />
	
</form>