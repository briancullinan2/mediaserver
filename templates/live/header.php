<?php

function register_live_header()
{
	return array(
		'name' => 'Live Header',
		'styles' => array(
			'plugin=template&template=live&tfile=live.css',
			'plugin=template&template=live&tfile=types.css'
		),
		'scripts' => array('plugin=template&template=live&tfile=dragclick.js')
	);
}

function theme_live_styles($styles)
{
	if(is_string($styles)) $styles = array($styles);
	
	foreach($styles as $link)
	{
		?>
		<link rel="stylesheet" href="<?php print href($link); ?>" type="text/css"/>
		<?php
	}
}

function theme_live_scripts($scripts)
{
	if(is_string($scripts)) $scripts = array($scripts);
	
	foreach($scripts as $link)
	{
		?>
		<script language="javascript" type="text/javascript" src="<?php print href($link); ?>"></script>
		<?php
	}
}

function theme_live_header()
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print isset($GLOBALS['templates']['vars']['title'])?$GLOBALS['templates']['vars']['title']:HTML_NAME; ?></title>
<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
<?php theme('styles', $GLOBALS['templates']['vars']['styles']); ?>
<?php theme('scripts', $GLOBALS['templates']['vars']['scripts']); ?>
<script language="javascript">
var loaded = false;
</script>
</head>
<?php
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
	if($GLOBALS['templates']['vars']['loggedin'])
	{
		foreach($GLOBALS['debug_errors'] as $i => $error)
		{
			?>
			<a onClick="toggleDiv('error_<?php print $i; ?>')"><?php print $error->message; ?></a><br />
			<div id="error_<?php print $i; ?>" style="display:none;">
				<code>
					<pre>
					<?php print_r($error); ?>
					</pre>
				</code>
			</div>
			<?php
		}
	}
	else
	{
		?>
		<form action="<?php print href('plugin=login&return=' . urlencode(href($GLOBALS['templates']['vars']['get']))); ?>" method="post">
			Administrators: Log in to select debug options. Username: <input type="text" name="username" value="" />
			Password: <input type="password" name="password" value="" />
			<input type="submit" value="Login" />
			<input type="reset" value="Reset" />
		</form>
		<?php
	}
	
	?></div><?php

}

function theme_live_breadcrumbs($dir)
{
	$crumbs = split('/', $GLOBALS['templates']['vars']['dir']);
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
			<li><a href="<?php print href('plugin=select&cat=' . $GLOBALS['templates']['vars']['cat'] . '&dir=' . urlencode('/')); ?>"><?php print HTML_NAME; ?></a></li>
			<li><img src="<?php print href('plugin=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
			<?php
		}
		elseif($count == count($crumbs)-1)
		{
			?><li><?php print $text; ?></li><?php
		}
		else
		{
			?>
			<li><a href="<?php print href('plugin=select&cat=' . $GLOBALS['templates']['vars']['cat'] . '&dir=' . urlencode('/' . $path . '/')); ?>"><?php print $text; ?></a></li>
			<li><img src="<?php print href('plugin=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li>
			<?php
		}
		$path .= '/';
		
		$count++;
	}
}

function theme_live_template_block()
{
	?><div class="template_box"><?php
	foreach($GLOBALS['templates']['vars']['templates'] as $i => $template)
	{
		if(is_numeric($i))
		{
			?><a href="<?php print href('template=' . $template, false, true); ?>"><?php print $GLOBALS['templates'][$template]['name']; ?></a><?php
		}
	}
	?></div><?php
}

function live_get_theme_color()
{
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
<div id="bodydiv">
	<div id="sizer">
		<div id="expander">
			<table id="header" cellpadding="0" cellspacing="0" style="background-color:<?php print ($theme == 'audio')?'#900':(($theme == 'image')?'#990':(($theme == 'video')?'#093':'#06A')); ?>;">
				<tr>
					<td id="siteTitle"><a href="<?php print href(''); ?>"><?php print HTML_NAME; ?></a></td>
					<td>
						<table cellpadding="0" cellspacing="0" id="middleArea">
							<tr>
								<td class="searchParent"><?php print str_replace(' from Database', '', constant($GLOBALS['templates']['vars']['cat'] . '::NAME')); ?> Search:
									<form action="<?php print $GLOBALS['templates']['vars']['get']; ?>" method="get" id="search">
										<span class="searchBorder" style="border-color:<?php print ($theme == 'audio')?'#BB8888 #AA6666 #995555':(($theme == 'image')?'#BBBBAA #AAAACC #9999BB':(($theme == 'video')?'#88DDBB #66CCAA #55BB99':'#88BBDD #66AACC #5599BB')); ?>;"><span class="innerSearchBorder" style="border-color:<?php print ($theme == 'audio')?'#883333 #883322 #772211':(($theme == 'image')?'#888844 #888833 #777722':(($theme == 'video')?'#668866 #448844 #447744':'#446688 #335588 #115577')); ?>;"><input type="text" name="search" value="<?php print isset($GLOBALS['templates']['vars']['search'])?$GLOBALS['templates']['vars']['search']:''; ?>" id="searchInput" /><span class="buttonBorder"><input type="submit" value="Search" id="searchButton" /></span></span></span>&nbsp;&nbsp; <a id="advancedSearch" href="<?php echo href('plugin=search&dir=' . $GLOBALS['templates']['vars']['dir']); ?>">Advanced Search</a>
									</form>
								</td>
							</tr>
						</table>
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
theme('breadcrumbs', $GLOBALS['templates']['vars']['dir']);
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

