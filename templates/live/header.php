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
	foreach($styles as $link)
	{
		?>
		<link rel="stylesheet" href="<?php print href($link); ?>" type="text/css"/>
		<?php
	}
}

function theme_live_scripts($scripts)
{
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
<title><?php print $GLOBALS['templates']['vars']['title']; ?></title>
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
			<a onclick="toggleDiv('error_<?php print $i; ?>')"><?php print $error->message; ?></a><br />
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

function theme_live_breadcrumbs($crumbs)
{
	foreach($crumbs as $i => $crumb)
	{
		if($i == 0)
		{
			?><li><a href="<?php print href('plugin=select&cat=file&dir=/'); ?>"><?php print HTML_NAME; ?></a></li><?php
			?><li><img src="<?php print href('plugin=template&tfile=images/carat.gif&template=' . HTML_TEMPLATE); ?>" class="crumbsep"></li><?php
		}
		elseif($i == count($crumbs)-1)
		{
			?><li>{$dir|@htmlspecialchars}</li><?php
		}
		else
		{
			?>
			<li><a href="{'plugin=select&cat='|cat:$cat|cat:'&dir='|cat:$path|generate_href}">{$dir|@htmlspecialchars}</a></li>
			<li><img src="{'plugin=template&tfile=images/carat.gif&template='|cat:$smarty.const.HTML_TEMPLATE|generate_href}" class="crumbsep"></li>
			<?php
		}
	}
}

function theme_live_body()
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
		
?>
<body onload="init();" onmousemove="setSelector()" onmousedown="return startDrag(event);" onmouseup="endDrag();return false;">
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
										<span class="searchBorder" style="border-color:<?php print ($theme == 'audio')?'#BB8888 #AA6666 #995555':(($theme == 'image')?'#BBBBAA #AAAACC #9999BB':(($theme == 'video')?'#88DDBB #66CCAA #55BB99':'#88BBDD #66AACC #5599BB')); ?>;"><span class="innerSearchBorder" style="border-color:<?php print ($theme == 'audio')?'#883333 #883322 #772211':(($theme == 'image')?'#888844 #888833 #777722':(($theme == 'video')?'#668866 #448844 #447744':'#446688 #335588 #115577')); ?>;"><input type="text" name="search" value="<?php print $GLOBALS['templates']['vars']['search']; ?>" id="searchInput" /><span class="buttonBorder"><input type="submit" value="Search" id="searchButton" /></span></span></span>&nbsp;&nbsp; <a id="advancedSearch" href="<?php echo href('plugin=search&dir=' . $GLOBALS['templates']['vars']['dir']); ?>">Advanced Search</a>
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
/*
{assign var=dirlen value=$dir|strlen}
{assign var=dirlen value=$dirlen-1}
{if $dir|substr:$dirlen:1 eq '/'}{assign var=dir value=$dir|substr:0:$dirlen}{else}{assign var=dir value=$dir}{/if}
{assign var=crumbs value='/'|split:$dir}
{assign var=crumbcount value=$crumbs|@count}
{assign var=crumbcount value=$crumbcount-1}
{assign var=path value='/'}

								</ul>
							</div>
						</td>
					</tr>
				</table>
				<div id="content" onmousedown="return selector_off;">
					<div id="selector" style="display:none;"></div>
					<ul class="menu" id="menu">
						<li id="option_download"><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';"><b>Download File</b></a></li>
						<li id="option_open"><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';"><b>Open</b></a></li>
						<li><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';">Play Now</a></li>
						<li><div class="sep"></div></li>
						<li><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';">Download Zip</a></li>
						<li><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';">Download Torrent</a></li>
						<li><a href="#" onmouseout="this.className = '';" onmouseover="this.className = 'itemSelect';">Add to Queue</a></li>
					</ul>
					<div class="menuShadow" id="shadow"></div>
					<table id="main" cellpadding="0" cellspacing="0">
						<tr>
							<td class="sideColumn"></td>
							<td id="mainColumn">
								<table id="mainTable" cellpadding="0" cellspacing="0">
									<tr>
										<td>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Media Server Installer</title>
<link rel="stylesheet" href="<?php echo l('plugin=template&template=live&tfile=live.css'); ?>
" type="text/css"/>
<link rel="stylesheet" href="<?php echo l('plugin=template&template=live&tfile=types.css'); ?>" type="text/css"/>
<style>

td {
	padding-left:20px;
}

td.title {
	width:175px;
	padding-left:20px;
	font-weight:bold;
	font-size:10pt;
	background-color:#6F9;
}

input {
	width:194px;
	margin-right:50px;
}

select, a.wide, label {
	width: 200px;
	margin-right:50px;
	display:block;
}

td.desc {
	width:300px;
	border-left:1px solid #999;
	border-bottom:1px solid #999;
	padding-left:10px;
}

input.button {
	width:150px;
}

.title.fail {
	background-color:#F66;
}

.title.warn {
	background-color:#FC3;
}

h2 {
	font-size:12pt;
}
</style>
</head>
<body>
<div id="bodydiv">
<div id="sizer">
<div id="expander">
<table id="header" cellpadding="0" cellspacing="0" style="background-color:#06A;">
	<tr>
		<td id="siteTitle"><?php echo HTML_NAME . (isset($GLOBALS['templates']['vars']['title'])?(' : ' . $GLOBALS['templates']['vars']['title']):''); ?></td>
	</tr>
</table>
<div id="container">
<table width="100%" cellpadding="5" cellspacing="0">
	<tr>
		<td><div id="breadcrumb">
				<ul>
					<li><?php echo HTML_NAME; ?></li>
					<li><img src="<?php echo generate_href('plugin=template&template=live&tfile=images/carat.gif'); ?>" class="crumbsep" alt="&gt;" /></li>
				</ul>
			</div></td>
	</tr>
</table>
<div id="content" onmousedown="return selector_off;">
<div class="menuShadow" id="shadow"></div>
<table id="main" cellpadding="0" cellspacing="0">
<tr>
	<td class="sideColumn"></td>
	<td id="mainColumn"><table id="mainTable" cellpadding="0" cellspacing="0">
		<tr>
			<td><div class="contentSpacing">
				<h1 class="title"><?php echo (isset($GLOBALS['templates']['vars']['title'])?($GLOBALS['templates']['vars']['title']):''); ?></h1>
				<?php
if(isset($GLOBALS['templates']['vars']['subtext']))
{
?>
				<span class="subText">
				<?php
	if(is_array($GLOBALS['templates']['vars']['subtext']))
		echo join('<br />', $GLOBALS['templates']['vars']['subtext']);
	else
		echo $GLOBALS['templates']['vars']['subtext'];
?>
				</span>
				<?php
}
?>
				*/
				
				}