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
		<?php print lang('Username', 'username'); ?>: <input type="text" name="username" value="<?php print $username; ?>" /><br />
		<?php print lang('Password', 'password'); ?>: <input type="password" name="password" value="" /><br />
		<input type="submit" value="<?php print lang('Login', T_IN_ATTRIBUTE, 'login'); ?>" /><input type="reset" value="<?php print lang('Reset', T_IN_ATTRIBUTE, 'reset'); ?>" />
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
			var username = "<?php print lang('Username', T_NO_SPAN, array('id' => 'username_func'), 'login username'); ?>";
			function $username_func(translation)
			{
				if($('#login_block_username').val() == 'Username' && $('#login_block_username').css('color') == 'grey')
					$('#login_block_username').val(translation);
				username = translation;
			}
			$('#login_block_username').focus(function () {
				if($(this).val() == username && $(this).css('color') == 'grey')
					$(this).val('').css('color', 'black');
			}).blur(function() {
				if($(this).val() == '')
					$(this).val(username).css('color', 'grey');
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
		<input type="submit" value="<?php print lang('Login', T_IN_ATTRIBUTE, 'login'); ?>" />
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