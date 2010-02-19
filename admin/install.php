<?php
if(isset($_REQUEST['image']))
{
	print_image();
}

session_start();

$_REQUEST['debug'] = true;
$_REQUEST['log_sql'] = true;

// display setting for specified step
if(!isset($_REQUEST['step']) || !is_numeric($_REQUEST['step']))
	$_REQUEST['step'] = 1;
	
// calculate buffer size
if(isset($_REQUEST['BUFFER_SIZE']) && isset($_REQUEST['BUFFER_SIZE_MULTIPLIER']))
	$_REQUEST['BUFFER_SIZE'] = $_REQUEST['BUFFER_SIZE'] * $_REQUEST['BUFFER_SIZE_MULTIPLIER'];
if(isset($_REQUEST['DIRECTORY_SEEK_TIME']) && isset($_REQUEST['DIRECTORY_SEEK_TIME_MULTIPLIER']))
	$_REQUEST['DIRECTORY_SEEK_TIME'] = $_REQUEST['DIRECTORY_SEEK_TIME'] * $_REQUEST['DIRECTORY_SEEK_TIME_MULTIPLIER'];
if(isset($_REQUEST['FILE_SEEK_TIME']) && isset($_REQUEST['FILE_SEEK_TIME_MULTIPLIER']))
	$_REQUEST['FILE_SEEK_TIME'] = $_REQUEST['FILE_SEEK_TIME'] * $_REQUEST['FILE_SEEK_TIME_MULTIPLIER'];

// list of acceptable post variables
$post = array('SYSTEM_TYPE', 'ENCODE', 'CONVERT', 'LOCAL_ROOT', 'HTML_DOMAIN', 'HTML_ROOT', 'DB_TYPE', 'DB_SERVER', 'DB_USER', 'DB_PASS', 'DB_NAME');

// set each valid post variable	
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


// include the modules
$tmp_modules = array();
if ($dh = @opendir($LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR))
{
	while (($file = readdir($dh)) !== false)
	{
		// filter out only the modules for our USE_DATABASE setting
		if ($file[0] != '.' && !is_dir($LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file))
		{
			// include all the modules
			require_once $LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . $file;
			$class_name = substr($file, 0, strrpos($file, '.'));
			
			// only use the module if it is properly defined
			if(class_exists($class_name))
			{
				if(substr($file, 0, 3) == 'db_')
					$tmp_modules[] = $class_name;
			}
		}
	}
	closedir($dh);
}

$error_count = 0;
$new_modules = array();

// reorganize modules to reflect heirarchy
while(count($tmp_modules) > 0 && $error_count < 1000)
{
	foreach($tmp_modules as $i => $module)
	{
		$tmp_override = get_parent_class($module);
		if(in_array($tmp_override, $new_modules) || $tmp_override == '')
		{
			$new_modules[] = $module;
			$post[] = strtoupper($module . '_ENABLE');
			unset($tmp_modules[$i]);
		}
	}
	$error_count++;
}

$GLOBALS['modules'] = $new_modules;

$post[] = 'LOCAL_BASE';
$post[] = 'LOCAL_DEFAULT';
$post[] = 'LOCAL_TEMPLATE';
$post[] = 'DIRECTORY_SEEK_TIME';
$post[] = 'FILE_SEEK_TIME';
$post[] = 'DEBUG_MODE';
$post[] = 'RECURSIVE_GET';
$post[] = 'NO_BOTS';
$post[] = 'TMP_DIR';
$post[] = 'LOCAL_USERS';
$post[] = 'BUFFER_SIZE';
$post[] = 'USE_ALIAS';

$required = array('DB_FILE_ENABLE', 'DB_IDS_ENABLE', 'DB_WATCH_ENABLE', 'DB_ALIAS_ENABLE', 'DB_USERS_ENABLE', 'DB_WATCH_LIST_ENABLE');

// set each valid post variable	
foreach($post as $key)
{
	if(in_array($key, $required))
	{
		$_SESSION[$key] = 'true';
		$$key = 'true';
		continue;
	}
	
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

$recommended = array('db_audio', 'db_image', 'db_video');

$supported_databases = array('access','ado','ado_access','ado_mssql','db2','odbc_db2','vfp','fbsql','ibase','firebird','borland_ibase','informix','informix72','ldap','mssql','mssqlpo','mysql','mysqli','mysqlt','maxsql','oci8','oci805','oci8po','odbc','odbc_mssql','odbc_oracle','odbtp','odbtp_unicode','oracle','netezza','pdo','postgres','postgres64','postgres7','postgres8','sapdb','sqlanywhere','sqlite','sqlitepo','sybase');

if(isset($_REQUEST['next']))
{
	header('Location: ' . $_SERVER['PHP_SELF'] . '?step=' . ($_REQUEST['step'] + 1));
	exit;
}
if(isset($_POST) && count($_POST) > 0)
{
	if(isset($_POST['dberror'])) $_SESSION['dberror'] = $_POST['dberror'];
	header('Location: ' . $_SERVER['PHP_SELF'] . '?step=' . ($_REQUEST['step']));
	exit;
}

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
<link rel="stylesheet" href="./live.css" type="text/css"/>
<style type="text/css">

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
	background:url(./install.php?image=gradient) repeat scroll center top;
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

#siteTitle {
	padding:0px 0px 0px 10px;
}

#middleArea {
	margin-left:auto;
	margin-right:auto;
}

.innerSearchBorder {
	background-color:#FFFFFF;
	border-color:#446688 #335588 #115577;
	border-style:solid;
	border-width:1px;
	padding-bottom:5px;
	padding-top:2px;
}

.buttonBorder {
	border-color:#CFE3C4 #99C383 #5DA253;
	border-style:solid;
	border-width:1px;
	padding-bottom:4px;
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
	background-image:url(./install.php?image=shadow);
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

<?php
if(isset($_REQUEST['install']))
{
	include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'PEAR.php';
	include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'database.php';
	
	if(isset($_SESSION)) session_write_close();
	
	$DB_CONNECT = $DB_TYPE . '://' . $DB_USER . ':' . $DB_PASS . '@' . $DB_SERVER . '/' . $DB_NAME;
	$DATABASE = new database($DB_CONNECT);
?>
<body onload="top.document.getElementById('loading2').style.display = 'none'; top.document.getElementById('install').style.height=document.getElementById('installtable').clientHeight+'px';">
<table id="installtable" border="0" cellpadding="0" cellspacing="0">

<?php
function printEachStep($result, $table)
{
	$e = ADODB_Pear_Error();
	if($e !== false)
	{

	?>
	<tr>
    <td class="title fail">Access to Database</td>
    <td>
    The connection manager reported the following error:<br /><?php echo $e->userinfo; ?>.
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>?step=3" method="post" target="_top">
	<?php
    for($i = 0; $i < 11 + count($GLOBALS['modules']); $i++)
    {
        ?>
    <input type="hidden" name="<?php echo $post[$i]; ?>" value="<?php echo $$post[$i]; ?>" />
    <?php
    }
    ?>
    <input type="submit" value="Panic!" />
    </form>
    </td>
    <td class="desc">
    <ul>
        <li>An error was encountered while installing the system.</li>
    </ul>
    </td>
    </tr>
    <?php
	}
	else
	{
		
    ?>
	<tr>
    <td class="title">Install Table "<?php echo $table ?>"</td>
    <td>
    <label><?php echo $table ?> Installed</label>
    </td>
    <td class="desc">
    <ul>
        <li>This table has been successfully installed!</li>
    </ul>
    </td>
    </tr>
    <?php
	}
}
	$DATABASE->install('printEachStep');
	?>
</table>
</body>
</html>
    <?php

	exit;
}
if(isset($_REQUEST['test']))
{
	include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'PEAR.php';
	include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'database.php';

	if(isset($_SESSION)) session_write_close();

	ob_start();

	$dsn = $DB_TYPE . '://' . $DB_USER . ':' . $DB_PASS . '@' . $DB_SERVER . '/' . $DB_NAME; 
	$conn = ADONewConnection($dsn);  # no need for Connect()

	$result = ob_get_contents();
	
	ob_end_clean();
	
	$e = ADODB_Pear_Error();
	if($e !== false)
	{

	?>
<body onload="top.document.getElementById('loading1').style.display = 'none'; top.document.getElementById('test').style.height=document.getElementById('testtable').clientHeight+'px';">
<table id="testtable" border="0" cellpadding="0" cellspacing="0">
	<tr>
    <td class="title fail">Access to Database</td>
    <td>
    The connection manager reported the following error:<br /><?php echo $e->userinfo; ?>.
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>?step=3" method="post" target="_top">
    <input type="hidden" name="dberror" value="<?php echo $e->userinfo; ?>" />
	<?php
    for($i = 0; $i < 11 + count($GLOBALS['modules']); $i++)
    {
        ?>
    <input type="hidden" name="<?php echo $post[$i]; ?>" value="<?php echo $$post[$i]; ?>" />
    <?php
    }
    ?>
    <input type="submit" value="Return to Step 3" />
    </form>
    </td>
    <td class="desc">
    <ul>
        <li>The system has failed to connect to the database.  Please go back to the Database Setup page (Step 3) to correct this error.</li>
        <li>If you are unsure what the reported error means, please contact your server administrator for more information.</li>
    </ul>
    </td>
    </tr>
</table>
</body>
</html>
    <?php
	
	}
	else
	{
		
    ?>
<body onload="top.document.getElementById('loading1').style.display = 'none'; top.document.getElementById('test').style.height=document.getElementById('testtable').clientHeight+'px'; top.document.getElementById('loading2').style.display='inline'; top.document.getElementById('install').src='<?php echo $_SERVER['PHP_SELF']; ?>?step=5&install='">
<table id="testtable" border="0" cellpadding="0" cellspacing="0">
	<tr>
    <td class="title">Access to Database</td>
    <td>
    	<label>Connection successful, tables will now be created.</label>
    </td>
    <td class="desc">
    <ul>
        <li>You have connected to the database successfully!</li>
    </ul>
    </td>
    </tr>
</table>
</body>
</html>
    <?php
	
	}
	
	exit;
}

?>
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
									<li><img src="./install.php?image=carat" class="crumbsep" alt="&gt;" /></li>
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
switch($_REQUEST['step'])
{
	case 1:
	$count = 0;
	case 2:
	$count = 3;
	break;
	case 3:
	$count = 6;
	break;
	case 4:
	$count = 11;
	break;
	case 5:
	case 6:
	$count = 11 + count($GLOBALS['modules']);
	break;
	case 7:
	$count = 11 + count($GLOBALS['modules']) + 3;
	break;
	case 8:
	$count = 11 + count($GLOBALS['modules']) + 5;
	break;
	case 9:
	$count = 11 + count($GLOBALS['modules']) + 12;
	break;
}
for($i = 0; $i < $count; $i++)
{
	?>
<input type="hidden" name="<?php echo $post[$i]; ?>" value="<?php echo $$post[$i]; ?>" />
<?php
}

if($_REQUEST['step'] == 1)
{

?>

<h2>Requirements</h2>
<p>First the script must check for a few necissary requirements in order for the site to run properly.</p>

<table border="0" cellpadding="0" cellspacing="0">
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
?><tr><td class="title">System Type</td>
<td>
<select name="SYSTEM_TYPE">
    <option value="win" <?php echo ($SYSTEM_TYPE == 'win')?'selected="selected"':''; ?>>Windows</option>
    <option value="nix" <?php echo ($SYSTEM_TYPE == 'nix')?'selected="selected"':''; ?>>Linux or Unix</option>
    <option value="mac" <?php echo ($SYSTEM_TYPE == 'mac')?'selected="selected"':''; ?>>Mac OS</option>
</select>
</td>
<td class="desc">
<ul>
    <li>The system has detected that you are running <?php echo ($SYSTEM_TYPE=='win')?'Windows':(($SYSTEM_TYPE=='nix')?'Linux or Unix':'Mac OS'); ?>.</li>
    <li>If this is not correct, you must provide permissions to the correct settings.&lt;os&gt;.php file.</li>
</ul>
</td></tr>
<?php

// check for file permissions
$settings = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'include/settings.' . $SYSTEM_TYPE . '.php';
if(@fopen($settings, 'w') === false)
{
    ?><tr><td class="title fail">Access to Settings</td>
    <td>
    <input type="text" disabled="disabled" value="<?php echo $settings; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>The system would like access to the following file.  This is so it can write all the settings when we are done with the install.</li>
        <li>Please create this file, and grant it Read/Write permissions.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title fail">Access to Settings</td>
    <td>
    <input type="text" disabled="disabled" value="<?php echo $settings; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.</li>
    </ul>
    </td></tr>
    <?php
}

// check for mod_rewrite
if(isset($_REQUEST['modrewrite']) && $_REQUEST['modrewrite'] == true)
{
    ?><tr><td class="title">Mod_Rewrite Enabled</td>
    <td>
    <a class="wide" href="http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html">Mod_Rewrite Instructions</a>
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that you have mod_rewrite enabled.</li>
        <li>Mod_rewrite is used by some templates and plugins to make the paths look prettier.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title warn">Mod_Rewrite Enabled</td>
    <td>
    <a class="wide" href="http://httpd.apache.org/docs/1.3/mod/mod_rewrite.html">Mod_Rewrite Instructions</a>
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that you do not have mod_rewrite enabled.  Please follow the link for instructions on enabling mod_rewrite.</li>
        <li>Mod_rewrite is used by some templates and plugins to make the paths look prettier.</li>
    </ul>
    </td></tr>
    <?php
}

// check memory limit
$limit = ini_get('memory_limit');
if(intval($limit) >= 96)
{
    ?><tr><td class="title">Memory Limit</td>
    <td>
    <a class="wide" href="http://php.net/manual/en/ini.core.php">PHP Core INI Settings</a>
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that the set memory limit is enough to function properly.</li>
        <li>This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.</li>
        <li>PHP reports that the set memory_limit is <?php echo $limit; ?>.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title warn">Memory Limit</td>
    <td>
    <a class="wide" href="http://php.net/manual/en/ini.core.php">PHP Core INI Settings</a>
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that the set memory limit is NOT ENOUGH for the system to function properly.</li>
        <li>This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.</li>
        <li>PHP reports that the set memory_limit is <?php echo $limit; ?>.</li>
    </ul>
    </td></tr>
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
    ?><tr><td class="title warn">Encoder Path</td>
    <td>
    <input type="text" name="ENCODE" value="<?php echo $ENCODE; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
        <li>This encoder could be VLC or FFMPEG.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title">Encoder Path</td>
    <td>
    <input type="text" name="ENCODE" value="<?php echo $ENCODE; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>An encoder has been set and detected, you may change this path to specify a new encoder.</li>
        <li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
        <li>The encoder detected is "<?php echo basename($ENCODE); ?>".</li>
    </ul>
    </td></tr>
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
    ?><tr><td class="title warn">Convert Path</td>
    <td>
    <input type="text" name="CONVERT" value="<?php echo $CONVERT; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
        <li>This convert could be ImageMagik.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title">Convert Path</td>
    <td>
    <input type="text" name="CONVERT" value="<?php echo $CONVERT; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>A converter has been set and detected, you may change this path to specify a new converter.</li>
        <li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
        <li>The encoder detected is "<?php echo basename($CONVERT); ?>".</li>
    </ul>
    </td></tr>
    <?php
}

?></table><?php

}

elseif($_REQUEST['step'] == 2)
{
    
?>

<h2>Path Information</h2>
<p>Before the site can't function properly, we must define some paths for templates and plugins to use.</p>

<table border="0" cellpadding="0" cellspacing="0">
<?php

// check for local root
if(!isset($LOCAL_ROOT))
    $LOCAL_ROOT = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
if(file_exists($LOCAL_ROOT . 'include'))
{
    ?><tr><td class="title">Local Root</td>
    <td>
    <input type="text" name="LOCAL_ROOT" value="<?php echo $LOCAL_ROOT; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>This is the directory that the site lives in.</li>
        <li>This directory MUST end with a directory seperate such as / or \.</li>
    </ul>
    </td></tr>
    <?php
}
else
{
    ?><tr><td class="title fail">Local Root</td>
    <td>
    <input type="text" name="LOCAL_ROOT" value="<?php echo $LOCAL_ROOT; ?>" />
    </td>
    <td class="desc">
    <ul>
        <li>The system has detected that there is no "include" directory in the site root folder.  You must specify the root directory that the site lives in.</li>
        <li>This directory MUST end with a directory seperate such as / or \.</li>
    </ul>
    </td></tr>
    <?php
}

// check for html domain
if(!isset($HTML_DOMAIN))
    $HTML_DOMAIN = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['HTTP_HOST'] . (($_SERVER['SERVER_PORT'] != 80)?':' . $_SERVER['SERVER_PORT']:'');
?><tr><td class="title">HTML Domain</td>
<td>
<input type="text" name="HTML_DOMAIN" value="<?php echo $HTML_DOMAIN; ?>" />
</td>
<td class="desc">
<ul>
    <li>This is the path that you would like to access the site.</li>
    <li>This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.</li>
</ul>
</td></tr>
<?php

// check for html root
if(!isset($HTML_ROOT))
    $HTML_ROOT = ((substr($LOCAL_ROOT, 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'])?substr($LOCAL_ROOT, strlen($_SERVER['DOCUMENT_ROOT'])):'');
?><tr><td class="title">HTML Root</td>
<td>
<input type="text" name="HTML_ROOT" value="<?php echo $HTML_ROOT; ?>" />
</td>
<td class="desc">
<ul>
    <li>This is the directory that the site is accessed through.</li>
    <li>This allows the site to run along site another website, in the specified directory.  This is needed so that templates can find the right path to images and styles.</li>
    <li>This path must also end with the HTTP separator /.</li>
    <li>The server reports the DOCUMENT ROOT is <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
</ul>
</td></tr>

</table>
<?php

}
                                                
// set up database
elseif($_REQUEST['step'] == 3)
{
	$dberror = false;
	if(isset($_SESSION['dberror']))
	{
		$dberror = stripslashes($_SESSION['dberror']);
	}
    
?>

<h2>Database Setup</h2>
<p>This site is largely based on database use; we will configure this now.  Although database use is optional, it is highly recommended for security and searching.  There will be no search options available without a database, only a flat file-structure will be shown.</p>

<table border="0" cellpadding="0" cellspacing="0">
<?php

// set up database type
if(!isset($DB_TYPE))
    $DB_TYPE = 'mysql';
?><tr><td class="title">Database Type</td>
<td>
<select name="DB_TYPE">
<option value="">&lt;Select One&gt;</option>
<?php
    foreach($supported_databases as $db)
    {
    ?><option value="<?php echo $db; ?>" <?php echo ($DB_TYPE == $db)?'selected="selected"':''; ?>><?php echo $db; ?></option><?php
    }
?>
</select>
</td>
<td class="desc">
<ul>
    <li>This site supports a variety of databases, select your database type.</li>
</ul>
</td></tr>
<?php

// set up database server
if(!isset($DB_SERVER))
    $DB_SERVER = 'localhost';
?><tr><td class="title <?php echo ($dberror !== false && strpos($dberror, 'Can\'t connect') !== false)?'fail':(($dberror !== false && strpos($dberror, 'Access denied') !== false)?'':'warn'); ?>">Database Server</td>
<td>
<input type="text" name="DB_SERVER" value="<?php echo $DB_SERVER; ?>" />
</td>
<td class="desc">
<ul>
    <li>Please specify an address of the database server to connect to.</li>
    <?php if($dberror == false) { ?>
    <li>WARNING: If this information is wrong, it could take up to 1 minute or more to detect these errors.</li>
    <?php } elseif($dberror !== false && strpos($dberror, 'Can\'t connect') !== false) { ?>
    <li>The server reported an error with the connection to the database, please check to make sure the address entered is correct and accessible.</li>
    <?php } ?>
</ul>
</td></tr>
<?php

// set up database username and password
if(!isset($DB_USER))
    $DB_USER = 'username';
?><tr><td class="title<?php echo ($dberror !== false && strpos($dberror, 'Access denied') !== false)?' fail':''; ?>">Database User Name</td>
<td>
<input type="text" name="DB_USER" value="<?php echo $DB_USER; ?>" />
</td>
<td class="desc">
<ul>
    <li>Please specify a username to log in to the database.</li>
    <?php if($dberror !== false && strpos($dberror, 'Access denied') !== false) { ?>
    <li>The server reported that there were problems with your login information.</li>
    <?php } ?>
</ul>
</td></tr>
<?php
if(!isset($DB_PASS))
    $DB_PASS = 'password';
?><tr><td class="title<?php echo ($dberror !== false && strpos($dberror, 'Access denied') !== false)?' fail':''; ?>">Database Password</td>
<td>
<input type="text" name="DB_PASS" value="<?php echo $DB_PASS; ?>" />
</td>
<td class="desc">
<ul>
    <li>Please specify a password to log in to the database.</li>
    <?php if($dberror !== false && strpos($dberror, 'Access denied') !== false) { ?>
    <li>The server reported that there were problems with your login information.</li>
    <?php } ?>
</ul>
</td></tr>
<?php

// set up database name
if(!isset($DB_NAME))
    $DB_NAME = 'mediaserver';
?><tr><td class="title">Database Name</td>
<td>
<input type="text" name="DB_NAME" value="<?php echo $DB_NAME; ?>" />
</td>
<td class="desc">
<ul>
    <li>Please specify the name of the database to use.</li>
    <li>This database will not be created for you, it must be created ahead of time with the proper permission settings.</li>
</ul>
</td></tr>

</table>
<?php


}


elseif($_REQUEST['step'] == 4)
{
?>
<h2>Select Modules</h2>
<p>Below is a list of available modules.  Modules can be added or removed at any time, but with large file-structures, inserting new modules could take a very long time.  Therefore, all modules are enabled by default, with the recommended modules marked as such.</p>


<table border="0" cellpadding="0" cellspacing="0">
<?php

foreach($new_modules as $key => $module)
{
    if(constant($module . '::INTERNAL') == true)
        continue;
    
    $module_en = $module . '_ENABLED';
    if(!isset($$module_en))
        $$module_en = true;
    ?><tr>
    <td class="title"><?php echo constant($module . '::NAME'); ?></td>
    <td>
    <?php
    if($module == 'db_file')
    {
    ?>
    <select disabled="disabled">
        	<option>Enabled (Required)</option>
    	</select>
    <?php
    }
    else
    {
    ?>
    <select name="<?php echo strtoupper($module); ?>_ENABLE">
        	<option value="true" <?php echo ($$module_en == true)?'selected="selected"':''; ?>>Enabled <?php echo in_array($module, $recommended)?'(Recommended)':'(Optional)'; ?></option>
        	<option value="false" <?php echo ($$module_en == false)?'selected="selected"':''; ?>>Disabled</option>
    	</select>
    <?php
    }
    ?>
</td>
    <td class="desc">
    <ul>
        <li>Choose whether or not to select the <?php echo $module; ?> module.</li>
    </ul>
    </td>
</tr>
<?php
    
}

?></table><?php

}

// create database
elseif($_REQUEST['step'] == 5)
{

?>
<h2>Install Database</h2>
<p>Before we go any further, the database should be installed.  Below, each table will be created, if there are any errors, you will be notified and given the option to return to the previous step.</p>

<div id="loading1"><img src="./install.php?image=loading" alt="" /> Testing...</div>
<iframe name="test" id="test" frameborder="0" width="100%" src="<?php echo $_SERVER['PHP_SELF']; ?>?step=5&test="></iframe>
<div id="loading2" style="display:none;"><img src="./install.php?image=loading" alt="" /> Installing...</div>
<iframe name="test" id="install" frameborder="0" width="100%" src=""></iframe>
</script>
<?php

}


// set up templates
elseif($_REQUEST['step'] == 6)
{

?>

<h2>Template Settings</h2>
<p>This site supports multiple templates.  In order for users to have the best visual experience, we recommend you review these settings.</p>

<table border="0" cellpadding="0" cellspacing="0">
<?php

// check for base template
if(!isset($LOCAL_BASE))
    $LOCAL_BASE = 'templates' . DIRECTORY_SEPARATOR . 'plain' . DIRECTORY_SEPARATOR;
if(file_exists($LOCAL_ROOT . $LOCAL_BASE))
{
?><tr><td class="title">Template Base</td>
<td>
<input type="text" name="LOCAL_BASE" value="<?php echo $LOCAL_BASE; ?>" />
</td>
<td class="desc">
<ul>
	<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
	<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
	<li>The server reports that <?php echo $LOCAL_ROOT . $LOCAL_BASE; ?> does, in fact, exist.</li>
</ul>
</td></tr>
<?php
}
else
{
?><tr><td class="title fail">Template Base</td>
<td>
<input type="text" name="LOCAL_BASE" value="<?php echo $LOCAL_BASE; ?>" />
</td>
<td class="desc">
<ul>
	<li>The system has detected that the local basic template files are not where they are expected to be.</li>
	<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
	<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
	<li>The server reports that <?php echo $LOCAL_ROOT . $LOCAL_BASE; ?> does NOT EXIST.</li>
</ul>
</td></tr>
<?php
}

// select default template

// get the list of templates
$GLOBALS['templates'] = array();
if ($dh = @opendir($LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR))
{
	while (($file = readdir($dh)) !== false)
	{
		// filter out only the modules for our USE_DATABASE setting
		if ($file[0] != '.' && is_dir($LOCAL_ROOT . 'templates' . DIRECTORY_SEPARATOR . $file))
		{
			$GLOBALS['templates'][] = 'templates' . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR;
		}
	}
}

if(!isset($LOCAL_DEFAULT))
	$LOCAL_DEFAULT = 'templates' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR;

?><tr><td class="title">Default Template</td>
<td>
<select name="LOCAL_DEFAULT">
<?php
foreach($GLOBALS['templates'] as $template)
{
	?><option value="<?php echo $template; ?>" <?php echo ($LOCAL_DEFAULT == $template)?'selected="selected"':''; ?>><?php echo ucwords(basename($template)); ?></option><?php
}
?>
</select>
</td>
<td class="desc">
<ul>
	<li>The default template is the template displayed to users until they select an alternative template.</li>
</ul>
</td></tr>
<?php

// select local template
if(!isset($LOCAL_TEMPLATE))
	$LOCAL_TEMPLATE = '';

?><tr><td class="title">Local Template</td>
<td>
<select name="LOCAL_TEMPLATE">
<option value="" <?php echo ($LOCAL_TEMPLATE == '')?'selected="selected"':''; ?>>&lt;Not Set&gt;</option>
<?php
foreach($GLOBALS['templates'] as $template)
{
	?><option value="<?php echo $template; ?>" <?php echo ($LOCAL_TEMPLATE == $template)?'selected="selected"':''; ?>><?php echo ucwords(basename($template)); ?></option><?php
}
?>
</select>
</td>
<td class="desc">
<ul>
	<li>If this is set, this template will always be displayed to the users.  They will not be given the option to select their own template.</li>
</ul>
</td></tr>
<?php

?></table><?php

}

// cron settings
elseif($_REQUEST['step'] == 7)
{

?>

<h2>Cron Settings</h2>
<p>The site will perform indexing of files while it is not being used.  This provides fast searching and reads more detailed information such as Artist and Album for MP3s.</p>



<table border="0" cellpadding="0" cellspacing="0">
<?php

// set up cron script
?><tr><td class="title">Running the Cron</td>
<td>
On Unix and Linux:<br />
<code>
&nbsp;&nbsp;&nbsp;&nbsp;0 * * * * /usr/bin/php /&lt;site path&gt;/plugins/cron.php &gt;/dev/null 2&gt;&amp;1<br />
&nbsp;&nbsp;&nbsp;&nbsp;30 * * * * /usr/bin/php /&lt;site path&gt;/plugins/cron.php &gt;/dev/null 2&gt;&amp;1<br /></code>
<br />On Windows:<br />
Run this command from the command line to install the cron script as a task:<br />
</td>
<td class="desc">
<ul>
	<li>In order for the cron script to run, it must be installed in the OS to run periodically throughout the day.</li>
</ul>
</td></tr>
<?php

// set up seek time
if(!isset($DIRECTORY_SEEK_TIME))
	$DIRECTORY_SEEK_TIME = 60;
?><tr><td class="title">Directory Seek Time</td>
<td>
<select name="DIRECTORY_SEEK_TIME" style="width:100px; display:inline; margin-right:0px;">
<?php
for($i = 1; $i < 60; $i++)
{
	?><option value="<?php echo $i; ?>" <?php echo ($DIRECTORY_SEEK_TIME == $i || $DIRECTORY_SEEK_TIME / 60 == $i || $DIRECTORY_SEEK_TIME / 360 == $i)?'selected="selected"':''; ?>><?php echo $i; ?></option><?php
}
?>
</select><select name="DIRECTORY_SEEK_TIME_MULTIPLIER" style="width:100px; display:inline; margin-right:0px;">
<option value="1" <?php echo ($DIRECTORY_SEEK_TIME >= 1 && $DIRECTORY_SEEK_TIME < 60)?'selected="selected"':''; ?>>Seconds</option>
<option value="60" <?php echo ($DIRECTORY_SEEK_TIME / 60 >= 1 && $DIRECTORY_SEEK_TIME / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
<option value="360" <?php echo ($DIRECTORY_SEEK_TIME / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
</select>
</td>
<td class="desc">
<ul>
	<li>This script allows you to specify an amount of time to spend on searching directories.  This is so the script only runs for a few minutes every hour or every half hour.</li>
	<li>The directory seek time is the amount of time the script will spend searching directories for changed files.</li>
</ul>
</td></tr>
<?php

if(!isset($FILE_SEEK_TIME))
	$FILE_SEEK_TIME = 60;
?><tr><td class="title">File Seek Time</td>
<td>
<select name="FILE_SEEK_TIME" style="width:100px; display:inline; margin-right:0px;">
<?php
for($i = 1; $i < 60; $i++)
{
	?><option value="<?php echo $i; ?>" <?php echo ($FILE_SEEK_TIME == $i || $FILE_SEEK_TIME / 60 == $i || $FILE_SEEK_TIME / 360 == $i)?'selected="selected"':''; ?>><?php echo $i; ?></option><?php
}
?>
</select><select name="FILE_SEEK_TIME_MULTIPLIER" style="width:100px; display:inline; margin-right:0px;">
<option value="1" <?php echo ($FILE_SEEK_TIME >= 1 && $FILE_SEEK_TIME < 60)?'selected="selected"':''; ?>>Seconds</option>
<option value="60" <?php echo ($FILE_SEEK_TIME / 60 >= 1 && $FILE_SEEK_TIME / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
<option value="360" <?php echo ($FILE_SEEK_TIME / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
</select>
</td>
<td class="desc">
<ul>
	<li>The file seek time is the amount of time the script will spend reading file information and putting it in to the database.</li>
</ul>
</td></tr>
<?php

?></table><?php

}

// optional settings
elseif($_REQUEST['step'] == 8)
{

?>

<h2>Optional Settings</h2>
<p>There are a few optional settings that affect the behavior of the site.  We will review these now.</p>

<table border="0" cellpadding="0" cellspacing="0">
<?php

// select debug mode
if(!isset($DEBUG_MODE))
	$DEBUG_MODE = false;
?><tr><td class="title">Debug Mode</td>
<td>
<select name="DEBUG_MODE">
<option value="true" <?php echo ($DEBUG_MODE == true)?'selected="selected"':''; ?>>Turn Debug Mode On</option>
<option value="false" <?php echo ($DEBUG_MODE == false)?'selected="selected"':''; ?>>Do Not Use Debug Mode</option>
</select>
</td>
<td class="desc">
<ul>
	<li>Debug mode is used by many templates to display debugging options on the page.</li>
	<li>This is usefull for viewing information about file system and database problems and to test if the system is running properly.</li>
</ul>
</td></tr>
<?php

// select recursion option
if(!isset($RECURSIVE_GET))
	$RECURSIVE_GET = false;
?><tr><td class="title">Deep Select</td>
<td>
<select name="RECURSIVE_GET">
<option value="true" <?php echo ($RECURSIVE_GET == true)?'selected="selected"':''; ?>>Turn Deep Select On</option>
<option value="false" <?php echo ($RECURSIVE_GET == false)?'selected="selected"':''; ?>>Do Not Use Deep Select</option>
</select>
</td>
<td class="desc">
<ul>
	<li>This tells to system whether or not it should read directories on the fly and recursively.</li>
	<li>If some files in a directory haven't been loaded, this will load them when the directory is accessed.</li>
	<li>On large systems, this could cause page response to be VERY SLOW.  This option is not recommended for system where files change a lot.</li>
</ul>
</td></tr>
<?php

// disable robots
if(!isset($NO_BOTS))
	$NO_BOTS = true;
?><tr><td class="title">Robots Handling</td>
<td>
<select name="NO_BOTS">
<option value="true" <?php echo ($NO_BOTS == true)?'selected="selected"':''; ?>>Disable Robots</option>
<option value="false" <?php echo ($NO_BOTS == false)?'selected="selected"':''; ?>>Allow Robots to Scan my Files</option>
</select>
</td>
<td class="desc">
<ul>
	<li>Some services like Google like to scan websites.  This option will prevent robots from downloading and scanning files on your site.</li>
	<li>This will also enable robots to view a customizable sitemap.php plugin that provides them with the information they deserve.</li>
</ul>
</td></tr>
<?php

// temporary directory
if(!isset($TMP_DIR))
{
	$tmpfile = tempnam("dummy","");
	$TMP_DIR = dirname($tmpfile) . DIRECTORY_SEPARATOR;
	unlink($tmpfile);
}
?><tr><td class="title">Temporary Files</td>
<td>
<input type="text" name="TMP_DIR" value="<?php echo $TMP_DIR; ?>" />
</td>
<td class="desc">
<ul>
	<li>This directory will be used for uploaded files and storing temporary files like converted files and images.</li>
</ul>
</td></tr>
<?php

// user files
if(!isset($LOCAL_USERS))
	$LOCAL_USERS = $LOCAL_ROOT . 'users' . DIRECTORY_SEPARATOR;
?><tr><td class="title">User Files</td>
<td>
<input type="text" name="LOCAL_USERS" value="<?php echo $LOCAL_USERS; ?>" />
</td>
<td class="desc">
<ul>
	<li>This directory will be used for uploaded user files.  This will also be included in the directories that are watched by the server.</li>
</ul>
</td></tr>
<?php

// buffer size
if(!isset($BUFFER_SIZE))
	$BUFFER_SIZE = 2*1024*8;
?><tr><td class="title">Buffer Size</td>
<td>
<select name="BUFFER_SIZE" style="width:150px; display:inline; margin-right:0px;">
<?php
for($i = 0; $i < 10; $i++)
{
	?><option value="<?php echo pow(2, $i); ?>" <?php echo ($BUFFER_SIZE / 1024 == pow(2, $i) || $BUFFER_SIZE / 1048576 == pow(2, $i) || $BUFFER_SIZE / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php echo pow(2, $i); ?></option><?php
}
?>
</select><select name="BUFFER_SIZE_MULTIPLIER" style="width:50px; display:inline; margin-right:0px;">
	<option value="1024" <?php echo ($BUFFER_SIZE / 1024 >= 1 && $BUFFER_SIZE / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
	<option value="1048576" <?php echo ($BUFFER_SIZE / 1048576 >= 1 && $BUFFER_SIZE / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
	<option value="1073741824" <?php echo ($BUFFER_SIZE / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
</select>
</td>
<td class="desc">
<ul>
	<li>Some plugins and modules require open file streams of a specific size.  This allows you to set what size these streams should try to remain below.</li>
</ul>
</td></tr>
<?php

// set up aliasing
if(!isset($USE_ALIAS))
	$USE_ALIAS = true;
?><tr><td class="title">Aliasing</td>
<td>
<select name="USE_ALIAS">
<option value="true" <?php echo ($USE_ALIAS == true)?'selected="selected"':''; ?>>Use Aliased Paths</option>
<option value="false" <?php echo ($USE_ALIAS == false)?'selected="selected"':''; ?>>Display Actual Path to Users</option>
</select>
</td>
<td class="desc">
<ul>
	<li>Path aliasing is used to disguise the location of files on your file system.  Aliases can be set up to convert a path such as /home/share/ to /Shared/.</li>
</ul>
</td></tr>
<?php
	
?></table><?php
}

// optional settings
elseif($_REQUEST['step'] == 9)
{

?>

<h2>Save the Configuration</h2>
<p>Almost done!  Saving the configuration is the last step, once this is complete the site will be up and ready to use.</p>

<?php

// save config
$config = $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'settings.php';

// check for write permissions on the settings files
$fh = @fopen($config, 'w');

// run database::installFirstTimeUsers with new secret key

if($fh !== false)
{
	?>
    <table border="0" cellpadding="0" cellspacing="0">
    <?php
    
    // save config
    ?><tr><td class="title">Configuration Saved</td>
    <td>
    <a href="<?php echo $HTML_DOMAIN ?>">Click here to view your site.</a>
    </td>
    <td class="desc">
    <ul>
        <li>The configuration has been successfully save to <?php echo $config; ?>.</li>
        <li>Your system should now be ready to use!</li>
    </ul>
    </td></tr>
    </table>
    <?php
}
else
{
	// print out config in a text box
	?>
    <table border="0" cellpadding="0" cellspacing="0">
    <?php
    
    // save config
    ?><tr><td class="title fail">Configuration</td>
    <td>
    <label>You must enable read/write access to <?php echo $config; ?></label>
    </td>
    <td class="desc">
    <ul>
        <li>The configuration could not be saved to <?php echo $config; ?>.</li>
        <li>Copy the text below and paste put it in a file named "settings.php" in the &lt;site root&gt;/include/ directory.</li>
    </ul>
    </td></tr>
    </table>
	<textarea style="width:100%; height:400px;">
    <code>
    </code>
    </textarea>
	<?php
}


}

?>

    <br />
    <br />
    <br />
    <?php
	if($_REQUEST['step'] == 9)
	{
	?>
    <input type="submit" name="next" value="View Site!" class="button" style="float:right;" />
	<?php
	}
	elseif($_REQUEST['step'] != 5)
	{
	?>
    <input type="submit" name="reset" value="Reset to Defaults" class="button" />
    <input type="submit" name="next" value="Save and Continue" style="float:right;" />
    <input type="submit" name="save" value="Save" class="button" style="float:right;" />
    <?php
    }
	else
	{
	?>
    <input type="submit" name="next" value="Save and Continue" style="float:right;" />
    <input type="submit" name="save" value="Try Again" class="button" style="float:right;" />
    <?php
	}
	?>
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
<?php

function print_image()
{
	if($_REQUEST['image'] == 'gradient')
	{
		header('Content-Type: image/png');
		
		$image= '89504e470d0a1a0a0000000d4948445200000010000003840806000000a8ecb1a90000001974455874536f6674776172650041646f626520496d616765526561647971c9653c000001d74944415478daeddd4b4ec3301000d071292c582055c0869370807259365c201247e034fc5bccaa7c533e9d486ee0791d3f8d67222b9a28718988d3488c526bbdc800d388b8ca028b2cb06c1ec1e3f82348030fe35fc21f0016e3064a449c64804924070000000000000000000000000000000000000000000000000000004073a0d45a2511000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000fc4fe0d3a83ff8047b3e9fd7de087e3bf91db0c9e41760d3c92f4029a57c07745d57beac4206896c22f3491ca48c83dc489b20bdc06f2a32c94c1ea40aa5d67a6e4b030000000000b055c03422ee9b033759e0b67904d7cd937897055207be955aeb713682c9f8811d3988d23402db3a000000000080c181f6a37cd5905e75b9d7bd1e89d5135a1ff2b145de87745df77ad15b645d7ffd2d32587fddbf850100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009a03db71e0db74fcc06e16d86b1bc132623f0b1ca48045c42c053c451c66977094026ac4590a98957299019e01db8e97086ce1a2090000000049454e44ae4260820a';
	}
	elseif($_REQUEST['image'] == 'shadow')
	{
		header('Content-Type: image/png');

		$image = '89504e470d0a1a0a0000000d494844520000000a0000011b0806000000e3cf15370000000473424954080808087c086488000000097048597300000b1200000b1201d2dd7efc00000016744558744372656174696f6e2054696d650030342f31382f30396b9a7a880000001c74455874536f6674776172650041646f62652046697265776f726b732043533406b2d3a0000004f970725657789ced9bdd71db3a108591d4e1166e012ec47d448df8dd2db8883cfa21e32e5c403ab8bc3e1e7ecac96a97224446ba0eb9331cd32080b3bf0028eefef8f7fbcff64ffb360cc3ebebebd3d3d350d3e3e3e3d05a3b693f1c0ec7f6fbfbfb8f0b52bbc6bdbdbd0d777777c3c3c3c3b1ffcbcbcb47bbee69571fb53f3f3f7fdceb52bb48f79a5bf371ef0486e6e399f393dd3317184e73fa449e5c56f8701975e95e6d97c828927e3487c6e8b9fa611fe653bbf713aedac1cdc66317fdaf3e8c779b09a3d213f7d8195935572474a3f9b85c36fde5dee5c97c08797c3cf3699cfa4ec9a23ead87dec7698ce65a8bbaf057439dc6fff2651ebeaf0b55fcd307bb62476223c5ff5ae363738daf7889fc700fa6fa8a97d14fbbf0218f5fcd55f1214c5dae07e729c72f0cf0ce2f3211cf8e894e88798f257c5e7d4cf63efc60ff6a8d75fb27fe768ceb5afe42ffc187984f6dac69f0403bba80d86be0b94bfef7b1d99ab6843298af33fcef4fe297f28ffee53eecba9c8a7f9eb32e334f8e5fcbeffb84d39cf867acf3d225bfe1298ef025e68c7c701e60cf68b3e3bf903fe8784a768f315deceb21f66e12ffde7e89fcee43ecebc8c8fc955fc63dab17bf8debd75ab444ff7f0ebf965f3e8d7fb5f6fb590abdf89e13e39573b2ae4bf6dfb89e4373e27f9477d1fa031d6c2ff0f720e723bb5f14ffb677e1e37eb670de78e63a773d4cefffe7e59fd2317a5e3dfe830f89fc5d0ab9fcbd2a9e8dfcddaa5bffc3aff32776d4ffd99a3c9796e87f0d4af1ab178031e6fd0ab1745c1f792716111f3cf318e892dfde5123cd8d7fec6757227f85fe8b5ca6663eedf3bb4e581fe39ad125ff301cd7dd66bacf7e9fb1fdfd649d809f5afeffe9fe9ff8d030ea84f781b8ce56ba98f4bfcafea63be8ef8cff1a9ff74791febaecfe8c3d3e7b76b1fcc9f86a7ec7af78197f0fe8923fc38cbf4544ec4a27e5f9e30c7ec4ac74cbfb7fc5dba5f873749ce9256befc22fe4ccb0b33e992ebaf00b9996d012fdeff83bfedf88df362effcdf12b035c097febf6bfb9fc05fc2eff75f077f977f9b78cbf75fd6f5dfe92ae85bf75fbdf187febf2df1c7febf1bfe3eff83b7eca0117f97457c59f89e979b88f63cee6c477aa0ee9d7a74f85df4ef3aefc1ba75f6aaff2c6af297fb4f7b5f1cfd1a7c21ff379f8eeb7461e542ffe9cf8f76fc07c1bf26fbe7bfc5f885f7c636c96ebe5df1cabef92d7947f8fff15f1c3f7f566fb41ac21a10f7999e404c5dc9d2e7cdbff8597d5ca64b540ea4bae8cfb43f5fdb724e33bcb7de04ce4b9415e2b92d51475e18ff53c2274dac2590cbd90f3e2b54b195f5df8c9f9abcac78647cfbbf2ba23d35397fc31a7ccf3cc321dbbbd0fb9fee6d38984cbe953e1071bb7765ae3e3f9969e6f5f9d19baf0db49edd66f79b822e230f6cdceff4bec9fad7ff8353e1973d19af9abb5cda790bbe6f56d512f8eefb58991af2efc89f37f56df91e53f2bfe9c874bf0bd5e9479f0811674ecf50f9efb072fd4089d3ba76c99aa351edf22aea3cf65b9ff2d59c5a6727745ec1dd1cfab735d4f2d50dc2fb3b91dd789da1391ef43e4dac69a1ff032029ff8f138679f8f63e7e8259e77225fd14695adcfb5a3475db12eb1b249e633acab5e3bc51acfde117551d5774ce9afd2f74ea794d9def7bf684b7f175c1affe4767b6d57ecd3335f15ff5e8b3e37fea9a7632e3ff3306f8cff6c1e8878f67a16f7533f3bcdd54b25efdaf19fed9f3df1ef6722ceee7e9ef2b5f65cfcc7f7814c7fe8fb3f3b08b76266170340000000486d6b4246fadecafe000000040000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000029a433a100003f156d6b5453789ced7d5b77db46b22e4f268e235fe364d6ec87fda2b5f63eeb3c4583fbe551144549135d3824e5d87ef1024022d68e63676459196f2dfcf75355dd68349ab8521245c7906c8140a31b8dafaabfaeaa2e80473ff52faf9e8f27675741f27c7c7476a527c3f18eb419fff31836ba1bda8e11262f0e77e22b2d79c9363f1f0ce22b5d4bf60fa6f195e199c97877125fb946329e9c9e41797f0f9a88f94f323a3cbcbcea8fe0cfcef6f4c355efdb5ed88b7aaf7bfd5ed0fbd03beb45c9c1f1111c7f04c7dfc1f17d387ede9bf5367b8750faae374fc68393109bdd3ea6d6b7a1d7e6dc4bfa8383b32b2be91f41f7e7b0a19be94f76e9a4c910efa23fd9a7bdfe21dbfc449b9d63dec0ee90f6c7533a77d8a7bde19836c7ece06404e746497fca0aa7acf5e9845de488b5c73607dbd8cb63ec95960c4e74eccee0c4c0660627266d8670d0808dc136266e9206d83c5dc066dc7bdffb08c766bd796f765d84f4cf1fa1547b26804bdcbbb88ef6e8b1b5b4fee8d74447bf5dfdc9d0b9a6fea818b5d1a03bc6e811c7681bf0390724faf0f723a0f58663f52dc72ac3b00a1decb7048f63317ca8bc161fcfcae1a3e5f0318d3c42b36b8e3183216430842c8690c510b292c9e81593ea64021fa2100e9cb0db984c4ee8401b0c1f720ca7a05dff067dfb08e5757a661a458a560da4ee73288d306a0165e43128e9f8ad8169f8cdc07cc2c1dc01857b0bbf67bd5f00aea0f77bef4def1307744352cadfe0f3fbdefb4a30753e6a75b331edeb9a553c6cb58a616b6b0c49e20344320e1a8f5cc36b8ca5e9190c4b539fb5c6ae7a40bb33069d1731e4e63764506855d3a5b124d935836c5980fe09e3f40ccfca0164d90c203d54742be610690ca3a86aa0a25ad4eb1611a28413b229e2444a750b402dea9618a8cb0ed03194863440df55aa99eedfac9eddeca47a3b7af60dc7e867980b2e0ad1711515538c8e2abb0cabe6f031ee1c9fc9a8cfd87fd25f20fd8702af3764d347a031809ca24d3b346f22e57f6a44f71c2fdd880a115308df8ddaab94e530c8028e9939b31acf9dcb0f4ab8a061070c489c6ada233922f2bf0034dfb64232b61acc9c713a73a23dd11048c16d1c48b2496e1e47846f925a23486ecc2c493f20b2cc2e493f8c5b437c3fef5cb48197e68c5a7782d4b935be736e99606504d8776e1ae031e06acce70c3375d2a8c66c1903b998140dbbd2408e8cf6635c98c7b6c1a0631836c34e9f37a646a178469c836e43408733c8279a4b97f1cdf2ae19b9084bc63e9a0c64c3e3560a39250dd1328d22b41c8696c3d072d8506604881fc25819ca38c78cf75be0785f582b41efd79a1889c750f4198a64a249286ad745d1622832152b84d10e188e46058eb6c6c3003e8f03f81c49ae780ed73cc72a03931d91c004a3b39d524e61a4834af67e6d338a1b696533ebb9582d6930e3441dde38ffd10443633805722ccf346ceea957cf010de637c47ff9709554d278d0174611d6205ca52f19ae2a47e9bb42940614d80bc104af1ed6eb8a93b1229ca6308ecfc03df95c71326f1ca70d81d37bb0482e561818aee2313fb29674d2f894a0316834068dc6a0d118345a43689e14aa105f8d69af3e79aed756bffa5245463643c86608d9d7a2ec7df25e2f6bbcd7755d61e028c18c4d30d90c269bc11430980206535068fb0fd1dda4e59629724d81329d83f9b5d93be29fe6bdf326a6581bd341b7e74d161bb44227bff5902b361cb839eb31a83c66fcb3b586f2c0483576fb14607ac3034d6f88adf2d8a1d548614bf480489a5ace90657ed382215b381849179b46c4835bc5ae3954e9883c1410952b9a1a7f8b8a462446ad4b692bf3d1970826dd9a9ec9767d1649424b952249debc059ef77366febb9b5d736fb890a54c8d2b18a6022cc45106eb1b0ed6092e1970881e08f71c6d2b0cb7d55957c1b2c909e8cdd4c1642aae65ea0aa5a13652e805c7526f10cec0a3646171b00c8ff93ee4e96c93c346cae738ccf3b1704be1c45a0c9f491862c408933d2664a706dc2aab9e2382c2005b0348319255b13413050c5483831a194a7c6dc663446ce2aef130417519ac1ec7d563c0461e0336e2c0469eeaafe38771fa2137c05991f89006984663ee938ec7698873b218ae2b12451aa45b09fce6ac11fc4a884ea05fb9ee5a122bb1b8dd6731ecc14022ec696bd29690662816224d9338024c8a3e668adf14de0702de0b8aa96032ca8726f64fbaa89d265be8c1bc01be4dec1f5b618c3cb322df4e2857ae1c5cc76a076e0a9c1e8b18090168d433c50fc531649e8ed18c7fdd25f9b7d134c5437b71a0e4087038ad3047bf5611fda670aa3c61733fcee68e1c6cf349172c0a3566804ec69c9af93e02adc785949c7a382380f21d7c5a5457bdf723949e01e4b56b49f975f2a5a3cf55be5f0b1b20afa8deb281834564fea3081950c36d387a01fb3fc22734d4d133ac5de4b859cc6ed66eba41ccd2697e0726f9b7f03f1dbaf29945ee0cb92da52666d03eb0d0c2c06c3ec534028ac629f2dfe2dc829627964cd8b619a4e974bd27120fce70c2be9df4e1a8b999de2adb4cb13e39fd050cc88001e92d18eadcf494b3cd7280ca003ee600fe4c16cd9c2f4d920e2a0b1acda2374ade9ef0ab9b05707089b1a5360a9b874ca66b2d4b162229d6ccb385368a55a41fc627d2b4adb1d94451d2e618375b3432bc223d6501a01b44385d3332d3755fbb39c0c53ea559b4664423bb6cecb783b18d531e73eb31ce5b8f71d000c426eee6750340c52a6a1421c8bc72d90b128b6d6a8664ca97636e61bfeffda6f0254e41ef7a312665e1745d84a16e5b0c43278f61d00242a7500f0b8d1ab6f4bb8c55936a619e300dae86b43569cb46afc3876f73d05285638b24d5ee8a5934689534c9e2d9456b6e5d13df2e62155af58be6c2575116cd9160092ff6c1641f987dedcdb97d8d1f46298693d4f51ba71cc9821ecd304df30287e8c214211a5a39e73ad5be36de75e57c4d1816252314fb7f1c53b308d3c82b76587c1e5df3795287efb04811b925e91a793f7353d23573253a5e0e62e64dff0b600cc8a0ac564f7b59dba7c58cc27c3e29f6d6220b0bfcc342f5c4e3437e7cc88e0b2869b676f964ed320d4d212505dd67ae61bbc1fe92f0ac9e5df23374a3bcc136b624d7ccb0703154f1a40b6797b2c04459864c3e0393ab2279cc25cf829c0254ef68a2fe4806786a96dfe3209abdc112e96d6e93e7689ab83466dec4c90308aa52108a68163633bc8258f03e9ba3dbe0f420c309fcc03d3273fea846ac708269f6e8510a995eaf7351e104a3cf8bc8505f29648f8405f38692a431ec305f48bd576173965d73d09baf020a5fc574da2705a686609dd7b79f9acefb8ba6731d74cfc49cfb969efa68bbcc50bc42787dd5a37c72699d2128b4a653e56b12895567e242e54b2d9ab1582c5834609a0ee013387e4101afbac74f6f6200dbcd03b00a867efda491a6a72ae6f40d0ddf54073137ff8c6285b7ae830b999466a112fa5e7eb12b2e9c358ad70f9504dfa898028bec41358c932d73712b468e633375851b746d362d3bedb4f5b1f09cdf932bf30680e78f4854ebeccd25c455cdd35ee123721807911037daacc094692db36f268b4e601d824f2404ff4d419c4d8a54b4c69002832d1e7615385af5aef4d2eb03e82b379884280811b90509e994e44307d20fe9f2cb881b93788b6c4156b22ed595c33a29fc8d4be139ad784594fdfa81161f90813146bc99114c7b3a618f523555ec4236c92bb6624d51a86d914b5a59a0268f11993cb40e5be2122e1e797e1b71e1e48299242d69a9cccbaf94a1bd9f0c0f07975743f94d033189654201b93329893426711cd39ac66f24a8d3d2122e8e210364c8b861c87018ee92160fc7033a653c6665fb6cf30237c950f6e25887f86b1fd06353ba24979c96962cd725837509367ba247cfa03f91588f98716dbc90967c3e082e8e78ce074e7d51ef57e09174f562b8f71c803fde618d1fc0e7bd11be8465c85eb2a2d14f2215e969117fff0a96bdc432edfaede84b369116e1cf504eb88af933156714dbfd48f7afca6dccd15b5425b96439b9994c6e6627b736727bc2e536066422b85b8c9bfca248ef899051d139a70dce594ea2019368d049b48d441f8891886b0168cbc87e4d2cad13a465a71565cb49ce6292b33ac92d331699042ec8fe394ff152c662f139a70dceb916bbea7a27d23622cd6cab80f267b2070d631ee24f8f9f961c5f4e5c361397dd496b19698dc8888ca4f748c53cba911e3f2d39be9cb45c262db793d632d21a12223381472a95ecf869c9f1e5a4e53169799db4da48eb3197d62e7fa7ccefc46fb271f298cba7e88cd3da339693a5cf64e977b26c23cbfb5c967d5a4cfd20965863f146877331ead4a3cbc92962728a3a39b591d38670ee70acb007a455873c2b511df2ac643999cd98cc669dcc9699d57ea67ccdf9c2ac961d3f2d39be9cb4e64c5af34e5acb38dba36c9d4bd8f80f847528979d56942d27b998492ece75ec9150a3792fec0d48146f68192e5d6f4fd5462d3fad295fae933a8ff4e276a04b880e07466ecfcced59b9bd29b69a247b14c05e464dbf93d4f4239c35a654fd97946dc092343265350b35c4d7b4d0903544dbb23335abd2c088d447d1e4dbbac80da8f952003f530096a04d8f9541bc957522d48c2028bbff590cc5f9425fad5909f30d5fe8aea04e757997b265681d8d523ff6285f106a49403b457d333433caf74ddb32bcb4540f7d570ff3a59600479f5b31ece54a9db4d009e691a6e70b5dbbbc615ded912abecfaffb77a5145fd23cbc14404f38405812d298b9a0a72c556a2a54394d333dec7fb1ca699ae1076e99ca01a53b6a5da7aaaa5b5155577bd468c4ac75f7ef4a219e728560a68020cfbad9aa7c3a96a6104dc3ffa5dd2e12dc6d5de4aee1c59c8ef794d379d13be1cf88ff524f4a86af05b65f42266ee164718d76ee9ab5055767fc5d07504aa955bd6a0250b376ee0aa00d89b53128817e48068c55d41bdfb51c5db1ed4c71b7e1cc8b94f9df13a54e64cc75a7f046e6f12c8c668b98de4d17ee4a1c0fb838a437314359cde40937e5384efe86b3d9c7d33c4d9d5fb2d9c77154ed94661face82a85aee42f61f5b2910fff1a4e9e6bddfdbb560436792e4e99255453de1feef936a4ac26eddc35654de87b182e15ca2a5431dff4a1e3652a6619f85ba662a11d9aa15ea2628eb3a8bb998acde6f85b0802aab5d7d4215bebeedf951a3c146af03bcf8cc2afd7f9ad9e2c172d3e432e5c209bacc3465061a8abb6224c3a32d5943a6ec5a6c467d6f79b08feee0e079757bb43695d734eaa704049cd1888e9c3df4b7aa30c5381ef493950057e1309faec7c6205fe7ad6ddd1e4f26ab0b38b7f7e22c379b717d3a3dca8340730cfb2b4e833fe42e0c1ce7338eb6bae50a0ff52dd87b9ba47f4f2b183de80d7f9bfbdab9e4ba54e4f875fad67f47e84cf111cc14f786c466f80f1e0980b251afdda74a60b7f7528c1bd2477d58decce7bd3de2754787ec5ffd3d372673e90cefc999e58024cf8b95f618f72673f96ce4e539ace99e521eab83d5ba9b30bad828341af13a00768614bebfe257d7a4cefb8f9855c94f4bbd5de51bd0fa28699abf188dec1f8a1f76be9f9ea15b2f7370ef84b9d02ca1b4971fa0b4943c52aab257a289d6f2a77be01bd794b66f27c410a46eecca7d2994794aa79c15fa37c46b3555a4b576ab187a2729a9e111faf75bff75f807fcc35277f474fe889fb3fb88f8d6363b6507f03ea6bd2afd98b152cf7c953aa6e21967ed5161e520b6fb9bf55d47fa9b65273420f29cee82e8a6a4a3d57b0dbe55ff9055ad21b9287b7786df5de17356242bafc074822ecfd0f1be5bcee3de82d06b53e2c30429fa47641e367429a77513a3e9fa5cfacf233cf4b3556adb9216aaae35a3df3ff0112bf42ff8724853979bfe75c1a27708db7a0bbec8568bf814ebea7117f0ec764663b85f38fd9e3a2fc2a0f251ede94989888bb0567dfe79c3d644fb075ecdcb1f3adb3b3ca151d3b77ecdcb173153b8f29eff6978e9d3b76be7576b63b76eed8b963e706ecbc21d8f9235d0fc75cc7d01d43df36433b1d43770cdd31740b869622d21d43770c7deb0cad726dc7d01d43770c5dc4d00f3843bfa2b1f70aaef14bcfe838bae3e85be768abe3e88ea33b8e6e61454b1cdd3174c7d0b7ced066c7d01d43770c2d18ba4093bbccbb9271df71769779d77176c7d9b7c1d999765e87b3bfacccbb8e9dd7819dbbccbb8e9d3b766ec3ce5f46e65dc7ceebc0ce5de65dc7ce1d3b3761e72f2ff3ae63e87560e82ef3ae63e88ea1db30f4979379d731f43a30749779d73174c7d04d18fa4bccbceb387a1d38bacbbceb38bae3e83656f4979379d731f43a30749779d73174c7d019430fe02cd47f499ee2b5cf8ca1b3f7d0bfce9db55ab60e80dffc9e05bf3368cfbb11b6aed6625507032503ec41ae765d9eb57c2e7bc96ac62e96c20df2b9653ae756dcc922479a8080bd12dd4bf56933a72b6d752f5da3e6b3ca17a76b8e128fbf395df35aeb1a5a23c667ac6d4fb8b6c9f38e6a8d7ecbf50df38a61def8d3e7ac998ac694dba26adeebe76b89aaf771d7966897b1f6395aa2ba32b63b4bb49c9f1f657c0a0c2da17d0d861ec315ce08d13f3743abb666c7d01d43770cddc50a6e96a11f677cda9b5572f4d39c1437e9bed85715becdc50c26249d332a936b6ce1ef0267ff278ca43ef43ba6bb62a3e435f4ff9c460a8eab3f60ff42dc33b2f9ff8adedf23eeddc4bf39aceef50265e47f05be5c7edc7fdb9b35f476ee4169f5585135ae7e3609c1bfd4a0d4a759614eb389450c90ce26e87506f01bc30c92c61ff06c0ff663e0ef199c9fac4043ea24ba8c963cca3d1b7a7731259bcfd431cdd13893bbf06bc1f9a9144cf814921c66825d3cb202629217ceecd78b29d9055cd5c59416adc8226d5946f71ee45a4bcb56ab7706e99407ba065c433a86a31ac7f9f27ad7c4ea731479d55b64ea7a76535ba66c2ebe9d99d25a811616eb4d5e031fc128998185f191ee67534286e9de37f273eb2b9a0bebb571069a678356a12efae4c5201bce405b54cfc613ac856c893c3983ff686dfa2b998bf2f8b5c3fe19d8aff86dd67f07cd435bee235d13d1c4b1526c9d3c003ef948bd0f28d7f3238eaa521bf04341bb4d6a7e47b32bf358f8f86b54ef11d4784be70afb44d18862dbf6aef4cc048d414d0989cb66d48e4fbc27b31e96cfa0158dec228deb24eaa34dfab70a3d6ba22bedb4ef6f80f8f98294ffae32f54aade4b6128b84c4f4b593583b7cf3e3362ca8d964fc3da57127b75e5aab95ae6cf40ec5dcfaa144239e40c97bd2cb88306433e36bd8ffa51796f6375fe70dbfeb7cadbfd06ca0da0933c2e5a2e1951e4be737bfca0694237ebfc15fb57d5db166cbee7e5e1aafaabafbac56f3bb2fbf52d9dd575f45bd7bb97d5d992f8aeffe971effdeeb8a19aa1801b56651ff9e14a05077c5a7854834b9dac31c1a8bd751636cc588843df665a665f36e191ef97a45fd7b5c8046f5d59e1462517fa5073924d46be86b31b3a33f6241794cb304f3a375e8ab5d60411a776a4116716b3b767e08e51f292eb829b7d53174c7d01d43770cfd5930b42e18da5a3b862ee3d7b636f40ee9cd2549b4ccbba7314cf8bfef9d9125ff21d91bc145f646d3cbab17873bf8d5c22fd926c98e19b6cd8ee28744612231126eb4dd87a95edd68abebe157ce855f69ae9d5f59a44789a243300b5e5f2aadb4fb4176044ad91dbf5d710c7b4672b028421d51e43aa05c075f8961a35c835c3e04ad2d5366e55c91e1bdd275e5a258f27d90ebefb4268c52fe24187a312760435af746ff7c563a07dc56ecb8485e7999df83f6f1dda67321e5ef78f4227dcfe92667c56da8fd3bae74ac4d9c0847a6097f512302b2ff23d85ab48629cf2e36ad6034cb8db91d49d4635a279507d4a3777cb59ce5cc2c5aff77258998f28f3492474ca8c7b46a9cad686a24091c7b773bcf17e39847ff6bb1aa99ae1bbfeae11b177ea3e71aa864a511d3efe01c76fdd4ce3b2f8cf97d0528aa967d51cd7fc136e039359f445da3753681cf33d571cdc61173a741332b66b18794578073a6438c1dc2764e1c3ca3516bd2485c85d4cb25582df92774df6904fe6ea4ff8ce49cadecc81aa0669bf88a0cff5a5ab73c0ba5ad16d820478ff8d42109ff48e7b3753c8b5836a6595aa7cc139b3250e6a4097328c333828519f976b4a05a9ad59af05d6f0fdafa4851fa3342ad4e1bfe4afe995c43c6ff9c2c98332ce7bdfb1eb0d8220e2dff75d6c28ece7b759af0eaccdeba7975f552ab967ab6bf3e33ad0e653145262ce151a7decc3a619f6157c7b1633e16704eba1b8efd5e8c47ec43db59f687d2da3735d3ce48ea26e53e5964e91a74458f381657466de258946d4c2c3b235bcc223d8849eed18a38b64a9ad59ab021cede2414ce0bb2edd763dc196b3cee8a50cce37e9f67ac9d534ef93b61e5e68eaea10c74ca4a8d2916ca32867cb23fdd0519a4f96b772583722c9b48e229b4f88e9e8460259b225f6e959cf803b15ed68bd71469fe40cf63df84f552d57e11ebda0a6bfeada2fe45c3a749da8e7b6b8dc77d9dd634d13cf5e8ba8cfc88b0c58802e6b0b2f8027be26931bee0dea9145404d5b8ce31c56b31372e65dd6df28737b39235cbc69a138a1a65008714d189085f936c8f88dbfdf8d7a6e79cd29c62f4ede71421409b6435ac5b86653e9a12d059afe13ae959cd18e787c29a1ff9567d5a239707a98c3df6dc613e433c7df7c03ed57aff277fce309f3b7df36f257216ead43f69a82b35ea9e34444bbe6d66bbba5adc3d6bc8d6c0bb670dff3ccf1a7a8af6dded13145f973c3551ccc2e9fbe18ea97db42857bdc2f9e763e2f6cf7cdf3e132f3e6fd97171c7c57f3e2e6efe34db2a9ea92ce3e26f00e5b7647fcf6084a64f4ee13db1d6ce498b501a9bb933d7c5479ad1aaa74b8cec938fe450f435cb4af1c847c2952fbf273f578dff633a77356b604d50cd6b0c72e8a702ff0647924fbe1daef5fb0a629168afbca64f316ab3812e7cb396528f6905cce2b1c688af7afb5276237b9a5e03bdb8dba7e9bf5923f97e4f99179fb8d6b1a7a53fc1678b2384f997bb5ceeb27d816b098c99d72b3e8252d669647bfc19b5989e990e73b12987b2904c5ab3c3bf6cdf22be58d5d8af427379193d5556d5a7d41becf79f4b4e2cef703591dc6a449797d50659c5e7cc4ff9d3c9078fad66856d11c5bc4c7ea0ccbeb31e5bfb99401fcef827b4dfd1b693a5f26d9689f659cac40549b8b432edd20a35fe75c816b269465c854c1631cc4be421613fa7ac5db4c0d36cd8f439e911d9ec17c4896f7aecdd9ee8d95dd2b892afbd687fdc6e3c00d79ae6e4cfc4e4d9e3aae79c6aa43208c8ca70691468fc6d0906b7497c28c1ecbcbc0cbe262b3092bc74d5b3bfade7a5dbe08c3f47131061f28afe8eb6a79757fd9dc3b3ab38d6e82719b23df6930c4742dedfd20ad1ebec2d425cd2f7a423a70b47c68393f00a5a9df6cf70b33ba4cde4e8ecca80bde9d9959e0cc7033a653c6665fb6cf30237c9f445fff22abdd01ea7ed7770133f5d5efd3c82733c2dd9e7dbe9e415b407f7303d805e4f0f0667576e3cb3627a6e7bfa6278330d25bb2f469757c3a329dec2ce21757a74087b0ec009a7730003379a9b5e0a67e04433d34de432b1a769b6ef8a3d7f16b9e63c2b737c5708c5b723dd32d23dc376678ea8e77881695b523dcfd5c5f56cb87a20ea05eedc71a4334dd790f666ce2cddb33ddfb37d51e6b99613a67b9e169a96d8d31dd77582ac1557772271661c4696e8996785b6e54bd70bb3fb0394245cfc20b24cd1173f8c6c19173bcc30333dcf714cd18a1105a67405db76851c74dff51cb9cc72c59e1306b66d4a3d7332048d99a76598f956a459a2ccd43dddb10566733fb4c5f56cc3776c818ba3f9335b96bb9bf5ccf2bdb92dcb3d72625116fba62d9000a0232bcedd6d24b519bb026b280833b9075ae499426256e86bd9f5acc8d7b32be8a1eb67d274e6808bd01068c2b244991784be25eb92e66608424fb356bc300c2c57c2c5b245af0dcd8d1c2fb727ce748d20c8eed69d87ba25eab97ae0db9a74f520c3ccf583d8127764ce3ddf11f7e0c48197dd9161b8a1348edcc090b5c08edcecfe6cd7ccf4dab2bd9923ae6e6bf21d11f2b29ee96e90004d1c9f5dc1a019216b20bf8ca6c78c3e8024f5647b74c83613da6cefb0cd8036136098399c39c00a7bc8375af28fd13fcfae6cdc4ed8ee09db8c904ff78607b8f9c704cf0960bbcb76a7d8dc3f267de2dcc31191ed314e067b93433c763839c5cd806d0e2744ce3b9323acb6bb3341c63e7e493d3c9cd0defef4081bd99f32ab60403614ce517fd0969ed0485e0ce9dc1747d4ffe9989a839ab87931d8a6c6872fa0815e727c645d5ec11f6454dac46ca3b38da66c603bc4f36166b113da804db67bbc83dbe9f6215d6ef4336e5e6047f564a77f4ad7d9e91377eff4b7e9e8609bf60647975787c3697ca56dd9c9f464c43e8c0ff891fe09ff90ecbc200893a363b8fcd1f180da4c0e8e08fcd1c121dbe0e1ffa6378459645b606e55407686c1337d2d8a17057cc53f244bd002bb644e512c87221c016507d88038f42e393864827a09523bdc7e0933f84f7b78e0744cfa73c81d939fa189908c81809c99f3e4f090e0389ad079473bd4cce08084b9738833fd2e36b9f3131edf3dc46b25c9f303b8bfe7eca42459b89ec6af773fbb0e5c53cf5d4b63d7d2abaf359aee8b039d61b28461d21f8fb0dba32991c3f0644ad0ca71c053b01e4f01845db087d338a07cec4bfb7686eead8dd7fa6e86f13128e0cc0ca399e10357be88af7e44ce7c095b2b19ed0c71ac8f26ecf068c20feff2fd5dbe3fe4fb43bedf077d1df5c1c43e98d004d89f1ce223b7a31130ba09c5e983b73f3ae9d3b8bc1b3e4c0993417ca5eb5b9ee71a6ed27f054df55fd14cd0df7e456c26d5d7597d5d4b5e8e70bb65e28f95bc9ce09eb7e5d04fb2337e8edd6017b17c9d5fc4f7b7344b33fcdacba48f076b3e6fc3f60dd6061cdad234c384fda236a0951dc402998e634120e808c6220aa3f104ba953cdf45b8b674c34e06a7445c193c27c7d48a5cddde3274cfe57dd4b64ccb849ddaa6f26d585b960e06a39f36e2b99eaf39f5ad18b956cc2dde0b7dab49174ca5b2669a8ecf456aa0282dbf4117ac5c2bc696ed199ac95b81360dd704cfa5b6155b6945b77d07ec59de8a6741df8cfa569c5c2b80828727512316dfa96dc355dab01c7018b98ad85b9eedc1c8a86fc5535a3174cb031ca815774b6318d5b6e22bad68b6a383d54dad785b96ed5be02bd4b612e45b1163b6a6220ed40275d7b71c81a9d540cf2c5549f9f575a351e5bc924a4c50531329a2a0f7cb8c759821a6dbc027bb2744c7db7b47fcb062d8257be3c1e5d51e9238f80ec8e1b041ee361dd8be645bee2231624cf606602dec0dc8c0db1bfcc46c35b2cef606fbe8500c9e23919d4cc8c43a9990819d8c063b70d931b80341f27c7cc40cad1d6933fe273845baee86b6638449fec50b3f1f0c48fafb688f1b9e998c770109d748c693536cbdbfb723198923b433fbb2a51892a5d8a74496b35e2452864391287a4ee9ae87145e9ea756627f9bfcb4fe36f41a5cc9a43f3800472ee91f1da153d63fa29be94f76e9a409b9207d662ef6fb876cf3136d768e7903ccdeec8fc9d0ec0f09a1fe9064d83f66072760cd8167df67166a7fca5a9f4ed8458e587b6c73b08dbd3cc65e81417fa26377062706363338316933d4d1b21d0c0db631719334c0e6e90236639ed68baff4985d1721fdf34728d59e342dea1adaa3c7d6d2faa35f131dfd76f52743e79afaa362d44683ee18a3471ca36dc0877da5e33925bbbf11cb560cab0cc32a74b0df123c8ec5f0a1f25a7c3c2b878f96c3c734f208cdae39c60c8690c110b218421643c84a26a3574caa13f46ea3100e9cb0db984c4ee8401b0c1f720cd1cdf9778fbdf2b74ecfc03a2b50b46a20759f436984510b28238f4149c76f0d4c70c71a81f98483b9432b78ecedd5e815e21b543e8915ee4c29592edffb4a30753e6a75b331edeb9a553c6cb58a616b6b0c49e20344320e1a8f5cc36b8ca5e9190c4b539fb5c6ae7a40bb33069d1731e4e63764506855d3a5b124d935836c5980fe09e3942d1fcb00593603480f15dd8a39441ac328aa1aa8a816f5ba458428e1846c8a389152dd02508bba2506eab203744c61391ca0ef2ad54cf76f56cf6e7652bd1d3dfb86638431f18b42745c45c514a3a3ca2ec3aa397c8c3bc76732ea33f69ff41748ffa1c08b3d56195168f6ada24d3b6219e75323bae778e94654889842f86ed45ea52c87411670cccc99d578ee5c7e50c2050d3b6040e254d31ec9117b7c0393045b21195b0d66ce389d39d19e6808a4e0360e24d924378f23c23749ad1124376696a41f10596697a41fc6ad21be9f772edac04b7346ad3b41eadc1adf39b74cb03202ec3b370df0187035e67386993a695463b68c815c4c8a865d69204746fb312ecc63db60d0310c9b61a7cf1b53a3503c23ce41b721a03ba7d5ccf7e241fe76be59de35231761c9d84793816c78dc4a21a7a4215aa6518496c3d072185a0e1bca8c00f143182b4319e798f17e0b1cef0b6b25e8fd5a1323f1188a3e43914c340945edba285a0c45a6628530da01c3d1a8c0d1d67818c0e771009f23c915cfe19ae7586560b22312986074b653ca29bde73aeafdda661437d2ca66d673b15ad260c6893abc71fea30986c6700ae4589e69d8dc53af9e031acc6f88fff2e12aa9a4f1a02f8c22ac41b84a5f325c558ed27785280d28b0c732033e479c8c15e134a555f9df3f5b9ccc1bc76943e0f49e9ed95c5d60b88ac7fcc85ad249e35382c6a0d118341a834663d0680da17952a8427c35a6bdfae4b95e5bfdea4b1519d90c219b21645f8bb2f779f261b5f7baae2b0c1c2598b109269bc1643398020653c0600a0a6dff213d937641cf3a01d71428d339985f9bfcb99b5f293db08129d6c674d0ed7993c506add0c96f3de48a0d076ece7a0c2a8f19ff6cada13c30528ddd3e7f3b41f65d8f2a76683552d8123d2092a696336499dfb460c8160e46d2c5a611f1e056b16b0e553a22b31739942b9a1a7f8b8a462446ad4b692bf3d1970826dd9a9ec9767d1649424b952249debc059ef77366febb9b5d736fb890a54c8d2b18a6022cc45106eb1b0ed6092e1988b7f9a7eef939cfaeacb3ae82659313d09ba983c9545ccbd4154a436da4d00b8ea5de209c8147c9c2e260191ef37dc8d3d926878d94cf7198e763e196c289b5183e93307c4f799ef2f3958b3c578d68e43586142359154b3351c0403538a891a1c4d7663c46c426ee1a0f135497c1ea715c3d066ce43160230e6ce4a9fe3a7e18a71f72039c15890f69806934e63e296690b310e764315c57248a3448b712f8cd5923f895109d40bf72ddb524566271bbcf62d8838144d8d3d6a42d21cd502c449a26710498147dcc14bf29bc0f04bceced4aefe82d0e0dec9f74513b4db6d08379037c9bd83fb6c218796645be9d50ae5c39b88ed50edc14383d16311202d0a8678a1f8a63c83c1da319ffba4bf26fa3698a87f6e240c911e0705a618e7ead22fa4de15479c2e67e9ccd1d39d8e6932e58146acc009d8c3935f37d045a8f0b2939f57046fc25328bea8a8f438ce0f8bfebd792f2ebe44b479fab7cbf1636405e51bd6503078bc8fc471132f43540efe8e1fd1ff96b03027aa568cd22c7cd6276b376d30d62964ef3d95759b1a12b9f59e4ce90db526a6206ed030b2d0ccce6534c23a0689c22ff2dce2d687962c9846d9b419a4ed77b22f1809ebab99df4e1a8b999de2adb4cb13e39fd050cc88001e92d18eadcf494b3cd7280ca003ee600fecc5ec6c69726e52fc4fb5638d94da2374ade9ef0ab9b05707089b1a5360a9b874ca66b2d4b162229d6ccb385368a55a41fc627d2b4adb1d94451d2e618375b3432bc223d6501a01b44385d3332d3755fbb39c0c53ea559b4664423bb6cecb783b18d531e73eb31ce5b8f71d000c426eee6750340c52a6a1421c8bc72d90b128b6d6a8664ca97636e61bfeffda6f0257bc3293e7b880ece591186ba6d310c9d3c86410b089d423d2c346ad8d2ef32564daa8579c234b81ad2d6a42d1bbd0e1fbecd414b158e2d9254bb2b66d1a055d2248b6717adb9754d7cbb885568d52f9a0b5f455934478225bcd807937d60f6b537e7f6357e18a5184e52d76f9c72240b7a34c334cd0b1cd2c3bb05888656ceb94eb5af8d775d395f138645c908c5fe1fc7d42cc234f28a1d169f47d77c9ed4e13b2c52446e49ba46decfdc9474cd5c898e97839879d3ecdb99e80dc495ea692f6bfbb4985198cf27c5de5a6461817f58a89e787cc88f0fd9710125cdd62e9fac5da6a129a4a4a0fbcc356c37d85f129ed5b34b7e866e9437d8c696e49a19162e862a9e74e1ec52169828cb90c967607255248fb9e45910fc9a967734517f24033c35cbef7110cdde6089f436b7c973344d5c1a336fe2e4010455290845340b9b195e412c789fcdd16d707a90e1047e60fa42e14ac40a2798668f1ea590e9f53a17154e30fabc880cf59542f64858306fd8db12e88d0b6aeabd0a9bb3ec9a83de7c1550f82aa6d33e29303504ebbcbefdd474de5f349deba07b26e6dcb7f4d447db6586e215c2ebab1ee5934beb0c41a1359d2a5f9348ac3a13172a5f6ad18cc562c1a201d37400b337d5bf6df0f8e94d0c60bb790056c1d0af9f34d2f454c59cbea1e19beae088bd6d87565c6f5907173229cd4225f4bdfc62575c386b14af1f2a09be51310516d9836a18275be6e2568c1cc766ea0a37e8da6c5a76da69eb63e139bf2757e60d00cf1f91a8d6d99b4b88ab9aa7bdc247e4300e22216eb4598129d35a66df4c169dc03a049f4808fe9b82389b14a9688d2105065b3cec2a70b4ea5de9a5d707d0576e3009511022720b12d229c9870ea41fd2e597113726f116d982ac645daa2b877552f81b97027b275644d9af1f68f1217b53b12098f674c21ea56aaad8856c92576cc59aa250db2297b4b2404d1e233279681db6c4255c3cf2fc36e2c2c90533495ad25299975f29437b3f191e0e2eaf1abcef6d038eab5f34745a5a72ddf7bf0d652f8e7588bff6013d36a54b72c96969c9725d32589760b3277af40cfa1389f58819d7c60b69c9e783e0e288e77ce0d417f57e051e49572f867bcf01787c9322367e009ff7f07588f079477a2f9f54a4a745fcfd2b58f612cbb4ebb7a32fd9445a843f4339e12ae6cf549c516cf723ddbf2a37e99b9715b9c925cbc9cd6472333bb9b591db132eb7317f5f5d40efc5cb4bef899051d139a70dce594ea2019368d049b48d441f8891886b0168cbc87e4d2cad13a465a71565cb49ce6292b33ac92d3316d3efa97b4ff611c74b198bc5e79c3638e75aecaaeb9d48db8834b3ad02ca9fc91e348c79883f3d7e5a727c3971d94c5c7627ad65a43522233292de2315f3e8467afcb4e4f872d27299b4dc4e5acb486b4888645fc5964a253b7e5a727c3969794c5a5e27ad36d27acca5b5cbdf29f33bf19b6c9c3ce6f2293ae3b4f68ce564e93359fa9d2cdbc8f23e97659f16533f8825d658bcd1e15c8c3af5e872728a989ca24e4e6de4b4219c3b1c2bec0169d521cf4a54873c2b594e663326b35927b36566b59f7becab3ed4592d3b7e5a727c3969cd99b4e69db49671b647d93a97b0f11f08eb502e3bad285b4e7231935c9cebd823a146f8a5250312c51b5a864bd7db53b551cb4f6bca97eba4ce23bdb81de8f2d7900c8cdc9e99dbb3727bf4051c49b24701ec65d4f43b494d3fc259634ad57f49d9062c49235356b350437c4d0b0d5943f0bb6b849a55696044eaa368f26d5de406d47c29809f29004bd0a6c7ca20deca3a116a461094ddff2c86e27ca1afd6ac84f9862f745750a7babc4bd932b48e46a91f7b942f08b524a09da2be199a19e5fba66d195e5aaa87beab87f9524b80a3cfad18f672a54e5ae804f348d3f385ae5ddeb0aef64815dfe7d7fdbb528a2f691e5e0aa0271c202c0969cc5cd053962a3515aa9ca6991ef6bf58e534cdf003b74ce580d21db5ae5355d5ada8aaab3d6a3462d6bafb77a5104fb9423053409067dd6c553e1d4b53087e7b4745b78b04775b17b96b7831a7e33de5745ef44ef833e2bfd49392e16b81ed9790895b38595ca39dbb666dc1d5197fd70194526a55af9a00d4ac9dbb02684362eddfe95bb2cf2560aca2def8aee5e88a6d678abb0d675ea4ccff9e28752263ae3b8537328f6761345bc4f46eba7057e278c0c521bd8919ca6a264ff63d4765b38fa7799a3abf64b38fe3a8da29cd3e58d1550a5dc95fc2ea65231ffe359c3cd7bafb77ad086cf25c9c324ba8a6bc3fdcf36d48594ddab96bca9ad0f7305c2a9455a862bee943c7cb54cc32f0b74cc5423b3443bd44c5d22f182b56b1d91c7f0b4140b5f69a3a646bddfdbb528387420d7ee79951f8f53abfd593e5a2c567c8850b649375d8082a0c75d556844947a69a52c7add894f8ccfa7e13c1dfdde1e0f24afa12e0c724d6d7bd034a6ac6404c1ffe5ed21b65d275b1b9783e27e85dacf84b815d2a75e80b8035fa2ae0eb7f29f046768fb55fea9b9df9333d9b74d17bc3cffd0a7b943bfbb174769abc74ce6c0c51c755bea4f73120304357825e1c408fcac29656f84bfaf438fbf25ff12d6aefa8de0751c3ccd578446f5bfcd0fbb5f47cf50ad99b1a8bbf9cf82f240d15abacd6e2d713ebf485e579294c886c7ea77baf92c253e9cc234acabce02f4c3ea37929ada52bb5d8e34f399d5ef80ae8fbbdff02fce3f42ba095af691e50e49779d3380a66b55f216df66205cb7df289aa5b88a55fb58587d4c25bee5915f55faadde2cbafefe77bae60b7cbbfdc0bb404467e4063aeeede17356242bafc074822ecfd0f1be5bcee3de82d86af3e2c30429fa47641e367429a7751aa19cfd2a753f999e7a51aabd6dc1035d571ad9e795b5fa39d31eea6c4b944d12dd87983b3f32b1a7bafe01abf740cdd31f4ad33b4d93174c7d01d433760e8078b0cdd333a8eee38fad639daea38bae3e88ea35bc438c6f42444674177ec7cfbec6c77ecdcb173c7ce2d621c63400daf8763ae63e88ea16f9ba19d8ea13b86ee18ba01437fcf197a026da72ff162e753e6408f7d8563c7d91d67df36671b1d67779cdd71760bab5ae2ec8ea13b86be758656b9b663e88ea1bf64862ed0e42f32f34eefd8790dd8b9cbbcebd8f9cfcece99765e879dbfbcccbb8ea1d781a1bbccbb8ea13b866ec2d05f62e65dc7d1ebc0d15de65dc7d11d47b789717c1999771d3baf033b7799771d3b77ecdc26c6f1e564de750cbd0e0cdd65de750cdd31741386ee32ef3ace5e0fceee32ef3aceee38bb8d55fde564de750cbd0e0cdd65de750cdd3174c6d003380bf55f92a778ed3363e8ec3df4af7367ad96ad03e037bf67c1ef0cdaf36e84adabb558d5c140b1ee1ee46ad7e559cbe7b297ac66ec6229dc209f5ba6736ec59d2c72a40908d82bd1bd549f3673bad256f7d215113eab7c71bae628d19f9bd335afb5aea135627cc6daf6846b9b3cefa8d6e8b75cdf306600f3c69f3ecfd85434a6dc16557ddacfd71255efe3ae2dd12ecbf873b44475656c779668393f3fcaf814185a42fb1a0c3d862b9c11a27f6e86566dcd8ea13b86ee18ba8b15dc2c433fcef8b437abe4e8a739296ed27db1af2a7c2bf96d0f724ffda565ab656a83be14ce038e03bf05f41859d9805f4d30351ec373f06e529df188db63f2f990af93d69cea28cc52cf776a866c53a628d3f4dbd1436b057a58ac37cb68e0a35c4b7717afb2b91510d3fc8f56820bbf169cbfbc16b68d57d9053cd8c5ab162dd4226d5946f79ed2487f4b2328d75a6f0b7f1734f03f4173fad0eb98ee898dfcd7d0fb731afd6855fc01fb17e28e5137ff57f4fd1e69d226fecd2175af1728787e05fa9447f3dbdeac61ace71e94565b0a2a0bd58f8db0e743ad08fea24d3c275bda22fb271d1b1873c39111136f336dc6b391c97144cde0fc6405fa5127d1bc963c024d9e8185f19190dd944668fa4542dbc4f0efa1ec5030fc8705bd7802f5dfe388237be73567e6d7b0ff4b2f2cb54ef375de702b385feb2f809fbd304fcd685eba6878a5c7d2f9cdafb201e588ec6ff0576d5f57f8a4eceee7a5de48d5dd67b59adf7df995caeebefa2aeaddcbede7effebb92bbffa5977e2574990d5a86805ab3a87f4f0a50a8bbe2d342249a5ced610e8dc5eba81e543122618f7d555d71ef541cb31ee6eb15f5ef71011ad5577b528845fd951ee49050af91c7e176668b7ab6464bc482f298a21b3a59323af4d55a887c789cad03b278d0d699c17ff446fd95b07519bfb663e9bf01cae72286c06df6dedf55bb7fa5737abd944cc01b710ec98a6456a60f52d073f62696a3a66a34efa2947c8aa1cc686e55e353b723a576f8e6392e2ca8f99a5ac7b1fe4e78638b5c1529ad97d66aa52b0fa4af0bdce438bd5db1a73123e959649944e4e5061475f4154f03b521c8452629ca436b9c7345f2f74a233c457ee77dd086df293a83baf1498ab6a9676e481128447f56caa8b7e56716c9ab9dcc37e008ce2b97a47fc52cf080cdf134f2dff7ce48f33e247b23b8c8de687a79f5e27007bfb6f025db24d931c3b6d951fc9028968a98296fb4dd87e9bc73a3adae070fce050f9a6bc783457a94283a0456f2f5a5d24abb9ff5f64917fe0ede0ec63c3fd2dda09c903fcbb4fd0dd79f7a26463b75b1dd2635bf233fac3df73f821a6fe95ce1c92aba561c035e0f0d9e090d36d64e839be84a3bedfb467e6a636d6cab1959ee16c5937d6e0123d24e81059c462beec602cee3d7765e936ce69291de4526bac8441799e82213eb1899304464c25ebbc84411b7e6d9f91eb48ecf2dce051f7fc73de2f419c64d1ed9d886367ec748f4dacc8f382b9af0176d8f80e410c1d6a228be2c079b56b79ae5c6dc8e1cea31ad93ca03ead13bbe5ace72661623f877258998f28f3492474ca8c7b46e92ad3a6a2409f4f8ef764414e39847ff6bb1f2c8b0cff6d7076f1dca6262724bc44653bf739d1828c3ae1ae38d741ff038a2d61733edd6036d638dd12e42b11af7ef7a7bd0c247f26acf68557653b4516c8bff956668b9463a4f9f932517d01aef47916ff23de0b045dc50feebace1bcae8979dd5c3b39d74bad5aea4fe8fe538fb94ee2b7238f671441c92231990e2de611f88a75f9d7d2bae5f9056db3056c90a647768203ed3b34a7c5dcfbb668fcc714f3d629a7c0a6dc02ccd445cbdda333829e1adfbe1d6da896669d268cf988c558d4dd68c2f78235b00f794d50a36c5f8154f292fca1b4f6bf601bf4dee65607bec291db9a1790ff4dca0e62fc6fd0153dd2045cebb2491370f4c7a40b33b2842c628a9898215a91265449b35a131ef55ef5f089f8dfee480bbe8373d8f5db6ac0b3c29a37257d9f3f8f8723df11315083667f7c562f241e40a67068352c240e88697dcca0a829fa1bab907eb904f392bfcf338ace29a7fc9d78f6317f745dacaf88e65af42630c396f916ec698745dfc2bdd3595945b009ea8ff247d7d0fad5291b2ea6580d8b42fb341edc05ab28cd28bd2bfccbb16c2289a7d0e23b7afe84956c8a3cea55b2e00fc475592f5e5324ec033d057f13b67555fb455c6b2b7cf9b78afa170d9fe169eb71596bec71d5698d1ad739a635555cf34bc7ff36cd149b59c99a65f8cc0955cc28c7b92ea2289b46d6ce8f5412f23c0f137445279b993df580b3de9ce64eb48a5633fecbb0ccdb19019df51aae939ed54cf77f28acf9916fd57ce5dcfa6e4e0bbee9edd0991fa1ad0f6205523eb62eb28fc917b638af47dce2f1a51c3c9631ad81acef36633a8f5f7ea50947e3a70209616e914fda89d14a5fc12612ed95d7f4c9ce371bc8f7193da1c0eee09c6627e4b6cdb594fa8cac5b976c2c9fa4ee9037e3e7a41e92bfebe7a48eff633a77359e6f1354ef5617bea738f327de2bf6fcc627f86c71dc30637497eb88fcb4175af0ecf9c3f59a0d50f63a49dee3599e313d3d14e6ac7187d65c4c8a82e05fb66f913ead4a37aad05c5e461b34bf9fb30cca3f996c66746c3571ea45149797c95325fe35a55e607fff6cf2b157f6fc5135a27959fd40eb9867dce79a407fcef8277c4a3da0f5ce4c5adf66eb6e9fa57c5c908a4b9140972282f8d7a1b9d226eb6815f259c4302f918784fd9c32a3d1064d338ed3270d46e4a15d1027e2a7df494267c4969bb96b2f5a25b79b558e3ede9cecec9856e731da30a71aa90c02b2385d1a111acf7c33b87dea430946e9f232f89aac8448b2ecd5d580db7ae2a00dce6a8c82bd9b24ffa467faf6d263ea037adeabcefa5ff5fb48f2cffcdefcdb4bd5a7e79bbc9144576ad4bd9104a3ceed9ed15f7c1eb67b2b499a57d6bd95e44b7c2bc92a9ecaffbae40d10c55c9cbe2b729f307bdff1f0357958adb30e3cacea5dc7c21d0bfff958d86bccc2ab78274f8e8593d1f6f4f2aabf73787615c71afd2443b6c77e92e148f0f4b7b462f43a7b770667ea7bd291d38523e3c1497805ad4efb67b8d91dd26672747665c0def4ec4a4f86e3019d321eb3b27db679819b64faa27f79955e688f3bcaef92a3c94f97573f8fe01c4f4bf6f9763a7905edc13d4c0fa0d7d383c1d9951bcfac989eff9dbe18de4c43c9ee8bd1e5d5f0688ab7b073489d1e1dc29e0370c2e91cc0c08de6a697c21938d1cc7413b94cec699aedbb62cf9f45ae39cfca1cdf1542f1ed48b78c74cfb0dd9923ea395e60da9654cf7375713d1bae1e887a813b771ce94cd335a4bd99334bf76ccff76c5f9479aee584e99ea785a625f674c7759d206bc5d59d489c198791257ae659a16df9d2f5c2ecfe002509173f882c53f4c50f235bc6c50e33cc4ccf731c53b4624481295dc1b65d2107dd773d472eb35cb1e784816d9b52cf9c0c4163e6691966be1569962833754f776c81d9dc0f6d713ddbf01d5be0e268fecc96e5ee663db37c6f6ecb728f9c5894c5be690b2400e8c88a73771b496dc6aec01a0ac24cee811679a6909815fa5a763d2bf2f5ec0a7ae8fa99349d39e02234049ab02c51e605a16fc9baa4b91982d0d3ac152f0c03cb9570b16cd16b437323c7cbed89335d2308b2bb75e7a16e897aae1ef8b6265d3dc83073fd20b6c41d9973cf77c43d3871e0657764186e288d233730642db02337bb3fdb3533bdb66c6fe688abdb9a7c4784bcac67ba1b00dd1e1e9f5dc1a019216b20bf8ca6c78c3e8024f5647b74c83613da6cefb0cd8036136098399c39c00a7bc8375af28fd13fcfae6cdc4ed8ee09db8c904ff78607b8f9c704cf0960bbcb76a7d8dc3f267de2dcc31191ed31f056b23739c463879353dc0cd8e67042e4bc3339c26abb3b1364ece397d4c3c309eded4f8fb091fd298b8d0dc89ec439e70fdad25319c98b219dfbe288fa3f1d53735013372f06dbd4f8f00534d04b8e8faccb2bf8838c4a9b986d74b6d1940d6c87783ecc2c76421bb0c7768f77703bdd3ea4cb8d7ec6cd0beca89eecf44fe93a3b7de2ee9dfe361d1d6cd3dee0e8f2ea70388dafb42d3b999e8cd887f1013fd23fe11f929d17046172740c973f3a1e509bc968eff803ae518e7a01d9d79b601d1e1c91404607876c83a7fe3745394d8a81b25c56f42ce6148b66ebc098a1c132603d8a9186940d893902e95a8f4b6f5a389c408f93c39720c2c3ed97309dffb48797391d3369726fea105afad4636f4403c91d122e474ce2473ba477830392eace214ef9bbd8dcce4f58bc7b0817184df7c581ce6a58c26ae88f47d8edd19446eef0644ad0cacbdaa760aa9d0208bbe08aa6690bf2b12fedeb12ba571d5eebcb12c6c7a08033338c66860f44f622befa1109ed256cad64b433c4b13e9ab0c3a3093fbccbf777f9fe90ef0ff97e1ff475d407fbf76042b3537f7288efa9188d806e4d284edf56f1a393bec28277c307be9e0ce22b5ddff23cd77093fe2b68aaff8a68babffd0a282c91ebebacbeae252f47b8dd32f1c74a5e4e70cfdb72e827d9193fc76eb08b58bece2fe2fb5b9aa5197eed65d2776a683e6fc3f60dd6061cdad234c384fda236a0951dc402998e634120e808c6220aa3f104ba953cdf45b8b674c34e06a7445c193c27c7d48a5cddde3274cfe57dd4b64ccb849ddaa6f26d585b960ed69c9f36e2b99eaf39f5ad18b956cc2dde0b7dab49174ca5b2669a8ecf456aa0282dbf4117ac5c2bc696ed199ac95b81360dd704b7a2b6155b6945b77d078c4dde8a6741df8cfa569c5c2b80828727512316dfa96dc355dab01cf0e6b88ad85b9eedc1c8a86fc5535a3174cb031ca815774b6318d5b6e22bad68b6a383494cad785b96ed5b60c8d7b612e45b1163b6a6220ed40275d7b71c81a9d540cf2c5549f9f575a351e5bc924a4c50531329a2a0f7cb8c759821a6dbc027bb2744c7db7b640f1f1ced095bebc5c990de07c436ca95c80299310364e63203c4cbdb1fbee71909ff1c386e9c5e917e9e1f8075f39c997d49b2603deadc7adc80a96a0e53d7666645e62c489d59905ab505b970bd646f0006cdde601f7d8fc1733ce3644206dfc9846cf1e4ff03d11a1f3f22102771000000be6d6b4253789c5d4ecb0e823010eccddff0130083e011cac386ad1aa811bc81b109574d9a98cdfebb2d2007e73293999dcdc82a3558d47c449f5ae01a3dea66ba894c6310eee92894463f0aa8ce1b8d8e9beb680fd2d2b6f402820e0c42d2194cab72b0f1b5064770926f641b06ac671ff6642fb6653ec9660a39d812cf5d9357cec8411a14b25c8df65c4c9b66fa5b7869ed6ccfa3a313fd8354731f31f64809fb51896cc4481f82d8a74587d1d0ff741fecf6ab0ee390285389415af005a7a65f5920ed045800000ab56d6b4254facecafe007f57ba0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ced9d8d91db380c4653481a492129248da49014924652486e909b77f3ee0b48c959af1ddb78339ed5ea87a40812a20090faf9731886611886611886611886617849be7ffffedbefc78f1fff1dbb27558e7b97e1d9f9f0e1c36fbfaf5fbffeaaf7dabe67fd5739aa0cc3fb81bccfe2f6f096feb9bbb68ed56f277fae47570d7b3e7efcd8d6d54afeeeff6c7ffaf4e9d7df6fdfbefdfcf2e5cb7ffaa2f617b5aff2292a2fcee518e7914efd5caedacfb1da6ff993769d5b695a5fd5b161cf4ace9dfe2f3af97ffefcf9d736f54fff2db9940c903932623fe754fec81499d7fe6c17f46bce253fb713da02e51bf6ece45f32f2f8afe8e4cf31e4427fb50c9173b515f759e4c735e03ebe3a863e00ca633d31ecd9c9ffacfe4ff977ef0ce879e45e7fab2d20d7dab62cfdcc58c9df7a00e8fb959e75c3d0734df923d792555deb3e485ff798c079f85992c776ba21db89f58ce55f6dc4690cff5275e2fe73b41f5dcd589c6d1fa75f67fb295959de290ffa6de65dd7792c97b2ac6dda207a8631c9ea9a61188661188661784518c7df9347b0d7a7fff36f2fef59f2ddfa1e5cea6bba36f5ae7884cbe8ed6a07cfe667489df096f6bebb96f7f39dfccfd8f18f7c86799ef775bec42ebd95fcff86fef3165cfef4b361cfc3a68e4d26fd77e9dbc317e46be9239dcf90fa243fce5df9034de76fa48c5c8ffdc8be2cdb2feddfdaa597f2b71fe251db40cadf7e361f733f297d89ce2cb9201b6cbeece79cf40bd867582023ec7605e751e7b40f93f667b71dda0cf7429e9916f2eeecd9995ecadf75f4a8e381aeff037568bde0be6d3f8e7d7b5d8cd02e7ec87dd0765ff7ff4a3beb78e76fcc674af6ebf4595d92deb3eb7f40b6e9032eecab2999e16f717f4396fcbf933f7ddf3e40c7fb589fc0cedf98f2272dfc00e4d3c9ff28bd57913fcff6fa9bb2b1bc8bf4c5db17e7fadee906f489db09bac5ba1876fec694bfcfebe44f9cca99f452fe9cd7f9c3fe76ec5b4b3f5b417da53f2dfd77f6ede5b5d46dd1d591fd7d3c47c863e54b8495bf317d88bca775e5f178e64c7adea61e1e55fec3300cc3300cc350635bec9bfc3c3ebf675c3df6801963bf1fb67df1b34dbe8b0bbd157fc3fcc3676767d3eafce0d609e80ec784e7dcc0f42d425eebf36b5f673fccf2a0af463f9ca3b3ab20ff95ddd776307c7cd88ed01bd87f3dc7a748bf50c6eedb67e773ecb7e32fc7681fd891f1490dc774fddc7e17d77b91f2e7fa6e5ea77d81b6ef5ae6f80fba79449eeb97f3cfecb7f335e888e9ffe758c97fa5ff5776f0f4a5a74f1d1b29ed22fbb16330ce1ccb7659e093d8c50b0cffe75af2ef7c7ee0b95f252fcf052ccef47f705c80cbc9b979ceb0e75af22fd0c9e93b2f788617d6e1ce73f5fc5fe906b7393fff1dab50ccdcaf35ddbb5ced5bbde3797cded90218c3e7f5c48d389dd4cfddf89f6700e4fbbfcbcab14c7be43f0cc3300cc3300ce7185fe06b33bec0e7863eb492e3f8029f1bdbdcc617f87ae41aabc9f8025f839dfcc717f8fc5c53fee30b7c3cae29ff627c818fc5cae737bec061188661188661786e2eb59574eb861c617f123f6cc4f75e43e395df11766badacd8bd23aee0fddc7e65fb90ee29ffb4333c335ed707db1bb6b9c26bf0ac646cf9637fc126b76a4b2b9b9ce56f7f2265f2fa3c2ee3ca9e609bc1ea5e28337accf2cff6e8750a9fc1a66cdb2b36bbfadf76b4ee9b3c9986d710c3af870dbe6b375dff2f6cd3631b5b60fd4fdaf6f9d06e7dbdd7e7f29a84abef0b9147cadfeb8eb1069ad7c47a749011b85e76dfe432297f3f3b576dc672f5359dfc1d6fe463f61fb39ffe497aec3ffabe988f914feef77a78f78c7bb926d8c721fb8565b97a2efea9fcbbfd9dfcd317d4c522d1afd1eda47ff40db9dd31fc14d6f3e4c1f14767d7ffed7b2bd28f03b7927fd71e6cfb773c01fd3d7d47ddbdecda46c6a8905efa291ff59d81b6ec7a491d70e447bf95fc0bafe34bec00acd69cce7bcd7bd9c9df65c8ef0bfb9a47957f913132e9c7dbadafcf71afa79969756d661797933ebf2ebfd5fedd7dacaecdebf29cae7e9e61ec3f0cc3300cc3300cb09a5b75b4bf9b2bf037f8537671acc3efece6d6adf679ce85e70a1dbd3fde82917f8f7d18b6a1e59c1a58f9e777edc5b691da76cc3fdfe5b0dfa9f0f744f0ff818ff95adb93f02d712f963fdfb3f13dbfea3c11cbcc7eb68c9b8723fddfd9e6adffd9e6bb3eb6d3d9466bff8cdb2271fef87ab00172adbf51e86f0939c6c472a62cf888eeada76ecd595b3b1ceda76fe63a01297f400fb82d60d7a72dd8378bfc287bce1bb1dfaf9b53d2eda7ddfabb40afc2b5e5dff59f4be4ef391fe8eefcd6a0bfc9b8937f3e0f3a9f6ec133e915e70a5f5bff5f2a7feb6d74b0636decc3433ee471a4ff53feb48d4c93f810c7083cb23fe7123c6ea28fd1af6e21ffc23e3df7e7dd3cf2bc36c77f3bf9e3c7abf473fcf7e8fedc6118866118866118867be078cb8c995cc57fde8addfbea701d78e7b63f30d78bbc1723ff359edfe0f87ae6c7d18f998363bf5da6b3b2a1780e5efaea6c87f2bca0950fd0c72813d766f9b1ff3a7e1f9fa6cbd3d9a35e016ca0690fc56ee7351a39afb3a9179e57c7cf7eb75c3b14db3f76fe6e8e97edbbf6e7923665cc72a5dfd1fdbff35562537e76f9a7fea35eb9f7550c4f374f28e97c3b2bf953cfe947f4fc347c3fd866ed1fb4ffd0f7b62a3fc7728e97f7577ecf32cf6fc54efe96d99fca7fa5ff53fe39ffd77e648e61abe798e7f2eed687dcc9df6d14488b36f6ec6dc058ff5bffdd4afe85fd907ece732ee312da03799cd1ff906d8334d9b64fdcedef157c421effad64fc9ef2f7f82fe7f8d90798fec16235fedbc9bfc879feddf8ef55e43f0cc3300cc3300cc3300cc3300cc3300cc3300cc330bc2ec45016977e0f041ffe2ad6ce3140475ce29fbfe4dc4bcaf0de10df724b58ff98b573327fe2b128dfa5f2e7ba8e4aebecb716ba38948e8c273ee2925840e296bc0ece51acfb51fd1a62a3e0da71f48e9df27778890bec62f72993bfebeeb85fca4b7c958f9176ce1720dedc31e78ed54dbc36106b08659f652d32c79beffa35f99f3997f31d3f4b5e8e45baa47e8939f2cfb151efb1ae59aed746b93896eb7f110f4e3fe57e3b3a3d4a3c97efdbdffea12dec7400f1bac492794e41de1bc7e967bbfe43fe8e095ce1fb4efde258ca4bea97732927e95e4bff675c7ee2e7327d9b36ed7dd4b3bfb7e1ef29b91edc6e5d4fbbfb72de1d5e1bccf7b36a877e8622b7ee7b1f70e6b9e2f93239778036941cd52fd02e1c9f7a2d76ed3afb2bfdc7ffa3f3b24d7bde57971efd8ffb4a59b9af5af7adcaeab5a26025b78c0dedd2a46c398f85bc12e795ed9eb6991cd5afcf234fcf557a2be8c155ff3f3beefd13f9738ee7dc649a7e1e76f1e04eb79b87449de7372a2e59e7ab1b2b763ad379653bf4f838cb7da67ee9ffdd7c85b7b22bc391de854efe397fa3e0ddc07d957b42c69e237406afe998d778ee90e3d5ad738f58c9bf6baf8c53ac1f56ed9bfc2fd5e7bb754a3d6fea0c47fdbff03c8bd5bb4c27ffa3b2f34cacf23a0fe4445db236dc6aac669d99f2674c98eb047a9d2faf2bd8bd5b7b8e99df1dba72f0dd43f44dae59d771a67ee1e85bbb1c7b8f798bbca3aef2cd791a096305c87a41bfa7def4bbf491de3bd3ee77ef84abb180c73997ac137ac9737a57bf94fb48ae9cf3ecf35687617833ff00a082fa20e0bf25a300000ed76d6b4254facecafe007f92810000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ced9d8d911c290c851d881371200ec489381007e2441cc85ee9ea3ed7bb674940cfcffe58af6a6a67bb69101208d0839e9797c16030180c0683c16030180c0683c1e03ffcfaf5ebe5e7cf9f7f7ce23af7aae7e273a5ac0065f8f7c1ff51e9f98afe337cfffefde5d3a74f7f7ce23af7327cf9f2e5dfcf2ebe7dfbf63befb0376504f4fb4746d4fbb49e959e4ff5bf0236d1feaef65ff551fa7695a693d7eddff99d5d54f2ace4045d1abd5ea5cbae67edbc7b3e706ae7ab7aebec1fe56bdf75b9b46f7b1e9a0f7905aafeaf7965f58efb9f3f7ffef77be82dd2fdf8f1e3f7bd7886baa82f0b443abd1ee93344fe9e26f288bcb91765ab5e4817d7f579eaa069232f4f471df47afc8dcfaaffc7dfaf5fbffe7eee0a3afb734d65d1f2234d94cf33599f233df732fbbb0c519edb089bc7dfd099a689ef8c5b5c733d451a9df338b47d453ea44717c8a9e954eef8a00bdab2ca1dd7541faa672d9ff17dd7fefacc15acfc7f5666d6ffe97f9dbc81ccfeea6fa8535677ec1c7a567dd12ee2832fd03ea87e813c567276ba403eca213ff715559dfdf990cfcbdf19ffef3117b8c5fee83cfa23f5eee4a5fe95fd7d1de2c0b6d83dfe465b207ffef77e86acf451da8b42f3a14e992ef0b72e2bed91b95e657f64d4e7bdfc4c6f2b5b5cc52df6a71d6bbbefe40d64f6577f87efcdfa287dddfd8fea36ee71ddfd24be23b33f7ad03696e942d3a9cd6917c8eff6c73ff24cfce519cd37ae21e333ec4f5f517dd09f0175f5efea6f19df1c9a1e99e95bfa3d9ea50f746ba5c88bfbf84dc0ff91c6ef21277d2f03f5c68764bad07232f9c95fc743fa87ffefb2a8fc3a97a9f4e9ba1d0c0683c16030180c3a38f797dd7fcdf26fc13db8842b382df3347dc5db9ec263e5c4a558cf6471817b6255feead9159fd671988fc2a9ceaee8b8e26def21abc6b6ab672aceaaf21f555c7ab7fc5bf9b44741e5d63d135ea72b7275f1fc7bb5eb4c56e5d5fcfe09e755a53f29ff563eadd293f386ce29fa1e05e77890857c9c0359e9acd281c615bb671e697ff4e39c5cc57969dc5e63e655fad3f26fe1d32a3d11a30bc0b3c2f321f3cafec4ed541695b3d359a503b53f7266f267feffca1e9a13fb579c9773d6cabd561cd96ef9d4f52a9f56d91fee58f923e5143dffcc46c8dcfd7faa03cfab927f972b5ba11aabe046f47ec5790548ab5c4c977eb7fc5bf9b4ce4fd287a38c680ff4359eddb5bf8e199ef65407a7f6bf15ea6fe01a9477af7c536617aefbdcc1d39f947f2b9fd6e929b3b7ce3b1917b4eccc4601e516b3b43b3a780dfbebd8c9c7f7e2292f884f53ce0b79b2eb55fa93f26fe1d332dece6503ca295665332777ae94e791674767998e3def4afeae5e83c16030180c0683c10ac4be88f99c8275206b98552caa4b1373db1dfee71ed89135806e1e59e6ae2cf706e76258777b0c7f05e21facbf77d626ddfaf599fb1933ae51d79481f84eacf0516576d71f098f5d07a8afc6b7baf6af676348affacb9ee719ee69bd9df78a7bee97f4cc41a6339ee9f4497ff33886c768b3f277f2ca64ccd275d777cbc8f4b883551c49f7e457e7719483f038adeed5d7e749a33e079d2b97a3f140e5ec940f7099f41c51c59fe1f3c84763fe5a97aafc9df25cc6aacceafa8ebcc41b55c613acecdf9db7045dccd2f92ff83aedffe8cacf3364e73bc9ab3a674239c8529d8dc8f4ef7c47577e9597eed77719ab32abeb3bf2667a3c41c78f91df8a67ecec5fe97fe76c11f9faa7cb1779d0fd8ecfd2fe9ec5e2b3f23daf9db3845599d5f51d79333d9e201bff955bf4365fedfbb9daff335d55fd5fcbeeecef1cf4aaff6bde5dffafe67f5dffd7b2ab32abeb3bf2de6aff808e2d3e86e19f747ce9f82b976935feabdc6eff809ee1abce9f67ba5acd597c3cf5314e79c5ac7c4537fe6bfaaacceafa8ebcf7b03ff5aed6ff7a2f6b9bdcd73ee43c9acfff3d8dce917d4e9fcde5bb1881ce85f99e0119fc3ee575e55779ad64accaacaeafd2647a1c0c0683c16030180c2ae8dc5c3f5738c047a392cbd71f1f01cfe20259bbfb1eccd53a82fdd2cf441567abf663be673c8b0bccf6322bb2b880eed9d5fdff27eb57b515b136bd9e716ed89f7b9e0772e875e72277b83cd2546bf4ce2e991ef45ca072a495be6ee51b4fe0fbefd53e55ec8f98137e638723543fa3f12baeeb9e79f6ef7bbc51f9c22c8f2c5e9d71461d2f58c5fb7638ba2ed6a96713e1d7337dedc8b8e2024f50f97fec8f0c7e6e25e3685507de47340ea7e7f8285ffb85736e7c577d546726785e63ebf4932c46edfd85b6c7f915b0c3d1adb80ef2abf4b52be38a0b3cc1caff232b7d2fe355773842ce52685fcfcaefe4d17cbb3333210f6d96725c3755398c6deadf287bc5d19d709d99be7665dce50277d0e95bfbacfa78afeb0e47a8efb5ccfabf8edf5dffdfb13f673bf52ce76edfd2bd03da6f7738ba5daeb3d2d795feafcfdf627ff7ffc8eee33ffac74f56e9bc3df9b85ad95ff3cec6ff1dfba323bf7665fc3fe1e876b9ce4e5f57c6ff9db38e15aaf5bf72de594c80ebda6f3b8e10f9b09fce31b367b23970565e9547b57e3e995b5fe1e876b84e4fb753f71d199f1533180c0683c1603018bc7fc0e7e8daef51b892b7be5be31eefbed7d8c00976657f4f5c146b5e3800d6948fa803763c01eb5a627abaafb2db075c018ee1141a4fec70a58eaf056216be6e247e16f07dacbe0ec76fe87a953318f1d1980d6d4be30be8abe37655c6cefe5d5ebb69b44eba0fdee5b8571d41f6bb23b4f7788eeb94abe90291e66adfca74c4f5ec1c0b710b8db33a07a3bfcf453dd00d7a25d685df71f9294363f1d55901b545c5d1eda47199b4aeca795247fd2da12b750c280f4b5c0ffb68ac50d31127a5dc2a5ed8a1b2bf5eafecaff171da3d6d123d797c4bf3f2f876c5ab65fc89db5cfd9872c88a9d3401b59fc71f3d8e9bf17b57eab8fadd31d2fbfb049d3b3dddb7e5f2781cdaeb1370fb3b6f403efa9b94c898e906545cd48efd2b1e43b193063d2aef99c9ab7c98bf47f04a1d4fb843cae503575df99615788e312eb399efb5a00f395f459fd0b1a9e20dbdfd56fd71c7fe01f57fcae32976d228e7a8e3a0ca917dcfecbf5b47e504f1f91d77a89c9feec7ba021ddbf4e37b2de2836ff4bd1cdc531d64d7955fcb9eefced783cafef832e5d41d3b6954269f772adfce77fa87b799933a06d406baa7c7c767d7adb69353fe4fa1e30773ccecde6a8fddeabaef255a8d5b7e5fcbaf64e9d6aeb7a4f1b22b9d9cd6f1f4b9cc775dd9ff37180c0683c16030f8bba0eb185dcfdc6b1ed9ed498c7590be2b65a74c5dab12efced682ef016f415e8fe7b0ee3b3d4752a1b33f9c00e976ecafb116d63b3cf716f47982b7206f168f244eee311be7b4883d3adfa550ee56e315019e759fa33cb4c7203c26abfcbfea334bab791343a9b8182f17f93496a31c72c729693db5fe2a2f7cce8e5e905dcb51b94ed09d31718e47b9088da5773ec3b97be595e10b3c46aaf5f3f38dcec156fe9f72b232bd6ed9de6c6fffd88dd8a7c77cb16db66f9bb60227e27162ecadeda2d38bcbee729d60657fecea6566b1ff158fe8360a3007a86c7a929f7341de5fb2bcab18b0ee0fd27837b1d6ecb7a32aee557d8deead52ae25f3192bbdb8bf43ae13acfcbfdb5fe709b7da9f7e495cfd51f6d77eb16b7ffc0679a9cf235fb7ff6a6f077e447955e50fc0ae5eaafa9e6035ffa39e708cba07e516fb6bb9ee876fb53f3aa41cd5cbaefdc943c70eb5b9ee77d9c9077faefc9fcbeb3e3fd34b76cfe53a41b6fef37d453ab7c8ce5fab6e1c2b7bd1fe75ffc33decaff3bfeadc72a0b39bf771cd93beccb35d3e3effcb64d17d182bbdf8fccfe51a7c4cbc85f5e2e0f530f6ffbb31fcfe6030180cee856cfdb78a6580b7f4aef98e673a85aed7baf1b68a9d9e6057d7f72c33cb4fe33fabbd91015d97be055c39fb5081f8d7ee7ecd5bb0a36b4fff08fb57f969ec4763157ec629e3081dc42d3c9e0e77a6bc2efd8f3371cc7d950fe39e7f2776aa75f0772456b192ac6e1a93d176e1f228b272b2bac2ffb8ae358eef32a9bd88533b57b88bcaff6b8c5fcf4bd1cf94f3ad38422f07ddc19d68f9e845cfd5a01f97278b39ea773f7f4b3bd2783af2781bf0ba053c96ec7c40b60fdb63b3c4cfbdae1587a7f956fae079e2c61507bb637ff7ff40db968e531e77cd3842071c95f271aec3eeff5dfba3173d8fe93207f46c8f42d36536567d65f6f7b9889eadabf252fd28b7d8e9c3cbe1de0956fedf65caf65a541ca1eb8434ddfb1fef617f64a2ad653207de92fd2b6ef159f6cfe67ff842f597ba8f029f547184ae53fc7fd7ff03bbfe0edf94e93c9bc3eff87fad5b26cf3dfc7f66ff8e5b7c86fff70ff5f773b07aee50e7291947e8e5e8d9c1aa7d7b5ad785ce33abb3996e37c56afe1770fb57f3bfce7766e574f6efb845bf77cff9df5bc7bdd73b83f785b1ff606c3f180c0683c16030180c0683c16030180c0683c160f0f7e1117bd1d9fbf491c17e13ffacf6bdb3c7d2df6911606f4cf679843e756fa9c3df695c7189ec4bf37c7dff08ef00d20ffb379ec953767539cd47f7edf1a9ecafbf63c2be23f6d9e93eb72ccf6a6ff0adf2777bf5d586fa1e1087d61bddb2874cfb04f93dab6d57e8eaf2c832755fa4f68dacff38d8af96e5e99f6a6f9e3fbbda1bb26b7fdecb44faf8dfdfb1a1f67f6dbc86fdfd7707d43ef4c30eec23aea07da9dbcb16d7f5b7453aacec4fdba14f3b3cffacff739dfc79979beea1d47d9b01da9bbe6b897d7efa8ceeb1d57cb54d9286ef273819435417feaea1ac6f2ba85b579efa82ccf68cbdf8865599e4a9efef5199f5fd7227f6f7bdee5ce77fad27e792749c5459b8cf77f48acc7a2e44f3a53da8dc577cd3c97e713d5fa1be0079aabe4dbbdcf1e7d9d901cdc7f796afe4c78f6bfe0174a7ef62533d32e6fb7ca5d2b1cf79b44eda5f7d4fafee65d7f994e691bddb8ff6e0fdf0744ce09c53369f716043ffedcc6a1c66fdb4339623cbe9dcb0b33f7ad5310bffe9ed48edaffe5df79a93e789fdf55c22ed57cbccde5de5f3accafefc4f3bbe322740beece3407e74471bcefc3abaabfcaae77be5dd7581cafeea37016d318b15e899515ddf50579d1f56e71b32fb53ae8ee57a5dcfa57a9fd2731f9dfd91c9dbd733d0c55d76e71691eeaadcdd7877d29ef4dd3ada973eda798ac160f0a1f10f88246aa2eb77bd160000069d6d6b4254facecafe007f98820000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ced9d4b6f955514863fc5225ef0cac5bb5511ad288d220a55a016a5508ec52a171529a59452400554d0da0b528d7fa20d69d391238d71d2a669e2c899496776e0c09374d8a4894913d23675bd39eb0b3b2bfbe0a869b2f6fb264fd2a493933cfbbbedcb5a338b8b8b2dc22d5996c5c8e47fc42f795aaf5fbf5e21ba570a15ca0ae1568e01d784a99f9a9a5a2dbaef11ee1256e938e018f04b98e2fcfcfcc3a21aac13eed77150c167815b6cce8d8f8f5789ea8dc213c21ae14ee1368e0197d8fc562c16df14cddb84978427f53e703b9f032eb1f97b6666a6209a1b841d3a06f03cb89bf70097c47254147f2c340a35c2d37a0f5849ffee88a55d149f16300ef6089b84f5fa1ec067802f62f94c145f10300ede13b6088fe9336005fdbb22968ba2f8b28071f0a1b05da814eed57780e5fecd6469fd7f258a3b058c8363fa1eb841df012ae8df15b15c12c55d02c6418bb04be7031ed077c0e5fecd6469fde3dedf2d601cb40a6f09cf090fd2bf3b62c9fd5fa67ff7c402ef3deaffa45047ff6e8985fed32116fa4f8758e83f1d62a1ff748885fed32116fa4f8758e83f1d62a1ff74b0f9ab582c72fe2f1d6c42ff9cfff78f0dfc5f52fff9fa5f2dd7ffdc6293fbcfd77f8f67a5f5df67d53fd7ff7d6103fff0feadf0a5d09c71ff87676c72ffe1fe1f9c077886fe5d6203ffb8eebf51ff47d53ff680df47ffeeb009fd5f50ff6fd0bf5b6ce83f2d6ce83f2d6ce83f2d6ce83f2d6ce83f2d6ce83f2d6c7e191919c9fd9fcf4ae7c06b02ff3cffe70b1bebff23fa778d0dfc7f218abf163ecf6e9cff7d2ae3f95f8fd8e4fef3f3df47b2522da04afa77890dfc63de1f6bc09f0a8784d7b3521d28d40564fd075fd8fc3c3a3a0aff58033c271c145ecb4ab5e0e8df1f36f08fef3ebc039e153e10b60a8f0babe9df1d36a1ff33c2fbc2abf4ef161bfa4f0b1bfa4f0b1bfa4f0b1bfa4f0b1bfa4f0b9b1f3b3b3bcf47fcb3fea74ffe59585888f9c71c7087d094b1feab677e2fe3ffa2fa47fddf57e8df2d31ff61fde746f5ffa8fa67fd6f5f58ff3f74757561de1ff7803601bd205e161ec94abd80e8df17d6fff7dddddd98f7c73d0067bff70bd559a90708eaffb3ff832facffbe9e9e9e0e5dfb3b9195fac0d0bf5facffabbdbdbd1dbaf6d722ec1536d3bf5bacffefae5cb9d2aedffef4ef1ffa4f1bfa4f1bfa4f1bebff6ca150c8fd1f57ffe801f9907007fdbbc3fa3fa4f37eb9ff7afa774d39fff8066c0efcafa77f97c4fc9f52ffa8fdf44e76a3ffe72afa7747cc3fe6fdd1fff513e16df5bf8efe5d62fd1f54ff7806e0ecf76ea14a589b957ac02ff7ef254befffa43e0370f617b57f9f17d664acfdea9172fedb02ffacfdec97fff35f4bffaea1ffb4a1ffb4a1ffb409fdff3b373787f3deadea1fb55f76d1bf6b42ff7f4e4f4fd37f5a94f38f67c011f5bf91fedd12f37f42c7c0616167c6de3f9eb1fe71deaf45c700d602d0fb05bd7fd8fbc32731ffc7750ce05e80de1fecfde39798ff661d03f81bb57fd9fbc72fd67f93fa6f56ff61ed57faf7c7cdfc37d1bf7be83f6de83f6de83f6de83f6d42ff3f4d4e4ea2decb31057f6fcfd8fbc133a1ff81898909eb7f1bfdbba69c7fecfd3ea0fed9fbc32f31ff708fbddfa8fd84de0f95f4ef16ebff80ba47df2fd47e42ef07f6fef08bf5dfa8eeb1f70bb57f50fb93bd3ffc12f30ff7d8fbb32ff0cfdabf3e29e71f7b7f70f61fb55f59fbd92ff49f36f49f36f49f36f49f36a1ffbeb1b1b177f5dd3ff7cfdaefbe09fd9fe9efef0ffdd7d3bf7bacff82ba3f4cff4950ce3ff6fea3f6136affb3f7835f7e9d9f9fcffd770c0c0cecd76b1f7bff51fba95afdb3f7834ffae6e6e642ff0d7aed63ef775d56aafd8ade1facfdea13eb7f9f5efbd8fb85da3fa8fdc8dabf7e09fd9fbe76edda5ebdf6b10f00677f5fa47fd784fedb070707f7e8b5dfa8fe37d1bf6bae06efffa7868686f0ce8f3d209807d841ffeeb1fe77ebb55fa0ff2408fdb70d0f0fd37f5a58ff757aefa7ff3408fd57eb371fdc631e08b51f5ec8d8fbc133e5fc37a8ff2afa77cdcdfcd7a87ff67ef04bcc3feefd9807c4d93fd47e67ef07bf58ffb57aed631e1067bf50fb71adfa5feedf4a96d6ff669df3c3b58fb57f9cfd41ed3ff47e60ef0f9fc4fce3dac73cf0d6ac54fb8fb53ffd52ce3fe68171f66703fdbb86fed386fed386fed386fed326f7ffc7ecec2cf67aeda4ffa488f9c7b73ff6fe6ea17ff758ff3bd43ff601e0ec076a7fb3f7835ffe039903f105cda368b1000004796d6b4254facecafe007fa2360000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ced9a896deb3010055d481a492129248da49014924652883f36f8633c6c48d9b164f89a070c7490e2b1cb4322b5df2ba594524a29a594524afdd7f7f7f7feebeb6b4a85df83b21e974cfb12e95f53559fdd6e37e5f3f373fffafafa737ecb75a71e55d62d55be7f797939d863ebf4afadf4fffbfbfb2f4a75ac7a3fa3ff69fbcfe0ff993e3e3e7eda0073016da3ae2b8c76c17585d591f815cebd599a75cc38b3b4fab3754e19d23fa457503eca314a73a48a576956ffe7fc91fd5f754bf0451fff89cfb8882f729cccf9a3c2885fe29a674b95575dd7b19e99cd45591ef2636ccaf32c5ba69f549c591b48bb54becfe0ff0eb69bf9ffedededf08e887d78a6f7c74c237d419b20bcd2e29cf647fc9e561dcb37bc97915ef77d3e9369d29ebab22de71cf8e8fe9f69e6ffec3b7d9e4c4af8b0ec58ed26c754ca50f733fd54de23aff4dfa81d67ffaef04a1f1fd6f96c0e201e65e4fd27ef3d8ad6f83f85cd986fe9e7f96e80edb161f6ebec9bf4bd7c37188d15f93e9afd3fc790f4559625cb3babef128fa2adfc8f2ff16df6b59e4e7f2fe8e3498eb5d9f766f351d683fc7a7bc8f13cd364cce936e964996ef93be8afdacaffa5b251be03966dfb7b7bf735ed24fdc0fb0469d1a678e614ff673cc624f222cd5ebe253deafcaf94524a29a59e5397dae7cd74ef652ff9d694eb2097b0617d1bcdbe6bfaf7d45fb5f679b53fac39d49ac96ccd7a8dd2ff7d6d23bff347dffc4b6b21ac9bf4f36b28cb39ea47fce331bbbf94dee87a96d72cad51bf26ee6ccd6956e6bf8afdb2dcfb235fd63cf29cf6c233b9ce9e6d24f75e39670d977b3926b077b326bcdb3acb99eb89758ff566d69afaba5285f73ad6bd4c2b6d57c7f41990575ea31ea7c47a25f74997bdeea5329fa3ee976c6fa3fecf5a1bb6663fbe6b34f657bcdcc72de823acf3ad093f562fec97635effb721f721107b1975ccb5cab455a6d9f31ad9b7ff13419cd1fd91ff47653e477dfe3fc5ff197ff6fe9076e29cbd198ef833ffc958137e6abdfa9c977b02f87a54a7f441b74f5ecff2cab0d13e13f7b33f739d798fca7caeb6f07fdf47299f50a63c2f31d6317e3296d19ed7869f522fc21837f88f001bd3be10736df77f1f73728ff298fff97f22f328f5fbddffb3329fab63fecfb91d3bf47e92752df5f1297d83bf88cb3cb655f831bb6718b6e4fd3ae761c670e262e7b4357b9df833f7288f95837f6bf1216d276dca9ed9c8ffa3329ff32ec87e5bd6297d47dfceba628fd15845dc4cafe7d7fdd9fbc19a70c296ea9561d483b9a4fb37e7f2d13718cff7e74679f530da40df8b4c1b133ff39e95b9f7b54b6869bd403dbef4bf524a29a594524a29a594524a5d4d3b11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111913be41f87978e6ad93b8e9c000001536d6b4254facecafe007fa5850000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789cedd6e169836014865107711107711017711007711107b1bc810bb7a621ff8a09e781439b4ffbeb4d6cce539224499224499224499224499224499224491fd1711ce7beef4fe739cbb577f7bfba4ff76f5dd7731cc7739aa6c7cfec18fd2cf7d4bd7516cbb23c5ec7300c7fbe8774efb263957db36176adcdfb3db57fd537bffe8dee5fb6eb7b5639eb9fe57a9d7dfbc6d9bfba5ed3fdcb73beef5fffd7e7797eda3fd7ecff7d65db7aeed7efdbb6fdfaccf7e7bffdbfaffa1ed7f7abf740aed577fb9c45d59f1dd76b922449922449922449faf7060000000000000000000000000000000000000000000000000000000000000000000000000000000000f8403f0af9acb852da2a66000004666d6b4254facecafe007fa67f0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ceddc5b4b9451148771ad1cad7174c6c3a46596a559991dec7ca273f71dbe5074dd970a41fc24de8a171e50a9b5e0bf61338c5741c15a4ff0a3a02e82e775bf7baff79df97e7c7cfcdb7e6deceeeeae0e0c0cbc349fcc5bb366ae9a09336406ecdf2196ef55ffdbeaffd1bc31f7cd82e9d03facbaff8a257e6ede9bd7e6aeb942ffd04eeaff4afd2f9b36fdc3ead7ff9dfadfa17f78f4cf8dfeb9d13f37fae756faffdcdadaba65899f69f6e373009f07cdabff19fa8744ffdceafe372df153cdfefc3e705bfdc7e91f566fff279afdf93ae0fb8139fa87d6dbffb1f67ebe0edc52ff31739afe21d5fd6f58e247baf7fb75e0d7c345fa8756f75fb6c40fd5dfaf03bf1e2e9816fdc3ead7ff857e5fa67f78f4cf8dfeb9d13f37fae756fa7f5d5f5f2ffd9f57fd67cd28fdc33aa9ff03739dfee1d5fdafabbbcf7ed7e89f426fff35cd7efdddef2533a3fea7e81f526ffffb7a06e4effe2e9af3a649ffb0eafe4beaefb37f7ff7c73ffbd335e7cc20fd43aafbfbcffb3dcdfe57d57f9afea1f5ebef67007ff76381fee17dd9dfdff7fe5f3636361675dff7fefeeec715fa87f76e6f6faff4bf46ff744affcf9b9b9b57b5ef5ba37f1a6f0f0f0febfeabeaefef7ef9bbff53f40fadf49fd6cffb8ace80feeed7bcfa9fa57f5875ff7975bfab673f97cc24fd432bfd27d57b59f7009f05fabbbffedd2f23f40fabee3fa7ee2b9afdfabbbf1dfa8756fa4fa8f7a2f67ebe17f4677f6df5ffdfff4ffc9bfed7b407f0d99f3ffbf3cffe0cd33facbaff05fddcdfd059c09ffdf9673f1af40fab5fff659dfde91f1ffd73a37f6ef4cf8dfeb979ff5f4747471d9df74b7f9f0577e91f5e6fff05cd00e99f436f7f3ff72fe959803f136ad13fb4baff8cfa2fea5940e9cf77ffc555fab7b5df9bd71ec067c1fe4c6894fea1d5fdbb5af7177416a07f7ca5ffb8d6fb39dd037c2fe067c226fd43ebd7ff32fdd3a07f6ef4cf8dfeb9d13f37efffe3e0e0604cfd2f6a06e0b3209f09f9673ff8eee7b84eea7f9efe29d4fda734f799a37f1aa57f4bf3be59ad013e0b6cd33fbcd27f54fbbd19ad017e2ff03da17ff68beffe8aabeedfd1ba3fabb5c0ef0923f40fadf46f6abdefea1af0fe2dfa87d7db7f5afd27e89f02fd73a37f6ef4cf8dfeb979ff6f3b3b3be7aafe5d9d05fd4c384cffd0bcff87edededd27f4ad700fd73a8fb8febdc3fad6bc1fb37e81f5add7f4cfd27d5bfa9fe7cf77b5ca5ff59edf73adafb8de9d90ffd632bfd47b4deb7750db4f4ecc7dffde1bbbfe2ead7bfdcfb47e81f5eddbfa93de0b8fe4cfff8e89f1bfd73a37f6ef4cfcddb9ed19cb7cc80cad97f587f47ffb84aff86cefbadeaecdfa07f78839aef37b4de37abb5bfccfee91f57e93f545d03a5fd10fd5328d740b90f94759ff6390caaf3e91e83f407000000000000000000000000000000000000000000000000000000000000feca1f8edaa288f0778bbd00002a176d6b4254facecafe007fd4f00000000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000789ced7d2bb8ec28d6f692482c1289c422914824161989c422239158241219898d8c8c8c8d2c997f51fb9cee9e9ee9f9d4ffd4885a73e93e557bd709b02eefbb2e54e6e77d369e00dab3912dc6d7466087a3c302705dc5056b00df857ec3f3621de0810a1001b60a1bd09e24c177dd93d9b184c1f1cd7c73fcf1436f00959bdd412bd57133b6be6c4f1514e0789d40e128e930f061b9af2b3ff70912a0dc542ca45f8d03af3781b8735c9ddbdecf985e426c3129fc4311f395d20f0f2bd46bdb28ee46794e2238f596645043cc5f3916c8d23e4f0630266cab34b88f496d2bfeeeeb6e9077eedb47178f72c5b0f88d99e0013c51aa8371090fd114092baf97b28548937d0cfaa1aec165a1b71d7f718fb9ae7e7e825b292ea335638389362cd6fab8c5c0c1826d0e9e1d04754fa0f8efa839705cf8d3fd3c2f1b0f2f3fbcfcb7b84ec43a129e934dc29b057029b6152015f6c49c2d1cd51e3270c0270f87754b716002beb4cd5fb7e0dae6c01fd52fb899fb36fc341d3c7e00850bb41e373753ea1b81c7e14eddd54251d7e1d6edf3eba7a894754de0f77b8f8efb35c8252e42a64ec98a0b186b3a18096b9b4ec0bc6d8139d80ffce7022aa7758155c57c32a886d9253bd1ef11819bab1db85d46e161d79229cf0baa524525485763304d6703c63ebbf8292f746968936698f5d509d1b858863ecaca1ca812cb45cbc5f2e3291596d43c0f2ce936f67334c5e1c07fd6359b7ae062f03fa4a24a1ff0e0f65c1b3c194f1c4d254f232837e436e03aa7163d099daaeafdd38b075941a3f7c283480db6ab08904ca073369bce034d8358eac8f94263589d26c7984abd13740465bb2ecaf612fca8cf09332e186a652894cf3fd43c3f9d2d31e924709d4f013d00fd7f78141a128c0bffdafd091f5d3c4a74c2a1b756679b9a607c492c3ce4b8957de046f30e516a5dd06f255b5061d1fd018836fd18581259bfcd3ce117aa3f10a8596f680080469e17fc303637e181ad1f364a46f197475bf085057776299acf373f2c1ce440778e0a8d066e4de0c7a6dd136dcc05ca6b3e3ff5e8c94cda6aef0fb3153d80dd0fda2577835a88697a465478068110f484241181af6b50f6389b45b7906df2f8096a414f99f1d4d7fade80ab9e1c3e7efe9286e5dce0d88691ba06e94239fab5e209860bd6a4023855adab4b1c0e43c376b46a62eb0f4c0be8e5e743bc02ef8079af05a10be71e8d3ba46d176a0b502ed4052097f8e3ef446bf115c2ea77fea155ff45c2c28033f4dca8c1949195923878eb55c0854acafcb973128fa312899e5c84b3080ee8c99f8e2b3b067d070473ad184610e31d51281e4b49adeef7a658dd9b84c6a80ab9310bcbfafe2b4b8ffaa8e864588c1f5d3b4ac4a38eef63088878249da744d1d7b7de0f109ea213586069d71e20eca8ebd110406ccc8ffb726eb06d2481a8c65ed739ce84ef7a4247cf9ea0513d15c66e6610e46b8444eaf6ac653b0a3ca8fdf81661d3fc3e2cb5e2a29e9b6ac6ef6b8730429f5b7028f5e224bf1d00ba87c7a091dbf6cc70fd340c6ecb19cb71609458c693f909fa76cfb5f6e818d92e78f5486e8cfb8d6f729f4abe80cf35acf1c1e0ff48e01c75a989f1eaeb87978f0b66e0a7160cedd7d761ec189586895b5a17193a3e3cc74d404aa339f302fd02ace224d3715d1501a1007d3d4885160c6b04d0fe33c6ca18bae3aff9e9b2ba74bf51933d7f82012c0937c6c706b5dfbbfdeceae7730dc43e90e3a233f8b6efdedc365ba6d16795a8970c524c8dd88daaa8f911ad3f17b7280c8d2677dd2e3743237e427b08825dafd30ad7e29755d45702b20c3799d2317f6a6b7e8265946347bbbf7216cbc7f91fd0e344d82e1b123df4cb4bb4dceeeb08c540dd57ddc7b14d1203fb30e5809003e3fbede8e4bc30ce5c06daf2787f90f3ae76cf71df4e5aa20b3e2304c60d83848ce04245970999a5299582ba2679d8417d74e53f42fbea0b59ec6d28c2574970e181c9b1f280f10bb87cbd32972be8fde05c32b7f620edde29906cdc38b7d6746c18051cc1405a2e42bd80a3ef18049565f1858b8d835253dff481c6836d3e64984981e9fe6cfef4f2274e0d618664c4a24733b9258d86bc23ab293fe8bccf240501731f1cdf09035f353d13b2ed591cc74252232d6b227147502dd06512fe22e766adc108c2e125d6a92dcf93331e37ead0e4ccc838d44d39991981cf8a198a2c1bace8e45f2a6e3b4882c1fac6451642c6b1861dd6636cbf7ef8ceeeb1e70dcb9d5b838e2f73619f67b8491517450832e52c7a03d231da1774811b3a926b4cc6d3e7cec106a1cded1cc899080fe5a38b4729e61cd47b1237ca6ff0cbc56e64a8f8a4e8b7f0e1957b4072f550983e10c66bc2778c6bd7bdbea10b23b80f6b581766799112d645c7f79ac841a9cd42c239c2754d10b0dbde34503db95f04d36c9290c667578faed887dd4e9514d523965f6e998747efa47003700b2c5a851e12ff55759b36c893f7c356a69bd3b0b8e530454b91372a7cb7fd185daf7b9dfc3735465ce9d4b6bbfa7a51d815a07a21e182c70709751548803eeefff3a675709ab2daf6f554d9a3bb6f33502988f8b45be05ed536e3d6daf3e360202d9a642612e12a3db625c4c0ca6659923cbf5e9283d8b7605f369da8dfc87c681db8cceb0533d8a13e808432201950ba10fa71ffd7370715cf8eb30bfdd50efc2c4e73be6abb46993d42b58afc600ba8ed81b39543a7f2f604698e5592290152e9908f404cc5df06d2ae848bff891d02b7c7a76d623c32134648935271b0b21cc3b946bd1ee5e3f8f7581cba698de48eb2f54f6dac4741fddd10b7f4d27d3ab62a1abecb13750cc46605d83a1d40caab5248992e03f2d9ef8ab036c2f1bcb1c3368e6a90e501a763e6474f704f2d0d10faad361d4817d2fdfadcca7f4b9dc94ae6db55c0ceec163aba4ac783be9d8afdc9763c677a8ee38ce804f120f1ff0831462a716d992d3b930d49f28d1400370d3f03de1ee24fec2859720dd96465c70acfc94606ca170de59909d2fad1a5ff9672c5d84896101fcbc161ecc7c7dba1e8a53e18c610002ca195b8af33ff8b1b44ec5a088bb945f1cac6ae01d27171f0ecd837701b813fb883c88cde4bac0690f7c5250ec8a724813b0fe2bc3f1efd51ac72188d36b37050033760bc619986b8e95e3d840784fdc952e7b65f1786f599b3296da384c73ec04433231ec2a30cbc5f270de749fec21dc9ad750fcd8b43c1ca2d528d6e1065e3af84b3b2cff31f737395c0ee4ccfcc3cc8ad2f13b5e01b52297c4c745f31bbe9f2c13c75f57d450a44c87a6cc2f1241448d3110e2bef4e081d5f1532ecbf73076301dbaf2df2724891562ea019b3a588f41affced4d68f278091ef8c9985287d0be06f09b2ed3a5de8aca7db36a746686c77b186bc408cd27ac67b36ba505f993f0c01b60d45eb1bd2dd18d068b3a2f6dfb9a383c376a2e64bab0c2ffb42e9224dd2feb2683bc0d7f1f1fa17e704d0b1a16fbfeb296752026d20c324ac35bf2c2094458378fc283faa3a13b667c055ebb5851746bc349e99eac02009e8e01608f3b5dfb9c345348c81062196b3713f106ce4980790f541aef50e889f165976b4ee733a6d521eaa284707ffb05adf79da4771842b773011294e7afbf6e7e1758859f42dc52695a78fe8c022a25910093d274986fdce1d8338f0b7e8187bbb996d881b4c2f7e84b98f1983c221fe8fc7fbff2ee342efaee0d8d1cc413da89ef8e7bcb15999cb0c7c7a04f4f03a66891b23c2ac7f6f68b932586a81f8c7bc497c31d6d869331341287cf9a776106631c50313a5d60d4e4b80889a4cc0cdab1ada2e9febd3eb3fd272d95dc3714253a05e236f16f2fe2e6c8025047a02e2e2818b0dafc4f759d2e37e6988e4948350ae008871101eeb7526372884051cf9553b4202a1926c207a46eb78d78e06b381a28b4d9bf7cbf20b2c7c50660c56f5d6a0f66b8291279b4ebaf462cc942fa124454e95b9aa0a480341d769defb510b3d82752d1f2f54e65141443993a3a8d267d5ec57edf0e025595aefae5c809fda61d4a5a4e335904fad6a72a80fcbd1f1a9f8752239e3d642d87a7083ccacd6766ca5ad6c3d0b83108f1e15c9b859ece8c7799f15dcbe357403fd85a6d2110bd76d4583817a9e2bfcaa1def4d1bb1be8b3ea84cbf6bc73d8b72720c348d03f9eceaa7fc142f67fd0f9a45c07212fec07e508618a66e42df5ba0ae5c675b157ba1ab8792671aa3e359db0376529f57271b86870a7b4665b06d31efde018c7b0b02dd77be07034cffd53b901d37a8262491e7fe78fa63daf8c3d0309f0b818e9e6eddf689f3ed2611fada75c540357343e81443d5b16d9cf11f38481883e36566f1efb9c6740c92ce3c504a5afff48eb040838fef90f9c8dcffe81da1bb16349d4b7cae8f23600e177d17619f6b11524ee2ffa027977e495521c26bf2570180814c8c9b5ad7f54498044e4b95c1a505f7cca155e3060ca39ccfc4f7fddd3bb49a67cc12ef4c7e61285de5afde21426a76e828d166f2c7f51fa3bfb9f45cb7f342e5057d92417548f752a1a4a01106ce0200d567ebf798ee1a4d1fee0ba83c37870c4f8bb73ac8edca5bf76d3675ed76f68eddc73b338ee6b53dc8fcee771de9a7778c6e1ee8fbaffff8fa8f9704a4a433704bfece4da2a4b6a583a8ab7bf32e00740bae22a103f36e749a1a3f13e0eb8d6469f1b062cc4336b3f33db0ca4509b319a01ead1ed50e852c80a51bd1ae4fc80ab29cf530b968fe7687cb47178f923b1e3dbaeff54d5625a513f6f79bcaaa8872fca700c0e4c2349d48180d1d3872e4d9d4064bec8d80c978baed411cf43aca320dda1165c02ea9ac01d7dd9f304e3c679debe483f8992788a6caeecd36a3e98725c1c347cc31d6d6b76d7deea15e6aae6eedd2bdd353fd300c437a018bc7ed4c588633fd9cc85583f6844d3b5e05e2fe5e157e5863b0bd8e800161011ae876034748057522e1e70d318af36b4ed74bfd54823f2c88d9e772833deedc8e86587d364342105736dbbb009046df17a85cee33966d7b34215d39ffe1bbadd7dc4a346ee914d813354ad373847782dc5e0caa449ff768a80f828df48c7af682bbb6a3dffd38ff95e01c50eb945d977a9d17f0f3ba31b61b78a5e27e1700fcb54a382363be2581cfbe13aeb763467b64f3971bdddf031dbabd6f1df7884077c53089517350afe3b9129a5f15cc81ce203f0833794232c4907b7cbeff0535b3a7b8877dcb3e787b712861bfb2dbd18261a13f05808ed0fe519325f2a7cc6cc1acf634f482a4cf82786d5749d7868047edafa643e81ad2f4ec3112ea129208db126bd751d6fd8c33694a681bdc2cc7ff80fae3fa891a4bba9a5288f7c5c4f0a215c4c2c8f6c84f0160fac8e785cab2bb868f3c21f38d5b109eb84e77d9f4b661c4dcf8b6a25a808ec078895c5274769a01d25e5a4aeddd615c2801ca1ec85a995045968febffee20318a9adea305a1688ee0f79dec790630f75300904c21c8790ecd5103500fcc9e10ed235240d3b12634af619f3910022732c9019ec48be0b64915ac45c73858c9f84972d5c40a34a813962a799efd621fcf7f8bcbbcd31a2f23890d78b0ddd1dc3bc632527e1700aa8b01f5e219d6e14f37cfeda268ef93d598558ef35e08a76b04f40dbb9aa5e3a728e4bea02ca5889f321887fcd729cf4307f73a28d9903b971c51333ebdfe34e129fa2588f9d46236ee17423801fd5ca0141fb30070b5f59ac9ecab653ceabaed53e9b9109082d8ecda2bd36a73f0c4708c84b808df253c2619747b333f8ca308ac7c4e06297037116299d4027feee3e93fd461a469e44413b5576c087f921601a1bdf08858979f02c05ada3e3b75f89d29a46b6f03292357e1c8cd539a6eae6352bd78f4947b5bc073587631a4ac393589115fac1aac304be918f5c1188a11b6d59937da3e9e005470a135878bb8d9b4a46c1a33a553fc22d89dea4ceebd885890ea85e79dab5be1356df6a1f80e0cbabf51003d22757df691c670cf982a788d62f6d589baeee397e323694d2bb3f940bb5a219009feb7cfae7e6ae0d25994ddac151891a1b4d2241e3f358a95a06d0674ea75885d2087258e408ef7dd853a0e346838db83b898a1bb7475ec1733c03bfa0ace914fa431412f728bbf04be99050a1bc611cf522a35c0c7fbffcbec69dc266a2304591e323aba245a082861ad64e640df3e277a0a9e140684d74c10acafb3acdb0217eb87b6a9bd365a90d1c0387c601bc636dd2202e58c580131df5f800f7a0527100dee462da9f7bbd18ffb3fb0fa585e02f20b413a3f3108a2073bd8cc682f4b189e564227f5070cfc764f6e121f19cf044653f282bcc90cf506c6a4eab0efccde517b3d6b683799c0174de5afc0775851f970fc5c76efcd93ce4f2fffb62ec5f6daf1b90af48d4e33b7cd484b52f0c96c8a4b96229b711d0ff2941e895e57dc04b36881bc317774f91ee3a00b68400702a7acd133f065121f36fe4e7c969217b6c1a98b493b79ee8ff33f1f0338745b06835be921910bfaa5b5242c1fceae4cadb76596228f057aa6e52549de8641ea6bdccc171152e46c213375fa38e0da817913dfeb3f135f9e57c5e9bd2983416243d2f5e9f54ff921727abf8461a2a43553b1cd6ed697a3b3881123c2143cfd3433798fdc3622aa15a0d252fdead1f4df834381825104e5bf263eb85b8fbc230a420048cdc7bddf7b76a165e005d76b4fc3d754c96c71288f554803f0cdb5688f0f0cbbdd943810ba2c4074b0b5f6c8c3d5824cdb7b7008b41995fc1f892fd82acb4c228d14645f790c1fef7f9f6b2c34ee0b47cc96fdd162443e2cc1cf2e6f6b1222fd443b867c37965b2e151cb544d643ed17419c9f11e4238969c7e6e96cfffcaf89cf9fa149d6acd016f208e2f3de1f44bf71bd5d2bde1865450995f2965fab2d2da09983a60bdab063a91c9bb3e8e7805ef8bfa54cf4129871b1317e11eb104390df4393ff94f89e439339a18e08674e8d11e4f3fdfffb1a93f548c8043842166b68eae7ab4ac18e235aa42d60fa56816fa7994dcb61203972181117240013eed5ba14035a2dbc68f36b68f21f0a1f3f439324b709fb5e96f174c48ff7bf39642891381650dd855ddab69aa3d7775b12a201776c18e9cf3bb58c6495d46e97d786f46fce4babe88d64f90c481aa6de842844faaf85af9fa1496efc49cc204defe3fc5f2800f981bc0e19f01a9c9218eef035293612482ff6f5cc59cd3187b820b15152e68c6b1f2c6ecbd6fb5848bdad06ca89f00e9183cacb3f143eff189a6466930401e72636b37ffcfcbd5e457f154a966d8b1214aae974dec87ba54397b59bbdcc43ac026360bdeed930ca85761e351dc3c2783ce483f35c395902b7ddff63e1fbd7d0e47d31da303622d7b421d68ff39feb39ea646c3a27960cfb314822c13c484eb602265d0bc435cff68f36b639c7211649ed313b2290c91ef8ee597841cfd74b2d9779f57f6d7c687f1f9a0c641c0da2225df0cfd7ffdbebb90855b92e616bab676262773234ac8da6fd9883c06a2db920510eaaccc2004f64962dd604d6c13a4e8a14096447a797cba54dd9fea5f1e5f8f7a1490956f8473db357f87f21017236e99b77d14833f3b118bb16fbd27022f11dd7db8b170c01b9a182a32367362a78a5391ff0ae84ef6b7b4890ae3c5790a8f608eddc421651df8d4fe7bf0f4d560411f9857b4b68f59f5e3ccc467f1589570d1d3ce4b54cc086c7fd3a77247cb06f59809c2d515d3c13caabcdda21e121e59eb9fbf046cf1e7140107d3366a152f8251dbb988d6f15c4bf0f4d8ea3d00672af44f5f2f1fe0f2bcb4e7cec59cd59fc998e36fd6a1b97720331d131156b34f8fa692758c919fc66835c3adc65aedebcf720dc7eb950399ae5d231eddf8d8fb30af61f8626378e3851a1617065f5c7f1ef86e8f6883520396f73a64dda799709f54b042dd1db758aa4751639e6dc074264783748875699bed6d826a65f0fa8af9d2143f2a36c0118674c0e20ff303489b17fb77953f702a57d7cfe71d9b3de6bd0f0ec01d9facc62ae733a0166ba272fd0fc82c00e1de3bcb34021fe5d25b983dc7a23d73dc44aa473c76033977f5720657a3c8d504947f20f439382aefb30a840e834edc7fbbf0b03d5de1efb35b8023567b59578ff638eed54bf47f4dcfb5519f423af52d41b4343c63f746478c71cf486f84c608cc68138a7f5ebe964677354e23f0f4d367d2c0c025f9d9a93201f965c826bd9ac04def89e6c74ce846caf73a08b3a6d2232d7e335b76201655c5d085cf20ccb9bdce47b7861df4c6fb98b51d37f6c50774b378dece93f0f4deea589005e612cd51f5ffe6c6228d4c031873ff1bfeb8306edf30ecf33931d84dd8c5e37a2dbf63af01085cbde83c3c35eba7573accf9a7142b9382ac18de8688047788c1fb059fe0f43936d3502834d7eed7b531f67c0396ee8915258961d437d5bf9ab8b179f68074fce74439b9731235733e8072a9afb3a3bbd265f18874714dbdb82b4269c487c89ddd0e0c1e835824c9189ff3c34c983eff25c6205a6fbc7fbbf861197443f5d664d7b56362a8253e43c879bd9db65998d302ca021abd80d41b4cbd8bebce7f685e4a1f68c4bb8089f6da49c6d9dc1c6df8dc3728b9dbd4ff76f4393ada49afcbc32890a081ff77fd6e12383c3702724b44690cbbf4021d27b309e17568411a65957a9d8c26f65ad2ca98a27aa8b4827babff760e772fd31cee2dae81583239acdbf0f4d5253961add101359ba8fdf7f925a421dd068c47c9678b2b748f3400d36db597a69571f6c84775113c10c111ce17d8279710f1773e0adecb32160abb31f7e7923a22d4f18dd0bd591aaf6b7a149a25895f7906dd6cb11147fbcfe07f1a8cc79cff181d1f59f7ceffc2a306f40a2811b5ddb93895e2860500f11ca71edabc28376b6ad6566b701e9cf76ee279a4f413d4280fb1e8c83dd8270e4ef43934a230abca8100cd69e9e8fa7ff7195e635b10d52d1c3ee17ba80eb81775626ed85290cd4a983d96e40706fef8de7b25dddcc7b33ac76b63a0bfa0e18c7fab586dec35991e8cd662a6157efd25f8726270a4a6b306ea589a2af44b878e88ffb7f0b945c2f4893dcccc9440ce17503ce710f5a965cda8c472be595b5580b7fec5aa5543e7a271137f951878171119b84b98ebeb9c98ced02234be2522ef22f4393bb0756d6dc67c751d7c8aca8e5e9e3f31f5c0be210ec60b8b73f64dcdb173179b394e5754ddb36c6b5385a585d38c3f85e7085aa7751eb1645bee70d2608ef8c87ad3116667213529a03c14b69fbf1e7d0241047a416ce13d3f0d4dd9c9d8fdbc701d095181e8caff068332781c0aec2f894b79306c77bbc1c1401b19a1e1e3b3bd67e46a0f64bee3337d06b9a1496cc753c0870f6b564ca8ee5e8882b43a87f199aa408990c8d52e10f91f74d7ab9c68fe7bf371b296e00d28008a9e604f6d4206c45030e471d1b79cd6cbd873a12cc91ff498ddeed926f1fb142ec3c415c8e8c9a5c368f64f0e8fd46a6e0711fe0cfa1c98551895be2b5313c6ba8eedd4ec33edeff9753257ceb82ced095ef73015475c66c65c1f592629a417d41b8ebe6ed169ecdb4fe31e3fd1b30c0417b1fc94bf993ca981b14d3babc746ad107f2c7d064164c493d729e09110fa2505267f3fbc7f35f4c62b852b3a3e90dc59eeb106c3fabe290949e2921b0d65bab8946c2e6086fd6e70b776926026b5e94435628744070fc5732d3f7b20ef37b68d27881489747526eb204cd898c6511044362fa7805d489b081aae93647c1b3a9f71d91ac8d5185d66432375cccd8ab649c53911bc93d5cbb4602a7662abc30e9911d03dba300dc0e4f500390fac7352872845adf9913785191105a163e08a57e47a041651e4e2df5e3f9ff6a59f4dc3797d2be6e3bfa6e3fd3b92250b7af78d27d1687b7951a16101d2fe04ee4f61800de573add8a26b497d7be86d93a78220ceaada5d183103b86c452e714ed8e94b9170c81ae63e49b85d199f5b1f1dc15fdfcfd17456c6d47c2a7613d8f0b176808f216c2a842475fe67585b3d7596e5aecabad46597320f455e9b522157a4d1b26cb3acbdd476eb801cf75ed2ef54458891a83c935586e141c9ac1a1f6029b3bde7db6d4ab25584f3fbd7e3226f9ddd81ceeb80746726a6aa21118a21d8538889b0b7941cac6e4172ab157100911a1b3138980a9d2104990d21198f70493392fb9a775826229f5815ab36c183aeb0c73e1bc0eb92038b20835c0c04af59a3e7eff69b4819c8190d0f398493c906b9ded0ec2b60d88d9a4e35ea253d7789a5b8be807b2d6730e80ad474417b8e116c0e32886002b89972245a3b9b3486ed686415f9be15c46b00b5af9bdd91925d2cc1b8b92c4d23fdeff3460b504acac779dd71e5f7bd937a11c1c12b91022f4dd30ab59ed06f1ae4093667d98a5b6c9ffe7c550456aaf6dc8da763aef51c81e1d88bf04efd2de724ebeaf5b77ccf49d7ae02af6b18a351c088b633fa26a1f9fffe51894ac94669d37b5b5973f4b5fd11a509dd1272c20735a286f540d7c91eac3935c2d25eae89492fb688492ec7983c1f8c5f3ca5c09634b2f1709cba75947d4bbda90fe868d12d17a2663220c2ac7282cdfd17fdcffb779ed459f71289a7907985bf5bcb21c7160e561a03758c4bcdde07e10ffc1fea4be52aea5d02fe6c97b68926885f1cc22a667f8c31a113d7708988ef7dda0fbd597888a843ffad3e9ffccb69175124e1d687bdc67170ff3fa3f7471aa9e886ded2ce5a275a25bf759c2f3da6f74ff06a12bf4801421ab6866dae72e325decddb93f8726959c7741e15288b4be46c3c4beebb6be115565ad82ab0725fd9d64cfcb6c021bb369f8818bdafe71fe974d7326e73699589b77162d26ce1e1659edbe853328a5ad99473991ac7b63d781ccd6acc7bf0f4de6dd3ba3a2ade91c76def0fa806e812297ce0801ed62b51db92904bd3c83d7becddcf187655ff031b6151dfc9c70e8e8f7e77df7ab3131f4dd462391df595883817015a471141a5d97dc738e7f1f9a2c5bc2252a261ca106b140831d44bc66f3c3d2b94187d9996673940cbdcb2ca883de3ebffe3359cf1cd49517083be73a2368454cacd79d5b01524f648bca115e47e99a418dcba966df7e5921fdcbd0e445e5ea18f1086e17b70ef4fc30af7ec5e02fab410eddf2810a964be56df78201b1103e5effdd5759a2718622b5011213a5e23c1808d186468ab2ceeb2a6307b9bd3a1a78db77847ca4cd9e77ad9725fd65685267b0e5d8fa01e6dce664bf39e7b4fccc95aacbc3769e33c0760d4467998d68e73e3e3ffff233e989689f59446b906743e32803cab910c283aad4958df4d753c901b786acd49c7ce80d113251c1fc3934a928f1bd3f4f471495513334850d66eb6363b3b6506f70035480596183965728a33e1fc7ff40ad74e358348860f04c47a6d99e7095088bc984e361efe39ac5b06c1e5ca45b398263fb3236f3e2550e7f0c4d36662cba8af2bccb00279b17a41185069e43d0d6f134f8693c44f76e04b2efa123f878fc97d0b30d487a35e7be0f5baa0ab25fa9cfc1b7e81bb451749cc16d89764ecb435fccf160a08778e8d1fce27e0d4d62080d730016d714504578bb86c7d8873be2c0f76357c1178c2a6e5c0298d63f99ff8fd7bf76833a4f08f5b1334066be1f83c2d252cf26c665dbf2f5f31d15efc76d05965b6dd99c43078487ea35661728fc0c4dd6e4d6d94e6903bf8e5958d078d0a813a5e6d53da7340a79c448184f913ce49e307c9a8fe77f1c1333f4a5cc1008cc5bfff642093a7f643191225ad94f242b4b78f769cc923def7398e54ab8fe80d467a688c80c057edf695b527a278618bc9a07c647159e23af7691977dda3ea53f577e701faf73cfa03f9eff6bb3de41f7d74bc372c5b5c4c5ce6b409649f07c3e5e78f073b83ffd5cd5f0602c441be8e4f5cc9201a98cb08efb35af07e99d5703178fdbfcc95ea313e8f8e7770b8044d319b54d0788e116f858665f383c257c1e0013385fec9989701250f3318685e3ee8457fec06c6ddf8398938a663f349efdbcbb3abdf2cc810f935fa3d4947620fb8f1dfb4de03b6b9e3a70cdd67fb21f0ee21c8d5fd6f9e1736ad88e71775d244687a37d1cffa215bb79ab0dd7d7fb2c621827d5e8b04e87cefe3dd46fb9f6d6f4d4302c0af5a8c7ffbabbe27e96c64d8b1e83c4fb0a599552b489a534e2362b218f947d407e75876ab063749d43d4fae0f5502ec55928fd78ffd78e8fa0bdd77ab53f371230c6575846dca3be6f1b038c1bbdb7af8b337a0e8b818a8030087fb016ae10c16338a852a3ea47b1a2671f9b30e0f6130c02a6549755eb1af8f284bdbb048b9d77ad222f34b3ded68f8ff7bfbeee43be51605b057b4fb39304ba63c86eaf5d9503b62b957bdb7bade7bcded5ce0b4e1121813395813caedec756ed82ec7978e92c21430ae69962b22ea0ea086c66d6d7e5c1f8270e84d2c15c20d79fcecfede3eb67752c10cebcb7b89a772d2f16f4df33dba5f9baf1ed4eb0ede258289b630d838ed3e26ff54d1144f09173ca19a261b723fc23307b630dc1c8c0f025b575445743420113a55a8041e9cd5a36bb2cd88fe7fb78fd0b285a2587b1b5d8e77780d1c0e18a48620668546232f9fe3c24f7c62a63c0bce6086e6bf7b3945ff7d79095f0abd0f7bf237b442b42103c9921e2c7fc532fbc67ff03c38f60f83994ad2e5e075d9f8fc39f99c643edcc2b3e99ec33ef832b9505fd35c6eb5da406719d8784c470a66a270a72af39b5bc4644c8ac297e3a0ce3e813ba9f9739476ddcec93783292a5f92d3f73069672614722797655b38170613b520f0f02abe7e3f8f7c4839c1720cc419f5c2617fab9930aff3f8d23c407ec6e09dbed30da65658dbdb493647e6f9271e5b6adaf210021c7b2505e48293ebe6fb5ebc8912eaec4bc0891d4b63d4879bb1937b453b42d4d02bd0ae13fdefff43879a363b6ce1199dfa77113847cdc2308de3522f932d0910b876cb56d2397fd5589d1ca0ebbecfd65fccea4ce5686cec862174d901483a4cd9805f76a41cc633706be5a1f0ae45d3e0b34bd6762f60ac415fdf1f5730932575273154b6703ed36de08e6e70d277338711f1b7af154545b94529b5b77c400cd29498b9739f83578a58edc576afdda0d2394216e4e2b7a5092c4da9413e209c14b1067e2b2c1de54458c2c07c201c1f9c7eb3fb3a57be95e527fa844dda53db40452a5755e7ac3ae63af92a7b3cc07a526440ebc048927693734f85315cb4abd1cdf948fed2648a5e655a91b47e0bb1c5b11fb82e1637208c3cda6818a3cfa928ba11ed7bf7e1efe89347dda85189eed68a2b0ce3baffcf26c70415af0b4259258d45a163b74ae513f8413b1f7f71d0e9ef37999ef6b312bc6b37536c21d5baba9ccd6e8860ce144f5996334bfc69cdaf3d0dd2a6484954223217ddeff653ec773616d68a1b9ffea4733635ee71ee697387539f3bcb5951be2b888df82cf55e9798787423b660b225990693dd644a70b3dcae21c82c13491ed8a26a18e92ded77cbe43fd93d233bbc4f79a675dd87efcfe9b57676a0668534958550e939122314135461e7f723406d7127bfce1dceb41e497e96c6a9f77b854f0cee196dd74363c1def2f4ec1a0208d5cfba4f5f3ab52a574c7fb0e98617c0cc669a8cccd3e89542e9a576f3e3eff788c95cfafbc59bce6c87be7b7b7006d2c074ab27b67677dc9767e29ea5d0f17d1e673213f77f8e8f7589b8981e6e8981e479746b0a56c21378e86559cd67c436ee11666efb3853a2e406d7125aec1e88794b17fdcffd3520617753f072e968f3aafe86142ebbcd6bacccc0f4523471c8367277958ab44e5ff75875357bae499c78a44298c983231d7d1b5198956b588616757ace0508eb3b2718dbde7abcfc892cf0b03cd1673941f3f7fc2b69540e5672229aaedaae7a9735cd654f2e2f47bde01eaab663367f7896f02d1fecf1d5e10773a5eebbc220095dd7bb918aa6423b3151821cf8674e07da92c6ec67957464b76088fc2845894123496837c3cfb8d422b04a0e8aec23888693d8f2d3bb408b35809351c6c3d2f786ceaf38e779596e5f71d6eb35dec200a19e02b53dbd3bc2c7bd59e00af2fd094e25600a1fbbb2b3c3c0dd8cc08a4aec06f201e9fe2dee7e8d487654e70f95e4ffffe16bcd9f98e4f760b7152b620a3137748014633fbdd81df3d6ffd8f3bfc607e6b9252cf037743dfcfedb29475c2a6e3196ba16a4598137cfb8971cfa98ed41404824b562ccf0be0cf2a3fdefffe786be1dc7d519078c155b9e913069c2f4ddbfcde03c4c2e1813d9d0ba4d5b070fdbecae63de78a90081ece5e78fa4216cb8ff717deda4b78aba7d340f05b90f2ce4999bed459425d1654aaa6e29e9007998fe7fffad14b7105c92e02e1bd8050d6db771bffbcc3d341475cf49a57c2cf8b81d8e239f9e30ecf32531eb397e5415ce893595c9cdf793dc7a8f0476af7becce9c7f5d5a2c24dee401ae28c74d482b6847603447fbefc830e7ace2bb2d9cc420ec4ba5cb79facfcbcc3d5ac85f7d9eb36cbdb6b99364cfeb8caa6da52df5f72fd6e9d6c7c5541cfaf103c110aa2b9cb7ca6b22182b83bba12c138995f31dc67e973de8df9c6439f87ffbfe4200b3eea5642897ee62cb6b9ae19a0cb985fff266699e4d56a70f12f57d9d48dca776100f94f20bc9ab0480c1233c9dbfbfebe01496098c8f275d8f915bbf5b831eacda4c9fbb708c8cff7ff7fe52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef295af7ce52b5ff9ca57bef21580ff07dd9a19997729a6f70000321069545874584d4c3a636f6d2e61646f62652e786d7000000000003c3f787061636b657420626567696e3d22efbbbf222069643d2257354d304d7043656869487a7265537a4e54637a6b633964223f3e0a3c783a786d706d65746120786d6c6e733a783d2261646f62653a6e733a6d6574612f2220783a786d70746b3d2241646f626520584d5020436f726520342e322e322d633036332035332e3335313733352c20323030382f30372f32322d31383a31313a31322020202020202020223e0a2020203c7264663a52444620786d6c6e733a7264663d22687474703a2f2f7777772e77332e6f72672f313939392f30322f32322d7264662d73796e7461782d6e7323223e0a2020202020203c7264663a4465736372697074696f6e207264663a61626f75743d22220a202020202020202020202020786d6c6e733a786d703d22687474703a2f2f6e732e61646f62652e636f6d2f7861702f312e302f223e0a2020202020202020203c786d703a43726561746f72546f6f6c3e41646f62652046697265776f726b73204353343c2f786d703a43726561746f72546f6f6c3e0a2020202020202020203c786d703a437265617465446174653e323030392d30342d31395430303a32323a30365a3c2f786d703a437265617465446174653e0a2020202020202020203c786d703a4d6f64696679446174653e323030392d30342d31395430303a32353a32325a3c2f786d703a4d6f64696679446174653e0a2020202020203c2f7264663a4465736372697074696f6e3e0a2020202020203c7264663a4465736372697074696f6e207264663a61626f75743d22220a202020202020202020202020786d6c6e733a64633d22687474703a2f2f7075726c2e6f72672f64632f656c656d656e74732f312e312f223e0a2020202020202020203c64633a666f726d61743e696d6167652f706e673c2f64633a666f726d61743e0a2020202020203c2f7264663a4465736372697074696f6e3e0a2020203c2f7264663a5244463e0a3c2f783a786d706d6574613e0a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020200a3c3f787061636b657420656e643d2277223f3eb7cb5ef0000002aa494441546881ed9aeb8eaa301485572922b1efff9a86c41808f4727e9c9400bdec5d83719c6913333a7eac7677df8a2aacb50e008410c88d466b0d6b2dacb559b09de719524a4829ff5fd93471c5e7f389711c31cf338c3170cec515876140dff7b8dd6e10424008012965b0e6f67ebf432905e7dcba84a669e2e0b22c0080aeeb70b95c56c52ddc0ec300ad35a494504aa1ef7b745db71ab7828fc703ce39f47d8f711ca194c2f57a45dbb67bc5711c218480520ad334615916586b03ebd77d9ca609f33c436b1ddda6c61803ad35966581d61ade530168ad853106c698d595ceb910f4ffcc4100d00048ba2d50f470ee8278a854b018f431778ce8a82295fc3bd027544a39987afb3a00fd63ab1a55a4a6dd4ded6b4e5271bb1e727b38e3f781b1cd0e5c987a2350dcaa91d143413bf01818d9306329926bf416b3928b33be1264bb30a9c82e52decfdbbcdefe8d8259176ea11cdce40a53d4ead881236a35353eedeb541d8f2ab2b787ba60d7678efe8e2a52753cd9675e8f9ea3215963726b0b406abcb9d89fa3c8f1ca0e2c7221e9194ecabe5ea4c8c0a5c6a77dcd0239dd7fa75854008ada705691eafe3b90559a59458abdc6e43b3f103c2fc2d92d2e96aec97eede1dc32ca82a238ccb2c6506b0b14a9f1c9a302a7daae20c06c71d48d59746af61a53cb28bb81645b7d6ed3fc069063f10e245d489de8cb155f4a57b286ffdac69e2df62cc5ed66b3d2952a7dfca342f1cd19bba450e32b7ced9f9c67755163678519a72caf8a451f02b283826d0c09924431c8ee339ccd06de92336cc5e319fc3d278024c8193f215d49f0fc782c0a333fce69c32f979424c892fb16f08311fe9ed31edb85e7362416f561f0fc0867f7c22dc472216bea738f5c7fd3d7c5dbc36ac31cc5f6f80549766ace071ac2717e2d802f7161052b58c10a56b08215ac60052b08fc03db6bb0834682f6770000000049454e44ae4260820a
';
	}
	elseif($_REQUEST['image'] == 'carat')
	{
		header('Content-Type: image/gif');
		
		$image = '47494638396108000800f70100ccccccffffff00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021f90401000001002c000000000800080000081d0003040020b0a04000040d0e44a81021c3820e131e7c08512244850101003b0d0a';
	}
	elseif($_REQUEST['image'] == 'loading')
	{
		header('Content-Type: image/gif');
		
		$image = '47494638396110001000b30c00aaa8a0918e869d9b93b7b5ad9d9b92b7b5ac908f86aaa89fc3c1b9d0cec577756c848279ffffff00000000000000000021ff0b4e45545343415045322e30030100000021f9040500000c002c000000001000100000044f90c9c98ca198067bb31404b359ccb260021892a649a54438b5e72b53ee0400dea4288c83f0d0fbfd86448f1128ec497e94c2209348600ad2e9a45a8d66190804836b8d4ec3e171d983163b27e94c040021f9040500000c002c000000001000100000044e90c9c904a1980a7bb30440c53186811de8515d41694ea93ab5e52953f5540cdeb42c8cc28ee7f9fd840362c608dcf5243f0a0291512830d329c56a9566198904837bf582c3e26e0f2d7e4ec29e080021f9040500000c002c000000001000100000044d90c9c9cea198da03f22c456575828081a0c8942535bc0345cc04f666338520de14048cddae67281a843c8fd1104c1299934422b35860a4524ab54ac14e150ac6d6dafd82c3dcde39dca3803d110021f9040500000c002c000000001000100000044e90c9c94aa1985a9b2742cc200ecc7160df1792e601502938b52f2c53269524dd24088cddaef7fb09791d82921044f694148522130860a4528a616b8862198b0583dbf582c3e26def1c5e53da99080021f9040500000c002c000000001000100000044d90c9c910a2985a9b674ad5c60c05f67de14896de490dab096224a528dd741ccc7de7bb9d0fd709f27e39060040592c320401c6e9a410a2d209f5693030ae82ac96db0d7cc599aec19c9c743b110021f9040500000c002c000000001000100000044d90c9c952a2985a9ba752d5c62008f67de14896de49ad6c9b91d4b274d35030b68d0fba42efd60116763e1c43473118328703c6690850a2d1a6d34a1030b0522d83d0f502004ab2c0ab9c743b110021f9040500000c002c000000001000100000044d90c9c994a2985a9be75ad5c62409f67de14896de49ad6c9b91946174138230b68debbadead03dc050238898e42206406034cb34981422982ac807138300ae00226cb0074bde0a4b99ba4b03311003b0a';
	}

	for($i = 0; $i < strlen($image); $i+=2)
	{
		print chr(hexdec($image[$i] . $image[$i+1]));
	}
	
	exit;
}

?>

