<?php

function register_live_header()
{
	$config = array(
		'name' => 'Live Header',
	);

	if($GLOBALS['module'] != 'admin_install')
	{
		$config['styles'] = array(
			'module=template&template=live&tfile=live.css',
			'module=template&template=live&tfile=types.css'
		);
		$config['scripts'] = array('module=template&template=live&tfile=dragclick.js');
	}
	
	return $config;
}

function theme_live_styles($styles)
{
	if(!isset($styles))
		return;
		
	if(is_string($styles)) $styles = array($styles);
	
	foreach($styles as $link)
	{
		?>
		<link rel="stylesheet" href="<?php print url($link); ?>" type="text/css"/>
		<?php
	}
}

function theme_live_scripts($scripts)
{
	if(!isset($scripts))
		return;
		
	if(is_string($scripts)) $scripts = array($scripts);
	
	foreach($scripts as $link)
	{
		?>
		<script language="javascript" type="text/javascript" src="<?php print url($link); ?>"></script>
		<?php
	}
}

function theme_live_head()
{
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print setting('html_name'); ?> : <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name'];?></title>
<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
<?php theme('styles', $GLOBALS['templates']['vars']['styles']); ?>
<?php theme('scripts', $GLOBALS['templates']['vars']['scripts']); ?>
<script language="javascript">
var loaded = false;
<?php
if(isset($GLOBALS['templates']['vars']['selector']) && $GLOBALS['templates']['vars']['selector'] == false)
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
	theme('head');
	
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
	<div id="debug" class="debug">
	<?php
	if($GLOBALS['templates']['vars']['user']['Username'] != 'guest')
	{
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			?>
			<a onClick="toggleDiv('error_<?php print $i; ?>')"><?php print htmlspecialchars($error->message); ?></a><br />
			<div id="error_<?php print $i; ?>" style="display:none;">
				<code>
					<pre>
<?php print htmlspecialchars(print_r($error, true)); ?>
					</pre>
				</code>
			</div>
			<?php
		}
	}
	else
	{
		?>
		<form action="<?php print url('module=users&users=login&return=' . urlencode($GLOBALS['templates']['vars']['get'])); ?>" method="post">
			Administrators: Log in to select debug options. Username: <input type="text" name="username" value="" />
			Password: <input type="password" name="password" value="" />
			<input type="submit" value="Login" />
			<input type="reset" value="Reset" />
		</form>
		<?php
	}
	
	?></div><?php

}

function theme_live_breadcrumbs()
{
	if($GLOBALS['templates']['vars']['module'] != 'select' && $GLOBALS['templates']['vars']['module'] != 'index')
	{
		?>
		<li><a href="<?php print url('module=select&cat=' . $GLOBALS['templates']['vars']['cat'] . '&dir=' . urlencode('/')); ?>"><?php print setting('html_name'); ?></a></li>
		<li><img src="<?php print url('module=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
		<?php
		// break up the module by the underscores
		$crumbs = split('_', $GLOBALS['module']);
		$current = '';
		foreach($crumbs as $i => $crumb)
		{
			$current .= (($current != '')?'_':'') . $crumb;
			if(isset($GLOBALS['modules'][$current]))
			{
				?>
				<li><a href="<?php print url('module=' . $current); ?>"><?php print $GLOBALS['modules'][$current]['name']; ?></a></li>
				<?php
				if($i != count($crumbs) - 1)
				{
					?>
					<li><img src="<?php print url('module=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
					<?php
				}
			}
		}
	}
	else
	{
		$crumbs = isset($GLOBALS['templates']['vars']['dir'])?split('/', $GLOBALS['templates']['vars']['dir']):array('');
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
				<li><a href="<?php print url('module=select&cat=' . $GLOBALS['templates']['vars']['cat'] . '&dir=' . urlencode('/')); ?>"><?php print setting('html_name'); ?></a></li>
				<li><img src="<?php print url('module=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
				<?php
			}
			elseif($count == count($crumbs)-1)
			{
				?><li><?php print $text; ?></li><?php
			}
			else
			{
				?>
				<li><a href="<?php print url('module=select&cat=' . $GLOBALS['templates']['vars']['cat'] . '&dir=' . urlencode($path . '/')); ?>"><?php print $text; ?></a></li>
				<li><img src="<?php print url('module=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
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
			?><a href="<?php print url('template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><?php
		}
	}
	?></div><?php
}

function live_get_theme_color()
{
	if(!isset($GLOBALS['templates']['vars']['files_count']))
		return 'files';
	if($GLOBALS['templates']['vars']['audio_count'] > $GLOBALS['templates']['vars']['image_count'] &&
		$GLOBALS['templates']['vars']['audio_count'] > $GLOBALS['templates']['vars']['video_count'] &&
		$GLOBALS['templates']['vars']['audio_count'] > $GLOBALS['templates']['vars']['files_count']
	)
		$theme = 'audio';
	elseif($GLOBALS['templates']['vars']['image_count'] > $GLOBALS['templates']['vars']['files_count'] &&
		$GLOBALS['templates']['vars']['image_count'] > $GLOBALS['templates']['vars']['video_count'] &&
		$GLOBALS['templates']['vars']['image_count'] > $GLOBALS['templates']['vars']['audio_count']
	)
		$theme = 'image';
	elseif($GLOBALS['templates']['vars']['video_count'] > $GLOBALS['templates']['vars']['files_count'] &&
		$GLOBALS['templates']['vars']['video_count'] > $GLOBALS['templates']['vars']['image_count'] &&
		$GLOBALS['templates']['vars']['video_count'] > $GLOBALS['templates']['vars']['audio_count']
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
<body onLoad="init();" onmousemove="setSelector()" onmousedown="return startDrag(event);" onmouseup="endDrag();return false;">
<?php theme('list_block'); ?>
<div id="bodydiv">
	<div id="sizer">
		<div id="expander">
			<table id="header" cellpadding="0" cellspacing="0" style="background-color:<?php print ($theme == 'audio')?'#900':(($theme == 'image')?'#990':(($theme == 'video')?'#093':'#06A')); ?>;">
				<tr>
					<td id="siteTitle"><a href="<?php print url('module=index'); ?>"><?php print setting('html_name'); ?></a></td>
					<td>
						<?php theme('search_block'); ?>
					</td>
					<td id="templates"><?php theme('template_block'); ?></td>
				</tr>
			</table>
<?php if(DEBUG_MODE) theme('debug_block'); ?>
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
				<div id="content" onmousedown="return selector_off;">
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

