<?php


function theme_live_debug_block()
{
	if($GLOBALS['output']['user']['Username'] != 'guest')
	{
		?><div id="debug" class="debug hide"><?php
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			if(substr($error->message, 0, 10) == 'PHP ERROR:')
				$class = 'php';
			elseif(substr($error->message, 0, 9) == 'DB ERROR:')
				$class = 'db';
			elseif(substr($error->message, 0, 8) == 'VERBOSE:')
				$class = 'verbose';
			else
				$class = '';
				
			?>
			<a href="#" class="msg <?php print $class; ?>" onClick="$('#error_<?php print $i; ?>').toggle(); return false;"><?php print (isset($error->time)?('[' . $error->time . ']'):'') . htmlspecialchars($error->message) . (isset($error->count)?(' repeated ' . $error->count . ' time(s)'):''); ?></a>
			<div id="error_<?php print $i; ?>" style="display:none;">
				<code>
					<pre>
<?php print htmlspecialchars(print_r($error, true)); ?>
					</pre>
				</code>
			</div>
			<?php
		}
		
		$GLOBALS['debug_errors'] = array();
		
		?>
		<a id="hide_link" href="#" onClick="if(this.hidden == false) { document.getElementById('debug').className='debug hide'; this.hidden=true; this.innerHTML = 'Show'; } else { document.getElementById('debug').className='debug'; this.hidden=false; this.innerHTML = 'Hide'; } return false;">Show</a>
		</div>
		<?php
	}
	else
	{
		if(isset($GLOBALS['output']['users']) && $GLOBALS['output']['users'] == 'login')
		{
			?><div id="debug" class="debug">Administrators: Log in below to select debug options.</div><?php
		}
		else
		{
			?><div id="debug" class="debug">
			<form action="<?php print url('users/login?return=' . urlencode($GLOBALS['output']['get'])); ?>" method="post">
				Administrators: Log in to select debug options. Username: <input type="text" name="username" value="" />
				Password: <input type="password" name="password" value="" />
				<input type="submit" value="Login" />
				<input type="reset" value="Reset" />
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
				$GLOBALS['output']['errors_only'] == true)?($errors . '_only'):$errors; ?>"><?php
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
				And <?php print count($GLOBALS[$errors]) - 5; ?> more: <a href="#" onClick="if(this.hidden == false) { document.getElementById('inner_<?php print $errors; ?>').className='error hide'; this.hidden=true; this.innerHTML = 'Un Hide'; } else { document.getElementById('inner_<?php print $errors; ?>').className='error'; this.hidden=false; this.innerHTML = 'Hide'; }">Un Hide</a>
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
	
	if(!isset($GLOBALS['output']['errors_only']) || $GLOBALS['output']['errors_only'] == false)
	{
		?>
		<div id="tmp_errors"></div>
		<script language="javascript">
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

