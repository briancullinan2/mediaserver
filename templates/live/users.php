<?php


function theme_live_login($username = '')
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>	
	<form action="<?php print url('users/login?return=' . urlencode($return)); ?>" method="post">
		<p>
		Username: <input type="text" name="username" value="<?php print $username; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		</p>
	</form>
	<?php
}

function theme_live_login_block($username = '', $return = '')
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>
	<form action="<?php print url('users/login?return=' . urlencode($return)); ?>" method="post">
		<p>
		<input class="stndsize" id="login_block_username" type="text" name="username" value="<?php print $username; ?>" />
		<input class="stndsize" id="login_block_password" type="password" name="password" value="" />
		<script type="text/javascript">
		// <!--
			$('#login_block_username').focus(function () {
				if($(this).val() == 'Username' && $(this).css('color') == 'grey')
					$(this).val('').css('color', 'black');
			}).blur(function() {
				if($(this).val() == '')
					$(this).val('Username').css('color', 'grey');
			}).mouseover(function () {
				$(this).trigger('focus');	
			}).mouseout(function () {
				$(this).trigger('blur');	
			})
			
			$('#login_block_password').focus(function () {
				if($(this).val() == 'Password' && $(this).css('color') == 'grey')
					$(this).val('').css('color', 'black');
			}).blur(function() {
				if($(this).val() == '')
					$(this).val('Password').css('color', 'grey');
			}).mouseover(function () {
				$(this).trigger('focus');	
			}).mouseout(function () {
				$(this).trigger('blur');	
			})
			
			$('#login_block_username, #login_block_password').trigger('blur');
		// -->
		</script>
		<input type="submit" value="Login" />
		</p>
	</form>
	<?php
}

function theme_live_users()
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