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
		
	?>
	<a href="#" class="msg <?php print $class; ?>" onClick="$('#error_<?php print $id; ?>').toggle(); return false;"><?php print (isset($error->time)?('[' . $error->time . ']'):'') . htmlspecialchars($error->message) . ((isset($error->count) && $error->count > 0)?(' repeated ' . $error->count . ' time(s)'):''); ?></a>
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
				<a href="#" class="msg" onClick="$('#request_<?php print $j; ?>').toggle(); return false;">Request made on <?php print $request['Time']; ?></a>
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
		<a id="hide_link" href="#" onClick="if(this.hidden || typeof this.hidden == 'undefined') { document.getElementById('debug').className='debug'; this.hidden=false; this.innerHTML = 'Hide'; } else { document.getElementById('debug').className='debug hide'; this.hidden=true; this.innerHTML = 'Show'; } return false;">Show</a>
		</div>
		<?php
	}
	else
	{
		if(isset($GLOBALS['output']['users']) && $GLOBALS['output']['users'] == 'login')
		{
			?><div id="debug" class="debug"><p>Administrators: Log in below to select debug options.</p></div><?php
		}
		else
		{
			?><div id="debug" class="debug">
			<form action="<?php print url('users/login?return=' . urlencode($GLOBALS['output']['get'])); ?>" method="post">
			<p>
				Administrators: Log in to select debug options. Username: <input type="text" name="username" value="" />
				Password: <input type="password" name="password" value="" />
				<input type="submit" value="Login" />
				<input type="reset" value="Reset" />
			</p>
			</form>
			</div>
			<?php
		}
	}
}

function theme_live_errors_block()
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
	
	if(!isset($GLOBALS['output']['errors_only']) || !$GLOBALS['output']['errors_only'])
	{
		?>
		<div id="tmp_errors"></div>
		<script type="text/javascript">
			$(document).ready(function() {
				$.get('<?php print url('select?errors_only=true', true); ?>',function(data, status, xhr){
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

