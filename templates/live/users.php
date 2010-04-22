<?php

function register_live_users()
{
	return array(
		'name' => 'User Pages',
	);
}

function theme_live_login()
{
	?>	
	<form action="<?php echo url('plugin=users&users=login' . (isset($GLOBALS['templates']['vars']['return'])?('&return=' . urlencode($GLOBALS['templates']['vars']['return'])):'')); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme_live_users()
{
	if($GLOBALS['templates']['vars']['users'] == 'login')
	{
		theme('header');
		
		?>
		<div class="contentSpacing">
				<h1 class="title">User Login</h1>
				<span class="subText">Enter your username and password to log in.</span>
		<?php
		
		theme('errors');
		
		?><div class="titlePadding"></div><?php
		
		theme('login');
		
		?><div class="titlePadding"></div>
		</div><?php
		
		theme('footer');
	}
	elseif($GLOBALS['templates']['vars']['users'] == 'register')
	{
		theme('header');
		
		?>
		<div class="contentSpacing">
				<h1 class="title">User Registration</h1>
				<span class="subText">Fill out the form below to gain access to additional site features.</span>
		<?php
		
		theme('errors');
		
		?><div class="titlePadding"></div><?php
		
		theme('register');
		
		?><div class="titlePadding"></div>
		</div>
		
		<?php
		
		theme('footer');
	}
}