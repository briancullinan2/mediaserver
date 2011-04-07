<?php


function theme_extjs_login($username = '')
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>	
	<form action="<?php print url('users/login?return=' . urlencode($return)); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print $username; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_extjs_login_block($username = '', $return = '')
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>
	<form action="<?php print url('users/login?return=' . urlencode($return)); ?>" method="post">
		<input class="stndsize" type="text" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('username').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="username" value="<?php print $username; ?>" />
		<input class="stndsize" type="password" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('password').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="password" value="" />
		<input type="submit" value="Login" />
		<span id="username" onmouseover="this.style.visibility='hidden'">Username</span>
		<span id="password" onmouseover="this.style.visibility='hidden'">Password</span>
	</form>
	<?php
}

function theme_extjs_users()
{
	if($GLOBALS['output']['users'] == 'login')
	{
		theme('header',
			'User Login',
			'Enter your username and password to log in.'
		);
		
		theme('login');
		
		theme('footer');
	}
	elseif($GLOBALS['output']['users'] == 'register')
	{
		theme('header',
			'User Registration',
			'Fill out the form below to gain access to additional site features.'
		);
		
		theme('register');
		
		theme('footer');
	}
	else
		theme('default');
}