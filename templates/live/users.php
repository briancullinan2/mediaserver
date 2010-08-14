<?php


function theme_live_login()
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>	
	<form action="<?php echo url('users/login?return=' . urlencode($return)); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_live_login_block()
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>
	<form action="<?php echo url('users/login?return=' . urlencode($return)); ?>" method="post">
		<input class="stndsize" type="text" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('username').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" />
		<input class="stndsize" type="password" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('password').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="password" value="" />
		<input type="submit" value="Login" />
		<span id="username" onmouseover="this.style.visibility='hidden'">Username</span>
		<span id="password" onmouseover="this.style.visibility='hidden'">Password</span>
	</form>
	<?php
}

function theme_live_users()
{
	if($GLOBALS['output']['users'] == 'login')
	{
		theme('header');
		
		?>
		<div class="contentSpacing">
				<h1 class="title">User Login</h1>
				<span class="subText">Enter your username and password to log in.</span>
		<?php
		
		theme('errors_block');
		
		?><div class="titlePadding"></div><?php
		
		theme('login');
		
		?><div class="titlePadding"></div>
		</div><?php
		
		theme('footer');
	}
	elseif($GLOBALS['output']['users'] == 'register')
	{
		theme('header');
		
		?>
		<div class="contentSpacing">
				<h1 class="title">User Registration</h1>
				<span class="subText">Fill out the form below to gain access to additional site features.</span>
		<?php
		
		theme('errors_block');
		
		?><div class="titlePadding"></div><?php
		
		theme('register');
		
		?><div class="titlePadding"></div>
		</div>
		
		<?php
		
		theme('footer');
	}
	else
		theme('default');
}