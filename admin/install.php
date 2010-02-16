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
<link rel="stylesheet" href="./live.css" type="text/css"/>
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

select, a.wide {
	width: 200px;
	margin-right:50px;
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
                                                
                                                <table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF">
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
												
												if($_REQUEST['step'] == 2)
												{
													
												?>
                                                
                                                <h2>Path Information</h2>
                                                <p>Before the site can't function properly, we must define some paths for templates and plugins to use.</p>
                                                
                                                <table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF">
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
												<input type="text" name="HTML_DOMAIN" value="<?php echo $HTML_DOMAIN; ?>/" />
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
												if($_REQUEST['step'] == 3)
												{
													
													include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb.inc.php';
													include $LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'adodb5' . DIRECTORY_SEPARATOR . 'adodb-exceptions.inc.php';
												?>
                                                
                                                <h2>Database Setup</h2>
                                                <p>This site is largely based on database use; we will configure this now.  Although database use is optional, it is highly recommended for security and searching.  There will be no search options available without a database, only a flat file-structure will be shown.</p>
                                                
                                                <table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF">
                                                <?php
												
												// set up database type
												if(!isset($DB_TYPE))
													$DB_TYPE = 'mysql';
												?><tr><td class="title">Database Type</td>
                                                <td>
												<select name="DB_TYPE">
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
												?><tr><td class="title warn">Database Server</td>
                                                <td>
												<input type="text" name="DB_SERVER" value="<?php echo $DB_SERVER; ?>" />
                                                </td>
                                                <td class="desc">
												<ul>
													<li>Please specify an address of the database server to connect to.</li>
                                                    <li>WARNING: If this information is wrong, it could take up to 1 minute or more to detect these errors.</li>
												</ul>
                                                </td></tr>
												<?php
												
												/*
												if(!isset($DB_SERVER))
													$DB_SERVER = 'localhost';
												# or dsn 
												$dsn = $DB_TYPE . '://user:pwd@209.250.30.30/?ConnectionTimeout=0'; 
												try { 
													$conn = ADONewConnection($dsn);  # no need for Connect()
												} catch (exception $e) { 
													 var_dump($e); 
													 adodb_backtrace($e->gettrace());
												}
												*/
												
												// set up database username and password
												if(!isset($DB_USER))
													$DB_USER = 'username';
												?><tr><td class="title">Database User Name</td>
                                                <td>
												<input type="text" name="DB_USER" value="<?php echo $DB_USER; ?>" />
                                                </td>
                                                <td class="desc">
												<ul>
													<li>Please specify a username to log in to the database.</li>
												</ul>
                                                </td></tr>
												<?php
												if(!isset($DB_PASS))
													$DB_PASS = 'password';
												?><tr><td class="title">Database Password</td>
                                                <td>
												<input type="text" name="DB_PASS" value="<?php echo $DB_PASS; ?>" />
                                                </td>
                                                <td class="desc">
												<ul>
													<li>Please specify a password to log in to the database.</li>
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
												
												
												if($_REQUEST['step'] == 4)
												{
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
																unset($tmp_modules[$i]);
															}
														}
														$error_count++;
													}
													
													
												?>
                                                <h2>Select Modules</h2>
                                                <p>Below is a list of available modules.  Modules can be added or removed at any time, but with large file-structures, inserting new modules could take a very long time.  Therefore, all modules are enabled by default, with the recommended modules marked as such.</p>
                                                
                                                
                                                <table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF">
                                                <?php
												
												foreach($new_modules as $key => $module)
												{
													if(constant($module . '::INTERNAL') == true)
														continue;
													
													$module_en = $module . '_ENABLED';
													if(!isset($$module_en))
														$$module_en = true;
													?><tr><td class="title"><?php echo constant($module . '::NAME'); ?></td>
													<td>
                                                    <select name="<?php echo $module; ?>_ENABLED">
                                                    	<option value="true" <?php echo ($$module_en == true)?'selected="selected"':''; ?>>Enabled (Recommended)</option>
                                                    	<option value="false" <?php echo ($$module_en == false)?'selected="selected"':''; ?>>Disabled</option>
                                                    </select>
													</td>
													<td class="desc">
													<ul>
														<li>Choose whether or not to select the <?php echo $module; ?> module.</li>
													</ul>
													</td></tr>
													<?php
													
												}
												
												?></table><?php
												
												}
												
												// create database
												if($_REQUEST['step'] == 5)
												{
													
												?>
                                                <h2>Install Database</h2>
                                                <p>Before we go any further, the database should be installed.  Below, each table will be created, if there are any errors, you will be notified and given the option to return to the previous step.</p>
                                                
                                                
                                                <?php
												}
												
												
												// set up templates
												if($_REQUEST['step'] == 6)
												{
												
												?>
                                                
                                                <h2>Template Settings</h2>
                                                <p>This site supports multiple templates.  In order for users to have the best visual experience, we recommend you review these settings.</p>
                                                
                                                <table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF">
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
												
												?></table><?php
												
												}
												?>
                                                
                                                	<br />
                                                    <br />
                                                    <br />
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
