<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Media Server Installer</title>
<link rel="stylesheet" href="/?plugin=template&template=live&tfile=live.css" type="text/css"/>
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
						<td>
							<div id="breadcrumb">
								<ul>
									<li><?php echo HTML_NAME; ?></li>
									<li><img src="<?php echo generate_href('plugin=template&template=live&tfile=images/carat.gif'); ?>" class="crumbsep" alt="&gt;" /></li>
								</ul>
							</div>
						</td>
					</tr>
				</table>
				<div id="content" onmousedown="return selector_off;">
					<div class="menuShadow" id="shadow"></div>
					<table id="main" cellpadding="0" cellspacing="0">
						<tr>
							<td class="sideColumn"></td>
							<td id="mainColumn">
								<table id="mainTable" cellpadding="0" cellspacing="0">
									<tr>
										<td>
                                            <div class="contentSpacing">




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
