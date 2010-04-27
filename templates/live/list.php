<?php


function register_live_list()
{
	return array(
		'name' => 'Live Lists',
	);
}


function theme_live_list()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Lists</h1>
			<span class="subText">Select the type of list you would like to view below.</span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php
	
	foreach($GLOBALS['lists'] as $type => $list)
	{
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url('module=list&list=' . $type); ?>" style="font-size:14px;"><?php print $list['name']; ?></a><br /><br />
			Format: <?php print $list['encoding']; ?><br />
			Extension: <?php print $type; ?>
			<br /><br />
		</div>
		<?php
	}
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_list_block()
{
	?>
	<div class="list_block">
	<?php 
	if($GLOBALS['templates']['vars']['user']['Username'] == 'guest') 
	{
		theme('login_block');
	}
	else
	{
		?>
		User Directory
		<?php
	}
	?>
	</div>
	<?php
}

function theme_live_login_block()
{
	if(isset($GLOBALS['templates']['vars']['return']))
		$return = $GLOBALS['templates']['vars']['return'];
	else
		$return = $GLOBALS['templates']['vars']['get'];
	?>
	<form action="<?php echo url('module=users&users=login&return=' . urlencode($return)); ?>" method="post">
		<input class="stndsize" type="text" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('username').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="username" value="<?php print isset($GLOBALS['templates']['vars']['username'])?$GLOBALS['templates']['vars']['username']:''; ?>" />
		<input class="stndsize" type="password" onmouseout="if(this.value=='' && !this.hasfocus) document.getElementById('password').style.visibility='visible'" onblur="this.hasfocus=false; this.onmouseout();" onfocus="this.hasfocus=true" name="password" value="" />
		<input type="submit" value="Login" />
		<span id="username" onmouseover="this.style.visibility='hidden'">Username</span>
		<span id="password" onmouseover="this.style.visibility='hidden'">Password</span>
	</form>
	<?php
}