<?php
session_start();

$_REQUEST['debug'] = true;
$_REQUEST['log_sql'] = true;

// display setting for specified step
if(!isset($_REQUEST['step']) || !is_numeric($_REQUEST['step']))
	$_REQUEST['step'] = 1;
	
// list of acceptable post variables
$post = array('SYSTEM_TYPE', 'ENCODE', 'CONVERT', 'LOCAL_ROOT', 'HTML_DOMAIN', 'HTML_ROOT', 'DB_TYPE', 'DB_SERVER');
foreach($post as $key)
{
	if(isset($_REQUEST[$key]))
	{
		$_SESSION[$key] = $_REQUEST[$key];
		$$key = $_REQUEST[$key];
	}
	elseif(isset($_SESSION[$key]))
	{
		$$key = $_SESSION[$key];
	}
}

if(isset($_REQUEST['next']))
{
	header('Location: ' . $_SERVER['PHP_SELF'] . '?step=' . ($_REQUEST['step'] + 1));
}

$supported_databases = split("\n", 'access
ado
ado_access
ado_mssql
db2
odbc_db2
vfp
fbsql
ibase
firebird
borland_ibase
informix
informix72
ldap
mssql
mssqlpo
mysql
mysqli
mysqlt or maxsql
oci8
oci805
oci8po
odbc
odbc_mssql
odbc_oracle
odbtp
odbtp_unicode
oracle
netezza
pdo
postgres
postgres64
postgres7
postgres8
sapdb
sqlanywhere
sqlite
sqlitepo
sybase');

// install stuff based on what step we are on
// things to consider:
// install stuff on each page
// show output information
// if there are errors do not go on


// install tables
//$GLOBALS['database'] = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

//$GLOBALS['database']->install();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Media Server Installer</title>
<style>

body, html {
	height:100%;
	width:100%;
}

body {
	margin:0px;
	background-color:#FFFFFF;
	background-image:none;
	background-repeat:repeat;
	background-position:top left;
	font-family:Verdana, Arial, sans-serif;
	font-size:70%;
	color:#444444;
	direction:ltr;
}

.debug {
	border:3px solid #FC0;
	background-color:#FF6;
}

#selector {
	-moz-opacity:.25;
	opacity: .25;
	filter:alpha(opacity=25);
	position:absolute;
	background-color:#6CF;
	border:1px solid #169;
	z-index:1000;
}

.menu {
	background:#FCFCFC none repeat scroll 0 0;
	border:1px solid #CCCCCC;
	display:none;
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:0;
	min-width:120px;
	padding:0 0 2px;
	position:absolute;
	z-index:2000001;
}

.menuShadow {
	background-color:#000000;
	display:none;
	-moz-opacity:.5;
	opacity: .5;
	filter:alpha(opacity=50);
	position:absolute;
	z-index:2000000;
}

.menu li {
	display:block;
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:2px 2px 0;
}

.menu li a {
	border:1px solid #FFFFFF;
	color:#444444;
	display:block !important;
	overflow:visible;
	padding:3px 6px 5px;
	text-align:left;
	text-decoration:none;
	white-space:nowrap;
	cursor:pointer;
	font-weight:inherit;
}

.menu .itemSelect {
	background:#F7FBFF url(images/browse_select_bg.png) repeat-x scroll center bottom;
	border:1px solid #D8F0FA;
}

.menu .sep {
	border-bottom:1px solid #DCDCDC;
	margin:2px 2px 0;
}

.template_box a {
	color:#FFF;
	padding-right:10px;
}

#bodydiv {
	padding:0 400px 0 400px
}
* {
	line-height:130%;
}
#sizer {
	width:100%
}
#expander {
	margin:0 -400px 0 -400px;
	position:relative;
	min-width:800px
}

#header {
	color:white;
	-x-system-font:none;
	font-family:Verdana,Arial,sans-serif;
	font-size:100%;
	font-size-adjust:none;
	font-stretch:normal;
	font-style:normal;
	font-variant:normal;
	font-weight:normal;
	line-height:normal;
	width:100%;
	background:url(images/headerBG_24_~HeaderGradientImageType~.png) repeat scroll center top;
	height:39px;
	text-align:left;
}

#header * {
	line-height:normal;
}

#header td {
	white-space:nowrap;
	vertical-align:middle;
}

#siteTitle, #templates {
	padding:0;
	white-space:nowrap;
	vertical-align:middle;
	width:0.1%;
	font-family:Verdana,Arial,sans-serif;
	font-size:125%;
	line-height:normal;
	font-weight:bold;
}

#siteTitle a {
	color:#FFF;
	text-decoration:none;
}

#advancedSearch {
	font-size:12px;
	font-weight:normal;
	color:#FFF;
}

#siteTitle {
	padding:0px 0px 0px 10px;
}

#middleArea {
	margin-left:auto;
	margin-right:auto;
}

.searchParent {
	padding:0;
	white-space:nowrap;
	vertical-align:middle;
	text-align:center;
}

#search {
	display:inline;
}

.searchBorder {
	border-color:#88BBDD #66AACC #5599BB;
	border-style:solid;
	border-width:1px;
	padding-bottom:6px;
	padding-top:3px;
}

.innerSearchBorder {
	background-color:#FFFFFF;
	border-color:#446688 #335588 #115577;
	border-style:solid;
	border-width:1px;
	padding-bottom:5px;
	padding-top:2px;
}

#searchInput {
	border-right:1px solid #8F8F8F;
	border-style:none solid none none;
	padding-bottom:4px;
	padding-left:2px;
	padding-top:3px;
	width:21em;
	vertical-align:middle;
	-x-system-font:none;
	font-family:Verdana,Arial,sans-serif;
	font-size:100%;
	font-size-adjust:none;
	font-stretch:normal;
	font-style:normal;
	font-variant:normal;
	font-weight:normal;
	line-height:normal;
}

.buttonBorder {
	border-color:#CFE3C4 #99C383 #5DA253;
	border-style:solid;
	border-width:1px;
	padding-bottom:4px;
	padding-top:1px;
}

#searchButton {
	vertical-align:middle;
	font-family:Verdana,Arial,sans-serif;
	font-size:100%;
	font-size-adjust:none;
	font-stretch:normal;
	font-style:normal;
	font-variant:normal;
	font-weight:normal;
	line-height:normal;
	width:auto;
	background:#307C0B url(images/headerBG_24_~HeaderGradientImageType~.png) repeat scroll center center;
	border:medium none;
	color:#FFFFFF;
	margin:0;
	padding-bottom:2px;
	padding-top:1px;
}

#container {
	margin:auto;
	max-width:900px;
	width:100%;
}

#breadcrumb {
	color:#444444;
	margin-left:9px;
	overflow:hidden;
	width:99%;
}

#breadcrumb ul {
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:0;
	padding-left:0;
	padding:0 0 0 1em;
}

#breadcrumb li {
	display:block;
	float:left;
	margin:0;
}

#breadcrumb a {
	color:#0066A7;
	text-decoration:none;
}

#main {
	height:100%;
	table-layout:fixed;
	width:100%;
}

.sideColumn {
	width:10px;
}

.sideColumn.right {
	background-image:url(images/shadow.png);
	background-position:0 0;
	background-repeat:no-repeat;
}

#mainColumn {
	height:100%;
}

#mainTable {
	border:1px solid #E0E0E0;
	table-layout:fixed;
	vertical-align:top;
	width:100%;
}

.contentSpacing {
	overflow:hidden;
	padding:1.25em;
	position:relative;
	width:auto;
}

.title {
	font-size:160%;
	font-weight:normal;
	margin:0;
	overflow:hidden;
	padding:0 0 0.2em;
	line-height:145%;
}

.titlePadding {
	clear:both;
	height:1.25em;
	width:1em;
}

.pageTable {
	width:100%;
}

.page, .pageW {
	border:1px solid #FEFEFE;
	margin:0.2em 1px;
	position:relative;
	height:1.5em;
	float:left;
	text-align:center;
}

.page {
	width:1.5em;
}

.pageW {
	width:2.5em;
}

.pageHighlight, .pageHighlightW {
	background:#F7FBFF url(images/browse_select_bg.png) repeat-x scroll center bottom;
	border:1px solid #D8F0FA;
	height:1.5em;
	left:0;
	position:absolute;
	top:0;
	visibility:hidden;
}

.pageHighlight {
	width:1.5em;
}

.pageHighlightW {
	width:2.5em;
}

.pageLink {
	background:transparent url(images/transparent.gif) repeat scroll 0 0;
	color:#0066A7;
	font-weight:inherit;
	height:100%;
	left:0;
	position:absolute;
	text-decoration:none;
	top:0;
	width:100%;
	z-index:100;
}

.files {
	margin:0;
	top:0;
	float:left;
	position:relative;
}

.file {
	border:1px solid #FEFEFE;
	float:left;
	height:9.5em;
	margin:0.2em 1px;
	position:relative;
	width:7em;
}

.select .selected, .select .notselected {
	visibility: visible;
}

.notselected {
	visibility:hidden;
	background:#F7FBFF url(images/browse_select_bg.png) repeat-x scroll center bottom;
	border:1px solid #D8F0FA;
	height:9.5em;
	left:0;
	position:absolute;
	top:0;
	width:7em;
}

.selected {
	visibility:visible;
	background:#F7FBFF url(images/browse_select_bg.png) repeat-x scroll center bottom;
	border:1px solid #D8F0FA;
	height:9.5em;
	left:0;
	position:absolute;
	top:0;
	width:7em;
}

.itemTable {
	left:0;
	position:absolute;
	top:0;
}

.itemTable tr {
	height:5.5em;
}

.itemTable td {
	width:7em;
	text-align:center;
}

.itemTable div {
	text-align:center;
	margin-left:1.2em;
	height:48px;
	width:48px;
}

.itemLink {
	background:transparent url(images/transparent.gif) repeat scroll 0 0;
	height:100%;
	left:0;
	position:absolute;
	top:0;
	width:100%;
	z-index:100;
	color:#0066A7;
	font-weight:inherit;
	text-decoration:none;
}

.itemLink span {
	bottom:0;
	cursor:pointer;
	display:block;
	height:4em;
	left:0.5em;
	overflow:hidden;
	position:absolute;
	text-align:center;
	width:6em;
}

#infoBar {
	border-top:1px solid #E0E0E0;
	height:64px;
	background:transparent url(images/middle_fade.png) repeat scroll 0 0;
	color:#FFF;
	width:100%;
	line-height:normal;
	margin:0;
	padding:0;
	vertical-align:middle;
	white-space:nowrap;
}

#infoBar * {
	line-height:normal;
}

.fileInfo {
	width:100%;
	vertical-align:top;
	height:48px;
	white-space:normal;
}

.fileInfo .title {
	font-size:14px;
}

.fileInfo .label {
	font-weight:bold;
	width:50%;
	text-align:right;
	margin-right:4px;
}

.fileInfo td {
	width:33%;
	vertical-align:middle;
	padding:4px;
}

.fileInfo .fileThumb, .fileInfo .fileThumb td {
	padding:0;
	width:auto;
}

.fileInfo infoCell {
	width:100%;
}

.crumbsep {
	display:inline-block;
	height:8px;
	margin:2px 8px 4px;
	vertical-align:middle;
	width:8px;
}

.subText {
	color:#8B8B8B;
	display:block;
	overflow:hidden;
	padding-bottom:0.1em;
	width:100%;
}

#footer {
	margin-top:0.42em;
}

#footerCtr {
	border-color:#F7F3F7;
	background-color:transparent;
	border-top-style:solid;
	border-top-width:1px;
	color:#444444;
	font-size:100%;
	width:100%;
}

#footerCtr td {
	padding:8px 0;
	vertical-align:top;
}

#footerCtr ul {
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:0;
	padding:0;
	white-space:nowrap;
}

#footerCtr ul li {
	border-right-style:solid;
	border-right-width:1px;
	float:left;
	padding:0 8px;
	white-space:nowrap;
	margin:0 0 3px;
}

#footerCtr ul li a {
	color:#444444;
	text-decoration:none;
	font-weight:inherit;
}

#footerCtr ul li.last {
	border-right-style:none;
}

span.title {
	width:150px;
	margin-right:50px;
	font-weight:bold;
	font-size:10pt;
	display:block;
	float:left;
	border-right:1px solid #999;
	clear:both;
	background-color:#6F9;
}

input {
	width:194px;
	float:left;
	margin-right:50px;
}

select, a.wide {
	width: 200px;
	float:left;
	margin-right:50px;
}

ul.desc {
	width:300px;
	display:block;
	float:left;
	border-left:1px solid #999;
	border-bottom:1px solid #999;
	padding-left:50px;
	margin:0px;
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
	clear:both;
}
</style>
</head>

<body>
<div id="bodydiv">
	<div id="sizer">
		<div id="expander">
			<table id="header" cellpadding="0" cellspacing="0" style="background-color:#06A;">
				<tr>
					<td id="siteTitle">Media Server Installer</td>
				</tr>
			</table>
			<div id="container">
				<table width="100%" cellpadding="5" cellspacing="0">
					<tr>
						<td>
							<div id="breadcrumb">
								<ul>
									<li>Media Server Installer</li>
                                    <?php
									
									for($i = 1; $i <= $_REQUEST['step']; $i++)
									{
									?>
									<li><img src="images/carat.gif" class="crumbsep"></li>
                                    <li><a href="?step=<?php echo $i; ?>">Step <?php echo $i; ?></a></li>
                                    <?php
									}
									?>
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
                                                <h1 class="title">Media Server Installer - Step <?php echo $_REQUEST['step']; ?></h1>
                                                <span class="subText">This script will help you install the media server.<br />
                                                The first step is to check for requirements and dependencies for the media server.</span>
                                                
                                                <form action="" method="post">
                                                
                                            	<?php
													
												if($_REQUEST['step'] == 1)
												{
												
												?>
                                                
                                                <h2>Requirements</h2>
                                                <p>First the script must check for a few necissary requirements in order for the site to run properly.</p>
                                                <?php
												
												// check for permission to settings file
												if(realpath('/') == '/')
												{
													if(file_exists('/Users/'))
														$SYSTEM_TYPE = 'mac';
													else
														$SYSTEM_TYPE = 'nix';
												}
												else
													$SYSTEM_TYPE = 'win';
												?><span class="title">System Type</span>
												<select name="SYSTEM_TYPE">
													<option value="win" <?php echo ($SYSTEM_TYPE == 'win')?'selected="selected"':''; ?>>Windows</option>
													<option value="nix" <?php echo ($SYSTEM_TYPE == 'nix')?'selected="selected"':''; ?>>Linux or Unix</option>
													<option value="mac" <?php echo ($SYSTEM_TYPE == 'mac')?'selected="selected"':''; ?>>Mac OS</option>
												</select>
												<ul class="desc">
													<li>The system has detected that you are running <?php echo ($SYSTEM_TYPE=='win')?'Windows':(($SYSTEM_TYPE=='nix')?'Linux or Unix':'Mac OS'); ?>.</li>
													<li>If this is not correct, you must provide permissions to the correct settings.&lt;os&gt;.php file.</li>
												</ul>
												<?php
												
												// check for file permissions
												$settings = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include/settings.' . $SYSTEM_TYPE . '.php';
												if(@fopen($settings, 'w') === false)
												{
													?><span class="title fail">Access to Settings</span>
                                                    <input type="text" disabled="disabled" value="<?php echo $settings; ?>" />
                                                    <ul class="desc">
                                                        <li>The system would like access to the following file.  This is so it can write all the settings when we are done with the install.</li>
                                                        <li>Please create this file, and grant it Read/Write permissions.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title fail">Access to Settings</span>
                                                    <input type="text" disabled="disabled" value="<?php echo $settings; ?>" />
                                                    <ul class="desc">
                                                        <li>The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.</li>
                                                    </ul>
													<?php
												}
												
												// check for mod_rewrite
												if(isset($_REQUEST['modrewrite']) && $_REQUEST['modrewrite'] == true)
												{
													?><span class="title">Mod_Rewrite Enabled</span>
                                                    <a class="wide" href="http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html">Mod_Rewrite Instructions</a>
                                                    <ul class="desc">
                                                        <li>The system has detected that you have mod_rewrite enabled.</li>
                                                        <li>Mod_rewrite is used by some templates and plugins to make the paths look prettier.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title warn">Mod_Rewrite Enabled</span>
                                                    <a class="wide" href="http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html">Mod_Rewrite Instructions</a>
                                                    <ul class="desc">
                                                        <li>The system has detected that you do not have mod_rewrite enabled.  Please follow the link for instructions on enabling mod_rewrite.</li>
                                                        <li>Mod_rewrite is used by some templates and plugins to make the paths look prettier.</li>
                                                    </ul>
													<?php
												}
												
												// check memory limit
												$limit = ini_get('memory_limit');
												if(intval($limit) >= 96)
												{
													?><span class="title">Memory Limit</span>
                                                    <a class="wide" href="http://php.net/manual/en/ini.core.php">PHP Core INI Settings</a>
                                                    <ul class="desc">
                                                        <li>The system has detected that the set memory limit is enough to function properly.</li>
                                                        <li>This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.</li>
                                                        <li>PHP reports that the set memory_limit is <?php echo $limit; ?>.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title warn">Memory Limit</span>
                                                    <a class="wide" href="http://php.net/manual/en/ini.core.php">PHP Core INI Settings</a>
                                                    <ul class="desc">
                                                        <li>The system has detected that the set memory limit is NOT ENOUGH for the system to function properly.</li>
                                                        <li>This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.</li>
                                                        <li>PHP reports that the set memory_limit is <?php echo $limit; ?>.</li>
                                                    </ul>
													<?php
												}
												
												// check for convert and image magic and vlc
												if(!isset($ENCODE))
												{
													if($SYSTEM_TYPE == 'win')
														$ENCODE = 'C:\Program Files\VideoLAN\VLC\vlc.exe';
													else
														$ENCODE = '/usr/bin/vlc';
												}
													
												if(!file_exists($ENCODE))
												{
													?><span class="title warn">Encoder Path</span>
                                                    <input type="text" name="ENCODE" value="<?php echo $ENCODE; ?>" />
                                                    <ul class="desc">
                                                        <li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
                                                        <li>This encoder could be VLC or FFMPEG.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title">Encoder Path</span>
                                                    <input type="text" name="ENCODE" value="<?php echo $ENCODE; ?>" />
                                                    <ul class="desc">
                                                        <li>An encoder has been set and detected, you may change this path to specify a new encoder.</li>
                                                        <li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
                                                        <li>The encoder detected is "<?php echo basename($ENCODE); ?>".</li>
                                                    </ul>
													<?php
												}

												if(!isset($CONVERT))
												{
													if($SYSTEM_TYPE == 'win')
														$CONVERT = 'C:\Program Files\ImageMagick-6.4.9-Q16\convert.exe';
													else
														$CONVERT = '/usr/bin/convert';
												}
													
												if(!file_exists($CONVERT))
												{
													?><span class="title warn">Convert Path</span>
                                                    <input type="text" name="CONVERT" value="<?php echo $CONVERT; ?>" />
                                                    <ul class="desc">
                                                        <li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
                                                        <li>This convert could be ImageMagik.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title">Convert Path</span>
                                                    <input type="text" name="CONVERT" value="<?php echo $CONVERT; ?>" />
                                                    <ul class="desc">
                                                        <li>A converter has been set and detected, you may change this path to specify a new converter.</li>
                                                        <li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
                                                        <li>The encoder detected is "<?php echo basename($CONVERT); ?>".</li>
                                                    </ul>
													<?php
												}
												
												}
												
												if($_REQUEST['step'] == 2)
												{
													
												?>
                                                
                                                <h2>Path Information</h2>
                                                <p>Before the site can't function properly, we must define some paths for templates and plugins to use.</p>
                                                <?php
												
												// check for local root
												if(!isset($LOCAL_ROOT))
													$LOCAL_ROOT = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
												if(file_exists($LOCAL_ROOT . 'include'))
												{
													?><span class="title">Local Root</span>
                                                    <input type="text" name="LOCAL_ROOT" value="<?php echo $LOCAL_ROOT; ?>" />
                                                    <ul class="desc">
                                                    	<li>This is the directory that the site lives in.</li>
                                                    	<li>This directory MUST end with a directory seperate such as / or \.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title fail">Local Root</span>
                                                    <input type="text" name="LOCAL_ROOT" value="<?php echo $LOCAL_ROOT; ?>" />
                                                    <ul class="desc">
                                                    	<li>The system has detected that there is no "include" directory in the site root folder.  You must specify the root directory that the site lives in.</li>
                                                    	<li>This directory MUST end with a directory seperate such as / or \.</li>
                                                    </ul>
													<?php
												}
												
												// check for html domain
												if(!isset($HTML_DOMAIN))
													$HTML_DOMAIN = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['HTTP_HOST'] . (($_SERVER['SERVER_PORT'] != 80)?':' . $_SERVER['SERVER_PORT']:'');
												?><span class="title">HTML Domain</span>
												<input type="text" name="HTML_DOMAIN" value="<?php echo $HTML_DOMAIN; ?>/" />
												<ul class="desc">
                                                	<li>This is the path that you would like to access the site.</li>
													<li>This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.</li>
                                                </ul>
                                                <?php
												
												// check for html root
												if(!isset($HTML_ROOT))
													$HTML_ROOT = ((substr($LOCAL_ROOT, 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'])?substr($LOCAL_ROOT, strlen($_SERVER['DOCUMENT_ROOT'])):'');
												?><span class="title">HTML Root</span>
												<input type="text" name="HTML_ROOT" value="<?php echo $HTML_ROOT; ?>" />
												<ul class="desc">
													<li>This is the directory that the site is accessed through.</li>
													<li>This allows the site to run along site another website, in the specified directory.  This is needed so that templates can find the right path to images and styles.</li>
													<li>This path must also end with the HTTP separator /.</li>
													<li>The server reports the DOCUMENT ROOT is <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
												</ul>

												<?php
												
												}
																								
												// set up database
												if($_REQUEST['step'] == 3)
												{
													
													include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php';
													include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb-exceptions.inc.php';
												?>
                                                
                                                <h2>Database Setup</h2>
                                                <p>This site is largely based on database use; we will configure this now.</p>
                                                
                                                <?php
												
												// set up database type
												if(!isset($DB_TYPE))
													$DB_TYPE = 'mysql';
												?><span class="title">Database Type</span>
												<select name="DB_TYPE">
												<?php
													foreach($supported_databases as $db)
													{
													?><option value="<?php echo $db; ?>" <?php echo ($DB_TYPE == $db)?'selected="selected"':''; ?>><?php echo $db; ?></option><?php
													}
												?>
												</select>
												<ul class="desc">
													<li>This site supports a variety of databases, select your database type.</li>
												</ul>
												<?php
												
												// set up database server
												if(!isset($DB_SERVER))
													$DB_SERVER = 'localhost';
												# or dsn 
												$dsn = $DB_TYPE . '://user:pwd@' . $DB_SERVER; 
												try { 
													$conn = ADONewConnection($dsn);  # no need for Connect()
												} catch (exception $e) { 
													 var_dump($e); 
													 adodb_backtrace($e->gettrace());
												} 												
												// set up database username and password
												
												}

												if($_REQUEST['step'] == 4)
												{
												
												?>
                                                
                                                <h2>Template Settings</h2>
                                                <p>This site supports multiple templates.  In order for users to have the best visual experience, we recommend you review these settings.</p>
                                                <?php
												
												// check for base template
												if(!isset($LOCAL_BASE))
													$LOCAL_BASE = 'templates' . DIRECTORY_SEPARATOR . 'plain' . DIRECTORY_SEPARATOR;
												if(file_exists($LOCAL_ROOT . $LOCAL_BASE))
												{
													?><span class="title">Template Base</span>
                                                    <input type="text" name="LOCAL_BASE" value="<?php echo $LOCAL_BASE; ?>" />
                                                    <ul class="desc">
                                                    	<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
                                                    	<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
                                                    	<li>The server reports that <?php echo $LOCAL_ROOT . $LOCAL_BASE; ?> does, in fact, exist.</li>
                                                    </ul>
													<?php
												}
												else
												{
													?><span class="title fail">Template Base</span>
                                                    <input type="text" name="LOCAL_BASE" value="<?php echo $LOCAL_BASE; ?>" />
                                                    <ul class="desc">
                                                    	<li>The system has detected that the local basic template files are not where they are expected to be.</li>
                                                    	<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
                                                    	<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
                                                    	<li>The server reports that <?php echo $LOCAL_ROOT . $LOCAL_BASE; ?> does NOT EXIST.</li>
                                                    </ul>
													<?php
												}
												
												// select default template
												
												
												}
												?>
                                                
                                                <div style="height:30px; float:left; display:block; width:100%"> </div>
                                                	<input type="submit" name="reset" value="Reset to Defaults" class="button" />
                                                    <input type="submit" name="next" value="Save and Continue" style="float:right;" />
                                                    <input type="submit" name="save" value="Save" class="button" style="float:right;" />
                                                </form>
                                            </div>
										</td>
									</tr>
								</table>
							</td>
							<td class="sideColumn right"></td>
						</tr>
					</table>
				</div>
				<div id="footer">
					<table id="footerCtr">
						<tr>
							<td>
								<ul>
								</ul>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
