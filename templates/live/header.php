<?php

function theme_live_head($title)
{
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print $title; ?></title>
<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
<?php if(isset($GLOBALS['output']['styles'])) theme('styles', $GLOBALS['output']['styles']); ?>
<?php if(isset($GLOBALS['output']['scripts'])) theme('scripts', $GLOBALS['output']['scripts']); ?>
<script language="javascript">
var loaded = false;
<?php
if(isset($GLOBALS['output']['selector']) && $GLOBALS['output']['selector'] == false)
{
	?>var selector_off = true;<?php
}
else
{
	?>var selector_off = false;<?php
}
?>
</script>
</head>
	<?php
}

function theme_live_header()
{
	register_style('template/live/css/types.css');
	register_style('template/live/css/search.css');
	register_style('template/live/css/layout.css');
	register_style('template/live/css/menu.css');
	register_style('template/live/css/files.css');
	register_style('template/live/css/debug.css');
	register_script('template/live/js/jquery.js');
	register_script('template/live/js/dragclick.js');
	
	$title = htmlspecialchars($GLOBALS['modules'][$GLOBALS['output']['module']]['name']) . ' : ' . setting('html_name');
	
	theme('head', $title);
	
	theme('body');
}

function theme_live_debug_block()
{
	?>
	<script type="application/javascript" language="javascript">
		function toggleDiv(id) {
			if(document.getElementById(id).style.display == 'none') {
				document.getElementById(id).style.display = 'block';
			} else {
				document.getElementById(id).style.display = 'none';
			}
		}
	</script>
	<?php
	if($GLOBALS['output']['user']['Username'] != 'guest')
	{
		?><div id="debug" class="debug hide"><?php
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			?>
			<a class="msg" onClick="toggleDiv('error_<?php print $i; ?>')"><?php print htmlspecialchars($error->message); ?></a><br />
			<div id="error_<?php print $i; ?>" style="display:none;">
				<code>
					<pre>
<?php print htmlspecialchars(print_r($error, true)); ?>
					</pre>
				</code>
			</div>
			<?php
		}
		
		// clear debug errors
		$GLOBALS['debug_errors'] = array();
		
		?>
		<a id="hide_link" href="javascript:return true;" onClick="if(this.hidden == false) { document.getElementById('debug').className='debug hide'; this.hidden=true; this.innerHTML = 'Show'; } else { document.getElementById('debug').className='debug'; this.hidden=false; this.innerHTML = 'Hide'; }">Show</a>
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

function theme_live_breadcrumbs()
{
	if($GLOBALS['output']['module'] != 'select' && $GLOBALS['output']['module'] != 'index')
	{
		?>
		<li><a href="<?php print url('select/dir//'); ?>"><?php print setting('html_name'); ?></a></li>
		<li><img src="<?php print url('template/live/images/carat.gif'); ?>" class="crumbsep"></li>
		<?php
		// break up the module by the underscores
		$crumbs = split('_', $GLOBALS['output']['module']);
		$current = '';
		foreach($crumbs as $i => $crumb)
		{
			$current .= (($current != '')?'_':'') . $crumb;
			if(isset($GLOBALS['modules'][$current]))
			{
				?>
				<li><a href="<?php print url($current); ?>"><?php print $GLOBALS['modules'][$current]['name']; ?></a></li>
				<?php
				if($i != count($crumbs) - 1)
				{
					?>
					<li><img src="<?php print url('template/images/carat.gif'); ?>" class="crumbsep"></li>
					<?php
				}
			}
		}
	}
	else
	{
		$crumbs = isset($GLOBALS['output']['dir'])?split('/', $GLOBALS['output']['dir']):array('');
		if($crumbs[count($crumbs)-1] == '')
			unset($crumbs[count($crumbs)-1]);
		$path = '';
		$count = 0;
		foreach($crumbs as $i => $text)
		{
			$path .= $text;
			if($count == 0)
			{
				?>
				<li><a href="<?php print url('select?handler=' . (handles($path, $GLOBALS['output']['handler'])?$GLOBALS['output']['handler']:'files') . '&dir=' . urlencode('/')); ?>"><?php print setting('html_name'); ?></a></li>
				<li><img src="<?php print url('template/live/images/carat.gif'); ?>" class="crumbsep"></li>
				<?php
			}
			elseif($count == count($crumbs)-1)
			{
				?><li><?php print $text; ?></li><?php
			}
			else
			{
				?>
				<li><a href="<?php print url('select?handler=' . (handles($path, $GLOBALS['output']['handler'])?$GLOBALS['output']['handler']:'files') . '&dir=' . urlencode($path . '/')); ?>"><?php print $text; ?></a></li>
				<li><img src="<?php print url('template/live/images/carat.gif'); ?>" class="crumbsep"></li>
				<?php
			}
			$path .= '/';
			
			$count++;
		}
	}
}

function theme_live_template_block()
{
	?><div class="template_box"><?php
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print url('select?template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><?php
		}
	}
	?></div><?php
}

function live_get_theme_color()
{
	if(!isset($GLOBALS['output']['files_count']))
		return 'files';
	if($GLOBALS['output']['audio_count'] > $GLOBALS['output']['image_count'] &&
		$GLOBALS['output']['audio_count'] > $GLOBALS['output']['video_count'] &&
		$GLOBALS['output']['audio_count'] > $GLOBALS['output']['files_count']
	)
		$theme = 'audio';
	elseif($GLOBALS['output']['image_count'] > $GLOBALS['output']['files_count'] &&
		$GLOBALS['output']['image_count'] > $GLOBALS['output']['video_count'] &&
		$GLOBALS['output']['image_count'] > $GLOBALS['output']['audio_count']
	)
		$theme = 'image';
	elseif($GLOBALS['output']['video_count'] > $GLOBALS['output']['files_count'] &&
		$GLOBALS['output']['video_count'] > $GLOBALS['output']['image_count'] &&
		$GLOBALS['output']['video_count'] > $GLOBALS['output']['audio_count']
	)
		$theme = 'video';
	else
		$theme = 'files';
		
	return $theme;
}

function theme_live_body()
{
	$theme = live_get_theme_color();
?>
<script language="javascript">
	$(document).ready(function() {
		init();
	});
</script>
<body onmousemove="setSelector()">
<?php theme('list_block'); ?>
<div id="bodydiv">
	<div id="sizer">
		<div id="expander">
			<table id="header" cellpadding="0" cellspacing="0" style="background-color:<?php print ($theme == 'audio')?'#900':(($theme == 'image')?'#990':(($theme == 'video')?'#093':'#06A')); ?>;">
				<tr>
					<td id="siteTitle"><a href="<?php print url('select'); ?>"><?php print setting('html_name'); ?></a></td>
					<td>
						<?php if(dependency('search') != false) theme('search_block'); ?>
					</td>
					<td id="templates"><?php theme('template_block'); ?></td>
				</tr>
			</table>
<?php if(setting('debug_mode')) theme('debug_block'); ?>
			<?php if(dependency('language') != false) theme('language_block'); ?>
			<div id="container">
				<table width="100%" cellpadding="5" cellspacing="0">
					<tr>
						<td>
							<div id="breadcrumb">
								<ul>
<?php
theme('breadcrumbs');
?>
								</ul>
							</div>
						</td>
					</tr>
				</table>
				<div id="content" onmousedown="return startDrag(event);" onmouseup="endDrag();return false;">
					<div id="selector" style="display:none;"></div>
					<ul class="menu" id="menu">
						<li id="option_download"><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';"><b>Download File</b></a></li>
						<li id="option_open"><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';"><b>Open</b></a></li>
						<li><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';">Play Now</a></li>
						<li><div class="sep"></div></li>
						<li><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';">Download Zip</a></li>
						<li><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';">Download Torrent</a></li>
						<li><a href="#" onMouseOut="this.className = '';" onMouseOver="this.className = 'itemSelect';">Add to Queue</a></li>
					</ul>
					<div class="menuShadow" id="shadow"></div>
					<table id="main" cellpadding="0" cellspacing="0">
						<tr>
							<td class="sideColumn"></td>
							<td id="mainColumn">
								<table id="mainTable" cellpadding="0" cellspacing="0">
									<tr>
										<td>
<?php
}

