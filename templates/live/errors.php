<?php

function theme_live_debug_error($id, $error, $no_code = false)
{
	if($error->code & (E_ALL|E_STRICT))
		$class = 'php_error';
	elseif($error->code & E_DATABASE)
		$class = 'db_error';
	elseif($error->code & E_VERBOSE)
		$class = 'verbose_error';
	else
		$class = '';
		
	if(($error->code & E_DATABASE) > 0 && dependency('code'))
	{
		$code = get_code_html($error->message, 'sql');
		$error->message = $code['HTML'];
		$error->htmlspecialchars = false;
	}
	else
	?>
	<a href="#" class="msg <?php print $class; ?>" onClick="$('#error_<?php print $id; ?>').toggle(); return false;"><?php print (isset($error->time)?('[' . round($error->time, 3) . ']'):'') . ($error->htmlspecialchars ? htmlspecialchars($error->message) : $error->message) . ((isset($error->count) && $error->count > 0)?(' repeated ' . $error->count . ' time(s)'):''); ?></a>
	<?php
	if(!$no_code)
	{
		?>
		<div id="error_<?php print $id; ?>" style="display:none;">
			<code>
				<pre>
	<?php print htmlspecialchars(print_r($error, true)); ?>
				</pre>
			</code>
		</div>
		<?php
	}
}

function theme_live_debug_block()
{
	if($GLOBALS['output']['user']['Username'] != 'guest')
	{
		?><div id="debug" class="debug hide"><?php
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			theme('debug_error', $i, $error);
		}
		
		if(isset($GLOBALS['output']['requests']) && count($GLOBALS['output']['requests']) > 0)
		{
			foreach($GLOBALS['output']['requests'] as $j => $request)
			{
				?>
				<a href="#" class="msg" onclick="$('#request_<?php print $j; ?>').toggle(); return false;"><?php print lang('Request made on ', 'debug request made on'); ?> <?php print $request['Time']; ?></a>
				<div id="request_<?php print $j; ?>" style="display:none;"><?php
				$errors = unserialize(gzinflate($request['Errors']));
				foreach($errors['debug'] as $i => $error)
				{
					theme('debug_error', $i, $error, true);
				}
				?></div><?php
			}
		}
		
		?>
		<a id="hide_link" href="#"><?php print lang('Show / Hide', 'debug show hide'); ?></a>
		<script type="text/javascript">
			$('#hide_link').click(function () {
				$('#debug').toggleClass('hide');
				return false;
			})
		</script>
		</div>
		<?php
	}
	else
	{
		if(isset($GLOBALS['output']['users']) && $GLOBALS['output']['users'] == 'login')
		{
			?><div id="debug" class="debug"><p><?php print lang('Administrators: Log in below to select debug options.', 'debug admin login'); ?></p></div><?php
		}
		else
		{
			?><div id="debug" class="debug">
			<form action="<?php print url('users/login?return=' . urlencode($GLOBALS['output']['get'])); ?>" method="post">
			<p>
				<?php print lang('Administrators: Log in to select debug options.', 'admin login to debug'); ?> <?php print lang('Username', 'username'); ?>: <input type="text" name="username" value="" />
				<?php print lang('Password', 'password'); ?>: <input type="password" name="password" value="" />
				<input type="submit" value="<?php print lang('Login', T_IN_ATTRIBUTE, 'login'); ?>" />
				<input type="reset" value="<?php print lang('Reset', T_IN_ATTRIBUTE, 'reset'); ?>" />
			</p>
			</form>
			</div>
			<?php
		}
	}
}

function theme_live_errors_block($errors_only = false)
{
	$error_list = array('warn_errors', 'user_errors', 'note_errors');
	
	$has_errors = false;
	foreach($error_list as $i => $errors)
	{
		if(count($GLOBALS[$errors]) > 0)
		{
			$has_errors = true;
			?><div id="<?php print (isset($GLOBALS['output']['errors_only']) && 
				$GLOBALS['output']['errors_only'])?($errors . '_only'):$errors; ?>"><?php
			foreach($GLOBALS[$errors] as $i => $error)
			{
				if($i == 5)
				{
					?><div id="inner_<?php print $errors; ?>" class="error hide"><?php
				}
				?><b><?php print theme('info_objects', $error); ?></b><br /><?php
			}
			if(count($GLOBALS[$errors]) > 5)
			{
				?></div>
				And <?php print count($GLOBALS[$errors]) - 5; ?> more: <a href="#" onClick="if(!this.hidden) { document.getElementById('inner_<?php print $errors; ?>').className='error hide'; this.hidden=true; this.innerHTML = 'Un Hide'; } else { document.getElementById('inner_<?php print $errors; ?>').className='error'; this.hidden=false; this.innerHTML = 'Hide'; }">Un Hide</a>
				<?php
			}
			?></div><?php
			
			$GLOBALS[$errors] = array();
		}
	}
	
	if($has_errors)
	{
		?><div class="titlePadding"></div><?php
	}
	
	if(!$errors_only)
	{
		?>
		<div id="tmp_errors"></div>
		<script type="text/javascript">
			$(document).ready(function() {
				$.get('<?php print url('errors_only', true); ?>',function(data, status, xhr){
					$('#tmp_errors').html(data);
					
					$('#warn_errors').append($('#warn_errors_only'));
					$('#user_errors').append($('#user_errors_only'));
					$('#note_errors').append($('#note_errors_only'));
					
					$('#tmp_errors').remove();
				}, 'html');
			});
		</script>
		<?php
	}
}

