<?php

function register_live_install()
{
	return array(
		'name' => 'Live Install',
	);
}

function theme_live_install()
{
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php print isset($GLOBALS['templates']['vars']['title'])?$GLOBALS['templates']['vars']['title']:HTML_NAME; ?></title>
	<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
	<link rel="stylesheet" href="<?php print href('plugin=admin_install&install_image=style'); ?>" type="text/css"/>
	<script language="javascript">
	var loaded = false;
	</script>
	</head>
	<?php
	
	if(!is_float($GLOBALS['templates']['vars']['install_step']))
	{
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
											
											for($i = 1; $i <= $GLOBALS['templates']['vars']['install_step']; $i++)
											{
											?>
											<li><img src="/?plugin=admin_install&install_image=carat" class="crumbsep" alt="&gt;" /></li>
											<li><a href="<?php echo generate_href('plugin=admin_install&install_step=' . $i); ?>">Step <?php echo $i; ?></a></li>
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
		
		
		
		
		<h1 class="title">Media Server Installer - Step <?php echo $GLOBALS['templates']['vars']['install_step']; ?></h1>
		<span class="subText">This script will help you install the media server.<br />
		The first step is to check for requirements and dependencies for the media server.</span>
		
		
		<?php
		
		theme('errors');
	}

	if(!is_float($GLOBALS['templates']['vars']['install_step']))
		output_heading($GLOBALS['templates']['vars']['install_step']);
	
	output_tests($GLOBALS['templates']['vars']['install_step']);
	
	if(!is_float($GLOBALS['templates']['vars']['install_step']))
		output_buttons($GLOBALS['templates']['vars']['install_step']);

	if(!is_float($GLOBALS['templates']['vars']['install_step']))
	{
		?>
			</div>
		<?php
		theme('footer');
	}
}

// print the fields that came before the current page, just incase the session runs out
function print_fields()
{
	switch($GLOBALS['templates']['vars']['install_step'])
	{
		case 1:
		$count = 0;
		break;
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
		case 5.1:
		case 5.2:
		case 6:
		$count = 11 + count($GLOBALS['modules']);
		break;
		case 7:
		$count = 11 + count($GLOBALS['modules']) + 4;
		break;
		case 8:
		$count = 11 + count($GLOBALS['modules']) + 6;
		break;
		case 9:
		$count = 11 + count($GLOBALS['modules']) + 13;
		break;
	}
	for($i = 0; $i < $count; $i++)
	{
		$post = $GLOBALS['templates']['vars']['post'][$i];
		?>
        <input type="hidden" name="<?php echo $post; ?>" value="<?php echo $GLOBALS['templates']['vars']['request'][$post]; ?>" />
        <?php
	}
}

function output_heading($install_step)
{
	switch($install_step)
	{
		case 1:
			?>
			<h2>Requirements</h2>
			<p>First the script must check for a few necissary requirements in order for the site to run properly.</p>
			<?php
		break;
		case 2:
			?>
            <h2>Local Resources and Libraries</h2>
            <p>Before the site can't function properly, we must define some paths for templates and plugins to use.</p>
			<?php
		break;
		case 3:
			?>
            <h2>Database Setup</h2>
            <p>This site is largely based on database use; we will configure this now.  Although database use is optional, it is highly recommended for security and searching.  There will be no search options available without a database, only a flat file-structure will be shown.</p>
			<?php
		break;
		case 4:
			?>
            <h2>Select Modules</h2>
            <p>Below is a list of available modules.  Modules can be added or removed at any time, but with large file-structures, inserting new modules could take a very long time.  Therefore, all modules are enabled by default, with the recommended modules marked as such.</p>
			<?php
		break;
		case 5:
			?>
            <h2>Install Database</h2>
            <p>Before we go any further, the database should be installed.  Below, each table will be created, if there are any errors, you will be notified and given the option to return to the previous step.</p>
			<?php
		break;
		case 6:
			?>
            <h2>Template Settings</h2>
            <p>This site supports multiple templates.  In order for users to have the best visual experience, we recommend you review these settings.</p>
			<?php
		break;
		case 7:
			?>
            <h2>Cron Settings</h2>
            <p>The site will perform indexing of files while it is not being used.  This provides fast searching and reads more detailed information such as Artist and Album for MP3s.</p>
			<?php
		break;
		case 8:
			?>
            <h2>Optional Settings</h2>
            <p>There are a few optional settings that affect the behavior of the site.  We will review these now.</p>
			<?php
		break;
		case 9:
			?>
            <h2>Save the Configuration</h2>
            <p>Almost done!  Saving the configuration is the last step, once this is complete the site will be up and ready to use.</p>
			<?php
		break;
	}
	
	?><form action="" method="post"><?php
	
	print_fields();
	
	?><table border="0" cellpadding="0" cellspacing="0"><?php
}

function output_tests($install_step)
{
	$output = array();
	switch($install_step)
	{
		case 1:
		$output = array(
			'system_type' => 'SYSTEM_TYPE',
			'settings_perm' => $GLOBALS['templates']['vars']['settings'],
			'mod_rewrite' => '',
			'memory_limit' => $GLOBALS['templates']['vars']['memory_limit'],
			'encode' => 'ENCODE_PATH',
			'convert' => 'CONVERT_PATH'
		);
		break;
		case 2:
		$output = array(
			'local_root' => 'LOCAL_ROOT',
			'check_adodb' => '',
			'check_pear' => '',
			'check_smarty' => '',
			'check_getid3' => '',
			'check_snoopy' => '',
			'check_extjs' => '',
			'html_domain' => 'HTML_DOMAIN',
			'html_root' => 'HTML_ROOT'
		);
		break;
		case 3:
		$output = array(
			'db_type' => 'DB_TYPE',
			'db_server' => 'DB_SERVER',
			'db_user' => 'DB_USER',
			'db_pass' => 'DB_PASS',
			'db_name' => 'DB_NAME',
			'drop_tables' => ''
		);
		break;
		case 4:
		$output = array(
			'enable_modules' => '',
		);
		break;
		case 5:
		$output = array(
			'db_check' => ''
		);
		break;
		case 5.1:
		$output = array(
			'db_test' => ''
		);
		break;
		case 5.2:
		$output = array(
			'db_install' => ''
		);
		break;
		case 6:
		$output = array(
			'site_name' => 'HTML_NAME',
			'local_base' => 'LOCAL_BASE',
			'local_default' => 'LOCAL_DEFAULT',
			'local_template' => 'LOCAL_TEMPLATE'
		);
		break;
		case 7:
		$output = array(
			'cron' => 'HTML_NAME',
			'dir_seek_time' => 'DIRECTORY_SEEK_TIME',
			'file_seek_time' => 'FILE_SEEK_TIME'
		);
		break;
		case 8:
		$output = array(
			'debug_mode' => 'DEBUG_MODE',
			'deep_select' => 'RECURSIVE_GET',
			'robots' => 'NO_BOTS',
			'tmp_files' => 'TMP_DIR',
			'user_files' => 'LOCAL_USERS',
			'buffer_size' => 'BUFFER_SIZE',
			'aliasing' => 'USE_ALIAS'
		);
		break;
		case 9:
		$output = array(
			'save' => '',
		);
		break;
	}
	
	foreach($output as $test_name => $variable)
	{
		if(isset($GLOBALS['templates']['vars']['request'][$variable]))
			$variable = $GLOBALS['templates']['vars']['request'][$variable];

		call_user_func_array('output_test_' . $test_name, array($GLOBALS['templates']['vars']['tests'][$test_name], $variable));
	}
}

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
	<input type="hidden" name="install_dberror" value="<?php echo $e->userinfo; ?>" />
	<input type="submit" value="Panic!" />
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

function output_test_db_install($result, $variable)
{
	include_once 'PEAR.php';
	include_once $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . 'include' . DIRECTORY_SEPARATOR . 'database.php';
	
	if(isset($_SESSION)) session_write_close();
	
	$dsn = $GLOBALS['templates']['vars']['request']['DB_TYPE'] . '://' . 
		$GLOBALS['templates']['vars']['request']['DB_USER'] . ':' . 
		$GLOBALS['templates']['vars']['request']['DB_PASS'] . '@' . 
		$GLOBALS['templates']['vars']['request']['DB_SERVER'] . '/' . 
		$GLOBALS['templates']['vars']['request']['DB_NAME']; 
		
	$DATABASE = new database($dsn);
	?>
	<body onload="top.document.getElementById('loading2').style.display = 'none'; top.document.getElementById('install').style.height=document.getElementById('installtable').clientHeight+'px';">
	<form action="<?php echo generate_href('plugin=admin_install&install_step=3'); ?>" method="post" target="_top">
	<?php
	print_fields();
	?>
	<table id="installtable" border="0" cellpadding="0" cellspacing="0">
	<?php
	$DATABASE->install('printEachStep');
	?>
	</table>
	</form>
	</body>
	</html>
	<?php
}

function output_test_db_test($result, $variable)
{
	include_once 'PEAR.php';
	include_once $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . 'include' . DIRECTORY_SEPARATOR . 'database.php';

	if(isset($_SESSION)) session_write_close();

	ob_start();

	$dsn = $GLOBALS['templates']['vars']['request']['DB_TYPE'] . '://' . 
		$GLOBALS['templates']['vars']['request']['DB_USER'] . ':' . 
		$GLOBALS['templates']['vars']['request']['DB_PASS'] . '@' . 
		$GLOBALS['templates']['vars']['request']['DB_SERVER'] . '/' . 
		$GLOBALS['templates']['vars']['request']['DB_NAME']; 
	$conn = ADONewConnection($dsn);  # no need for Connect()

	$result = ob_get_contents();
	
	ob_end_clean();
	$e = ADODB_Pear_Error();
	if(isset($e) && $e !== false)
	{

	?>
<body onLoad="top.document.getElementById('loading1').style.display = 'none'; top.document.getElementById('test').style.height=document.getElementById('testtable').clientHeight+'px';">
<table id="testtable" border="0" cellpadding="0" cellspacing="0">
	<tr>
    <td class="title fail">Access to Database</td>
    <td>
    The connection manager reported the following error:<br /><?php echo $e->userinfo; ?>.
    <form action="<?php echo generate_href('plugin=admin_install&install_step=3'); ?>" method="post" target="_top">
    <input type="hidden" name="install_dberror" value="<?php echo $e->userinfo; ?>" />
	<?php
	print_fields();
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
<body onLoad="top.document.getElementById('loading1').style.display = 'none'; top.document.getElementById('test').style.height=document.getElementById('testtable').clientHeight+'px'; top.document.getElementById('loading2').style.display='inline'; top.document.getElementById('install').src='<?php echo generate_href('plugin=admin_install&install_step=5.2'); ?>'">
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
	
}

function output_test_system_type($result, $variable)
{
	// check for permission to settings file
	?><tr><td class="title">System Type</td>
	<td>
	<select name="SYSTEM_TYPE">
		<option value="win" <?php echo ($variable == 'win')?'selected="selected"':''; ?>>Windows</option>
		<option value="nix" <?php echo ($variable == 'nix')?'selected="selected"':''; ?>>Linux or Unix</option>
		<option value="mac" <?php echo ($variable == 'mac')?'selected="selected"':''; ?>>Mac OS</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>The system has detected that you are running <?php echo ($variable=='win')?'Windows':(($variable=='nix')?'Linux or Unix':'Mac OS'); ?>.</li>
		<li>If this is not correct, you must provide permissions to the correct settings.&lt;os&gt;.php file.</li>
	</ul>
	</td></tr>
	<?php
	
}

function output_test_settings_perm($result, $variable)
{
	// check for file permissions
	if($result)
	{
		?><tr><td class="title fail">Access to Settings</td>
		<td>
		<input type="text" disabled="disabled" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that is has access to the settings file.  Write permissions should be removed when this installation is complete.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Access to Settings</td>
		<td>
		<input type="text" disabled="disabled" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system would like access to the following file.  This is so it can write all the settings when we are done with the install.</li>
			<li>Please create this file, and grant it Read/Write permissions.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_mod_rewrite($result)
{
	// check for mod_rewrite
	if($result)
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
}

function output_test_memory_limit($result, $variable)
{
	// check memory limit
	if($result)
	{
		?><tr><td class="title">Memory Limit</td>
		<td>
		<a class="wide" href="http://php.net/manual/en/ini.core.php">PHP Core INI Settings</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that the set memory limit is enough to function properly.</li>
			<li>This system requires a large amount of memory for encoding and converting files, some of the third party libraries are not memory efficient.</li>
			<li>PHP reports that the set memory_limit is <?php echo $variable; ?>.</li>
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
			<li>PHP reports that the set memory_limit is <?php echo $variable; ?>.</li>
		</ul>
		</td></tr>
		<?php
	}
}


function output_test_encode($result, $variable)
{
	// check for convert and image magic and vlc
	if($result)
	{
		?><tr><td class="title">Encoder Path</td>
		<td>
		<input type="text" name="ENCODE_PATH" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>An encoder has been set and detected, you may change this path to specify a new encoder.</li>
			<li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
			<li>The encoder detected is "<?php echo basename($variable); ?>".</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title warn">Encoder Path</td>
		<td>
		<input type="text" name="ENCODE_PATH" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system needs some sort of file encoder that it can use to output files in different formats.</li>
			<li>This encoder could be VLC or FFMPEG.</li>
		</ul>
		</td></tr>
		<?php
	}
}


function output_test_convert($result, $variable)
{
	if($result)
	{
		?><tr><td class="title">Convert Path</td>
		<td>
		<input type="text" name="CONVERT_PATH" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>A converter has been set and detected, you may change this path to specify a new converter.</li>
			<li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
			<li>The encoder detected is "<?php echo basename($variable); ?>".</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title warn">Convert Path</td>
		<td>
		<input type="text" name="CONVERT_PATH" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system needs some sort of image converter for creating thumbnails of images and outputting images as different file types.</li>
			<li>This convert could be ImageMagik.</li>
		</ul>
		</td></tr>
		<?php
	}
}


function output_test_local_root($result, $variable)
{
	// check for local root
	if($result)
	{
		?><tr><td class="title">Local Root</td>
		<td>
		<input type="text" name="LOCAL_ROOT" value="<?php echo $variable; ?>" />
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
		<input type="text" name="LOCAL_ROOT" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that there is no "include" directory in the site root folder.  You must specify the root directory that the site lives in.</li>
			<li>This directory MUST end with a directory seperate such as / or \.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_check_adodb($result, $variable)
{
	// check for libraries
	if($result)
	{
		?><tr><td class="title">ADOdb Library</td>
		<td>
		<label>ADOdb Detected</label>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that ADOdb is installed in the includes directory.</li>
			<li>ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">ADOdb Library Missing</td>
		<td>
		<a href="http://adodb.sourceforge.net/">Get ADOdb</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that ADOdb is NOT INSTALLED.</li>
			<li>The root of the ADOdb Library must be placed in &lt;site root&gt;/include/adodb5</li>
			<li>ADOdb is a common PHP database abstraction layer that can connect to dozens of SQL databases.</li>
		</ul>
		</td></tr>
		<?php
	}
}


function output_test_check_pear($result, $variable)
{
	$pear_libs = array('File/Archive.php' => 'File_Archive', 'MIME/Type.php' => 'MIME_Type', 'Text/Highlighter.php' => 'Text_Highlighter');
	$not_installed = array();
	foreach($pear_libs as $lib => $link)
	{
		if((@include_once $lib) === false)
			$not_installed[$lib] = $link;
	}
	
	// check for PEAR libraries
	if($result)
	{
		?><tr><td class="title<?php echo (count($not_installed) > 0)?' warn':''; ?>">PEAR Installed</td>
		<td>
		<label>PEAR Detected</label>
		<?php
		if(count($not_installed) > 0)
		{
			?><br />However, the following packages must be installed:<br /><?php
			foreach($not_installed as $lib => $link)
			{
				?><a href="http://pear.php.net/package/<?php echo $link; ?>"><?php echo $link; ?></a><br /><?php
			}
		}
		else
		{
			?><br />The following required packages are also installed:<br /><?php
			foreach($pear_libs as $lib => $link)
			{
				?><a href="http://pear.php.net/package/<?php echo $link; ?>"><?php echo $link; ?></a><br /><?php
			}
		}
		?>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that PEAR is installed properly.</li>
			<li>The PEAR library is an extensive PHP library that provides common functions for modules and plugins in the site.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">PEAR Missing</td>
		<td>
		<a href="http://pear.php.net/">Get PEAR</a>
		<?php
		if(count($not_installed) > 0)
		{
			?><br />As well as the following libraries:<br /><?php
			foreach($not_installed as $lib => $link)
			{
				?><a href="http://pear.php.net/package/<?php echo $link; ?>"><?php echo $link; ?></a><br /><?php
			}
		}
		?>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that PEAR is NOT INSTALLED.</li>
			<li>The PEAR library is an extensive PHP library that provides common functions for modules and plugins in the site.</li>
		</ul>
		</td></tr>
		<?php
	}
}


function output_test_check_smarty($result, $variable)
{
	// check for smarty
	if($result)
	{
		?><tr><td class="title">Smarty Templates</td>
		<td>
		<label>Smarty Templating System detected</label>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that Smarty system is installed in the includes directory.</li>
			<li>Smarty templates is a templating system for adding logic and extra functionality to a standard HTML document.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Smarty Templates Missing</td>
		<td>
		<a href="http://www.smarty.net/">Get Smarty</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that Smarty Templates is NOT INSTALLED.</li>
			<li>The root of the Smarty system (eg "Smarty-2.6/libs") must be placed in &lt;site root&gt;/include/Smarty/</li>
			<li>Smarty templates is a templating system for adding logic and extra functionality to a standard HTML document.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_check_getid3($result, $variable)
{
	// check for getID3()
	if($result)
	{
		?><tr><td class="title">getID3() Library</td>
		<td>
		<label>getID3() Library detected</label>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that getID3() library is installed in the includes directory.</li>
			<li>getID3() is a library for reading file headers for MP3s and many different file formats.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">getID3() Library Missing</td>
		<td>
		<a href="http://www.smarty.net/">Get ID3()</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that getID3() Library is NOT INSTALLED.</li>
			<li>The root of the getID3() library must be placed in &lt;site root&gt;/include/getid3/</li>
			<li>getID3() is a library for reading file headers for MP3s and many different file formats.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_check_snoopy($result, $variable)
{
	// check for snoopy cUrl api
	if($result)
	{
		?><tr><td class="title">Snoopy cUrl API</td>
		<td>
		<label>Snoopy detected</label>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that the Scoopy cUrl API is installed in the includes directory.</li>
			<li>Snoopy is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Snoopy cUrl API Missing</td>
		<td>
		<a href="http://sourceforge.net/projects/snoopy/">Get Snoopy</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that Snoopy cUrl API is NOT INSTALLED.</li>
			<li>The Snoopy class (Snoopy.class.php) must be placed in &lt;site root&gt;/include/</li>
			<li>Snoopy is an API for making connections to other sites and downloading web pages and files, this is used by the db_amazon module.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_check_extjs($result, $variable)
{
	// check for EXT JS common
	if($result)
	{
		?><tr><td class="title">EXT JS</td>
		<td>
		<label>EXT JS Detected</label>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that EXT JS is installed in the templates/plain/extjs directory.</li>
			<li>EXT JS is a javascript library for creating windows and toolbars in templates, this library can be used across all templates.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">EXT JS Missing</td>
		<td>
		<a href="http://www.extjs.com/">Get EXT JS</a>
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that EXT JS is NOT INSTALLED.</li>
			<li>The EXT JS root folder must be placed in &lt;site root&gt;/templates/plain/extjs/</li>
			<li>EXT JS is a javascript library for creating windows and toolbars in templates, this library can be used across all templates.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_html_domain($result, $variable)
{
	// check for html domain
	?><tr><td class="title">HTML Domain</td>
	<td>
	<input type="text" name="HTML_DOMAIN" value="<?php echo $variable; ?>" />
	</td>
	<td class="desc">
	<ul>
		<li>This is the path that you would like to access the site.</li>
		<li>This path is used when someone tries to view the from the wrong path, when this happens, the site can redirect the user to the right place.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_html_root($result, $variable)
{
	// check for html root
	?><tr><td class="title">HTML Root</td>
	<td>
	<input type="text" name="HTML_ROOT" value="<?php echo $variable; ?>" />
	</td>
	<td class="desc">
	<ul>
		<li>This is the directory that the site is accessed through.</li>
		<li>This allows the site to run along site another website, in the specified directory.  This is needed so that templates can find the right path to images and styles.</li>
		<li>This path must also end with the HTTP separator /.</li>
		<li>The server reports the DOCUMENT ROOT is <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
	</ul>
	</td></tr>
	<?php
}

function output_test_db_type($result, $variable)
{
	// set up database type
	?><tr><td class="title">Database Type</td>
	<td>
	<select name="DB_TYPE">
	<option value="">&lt;Select One&gt;</option>
	<?php
		foreach($GLOBALS['templates']['vars']['supported_databases'] as $db)
		{
		?><option value="<?php echo $db; ?>" <?php echo ($variable == $db)?'selected="selected"':''; ?>><?php echo $db; ?></option><?php
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
}

function output_test_db_server($result, $variable)
{
	$dberror = $GLOBALS['templates']['vars']['dberror'];
	// set up database server
	?><tr><td class="title <?php echo ($dberror !== false && (strpos($dberror, 'Can\'t connect') !== false || strpos($dberror, 'Connection error') !== false))?'fail':(($dberror !== false && strpos($dberror, 'Access denied') !== false)?'':'warn'); ?>">Database Server</td>
	<td>
	<input type="text" name="DB_SERVER" value="<?php echo $variable; ?>" />
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
}

function output_test_db_user($result, $variable)
{
	$dberror = $GLOBALS['templates']['vars']['dberror'];
	// set up database username and password
	?><tr><td class="title<?php echo ($dberror !== false && strpos($dberror, 'Access denied') !== false)?' fail':''; ?>">Database User Name</td>
	<td>
	<input type="text" name="DB_USER" value="<?php echo $variable; ?>" />
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
}

function output_test_db_pass($result, $variable)
{
	$dberror = $GLOBALS['templates']['vars']['dberror'];
	?><tr><td class="title<?php echo ($dberror !== false && strpos($dberror, 'Access denied') !== false)?' fail':''; ?>">Database Password</td>
	<td>
	<input type="text" name="DB_PASS" value="<?php echo $variable; ?>" />
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
}

function output_test_db_name($result, $variable)
{
	// set up database name
	?><tr><td class="title">Database Name</td>
	<td>
	<input type="text" name="DB_NAME" value="<?php echo $variable; ?>" />
	</td>
	<td class="desc">
	<ul>
		<li>Please specify the name of the database to use.</li>
		<li>This database will not be created for you, it must be created ahead of time with the proper permission settings.</li>
	</ul>
	</td></tr>
	
	<?php
}

function output_test_drop_tables($result, $variable)
{
	$dberror = $GLOBALS['templates']['vars']['dberror'];
	// drop tables
	if($dberror !== false && $result)
	{
		?><tr><td class="title fail">Tables Already Exist</td>
		<td>
		<input type="submit" name="drop" value="Drop Tables" />
		</td>
		<td class="desc">
		<ul>
			<li>It seems there are already tables in this database with the same name.</li>
			<li>If you drop these tables, it could cause an irreversable loss of database information.</li>
		</ul>
		</td></tr>
		<?
	}
	elseif($dberror == 'tables dropped')
	{
		?><tr><td class="title warn">Tables Dropped</td>
		<td>
		<label>Tables Dropped</label>
		</td>
		<td class="desc">
		<ul>
			<li>The tables have been successfully dropped.  You may now return to the install page.</li>
		</ul>
		</td></tr>
		<?
	}
}

// set up database
/*if($_REQUEST['step'] == 3)
{
	$dberror = false;
	if(isset($_SESSION['dberror']))
	{
		$dberror = stripslashes($_SESSION['dberror']);
	}
	*/

function output_test_enable_modules($result, $variable)
{
	$recommended = $GLOBALS['templates']['vars']['recommended'];
	foreach($GLOBALS['modules'] as $key => $module)
	{
		if(constant($module . '::INTERNAL') == true)
			continue;
		
		$module_en = $GLOBALS['templates']['vars']['request'][strtoupper($module) . '_ENABLE'];
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
				<option value="true" <?php echo ($module_en == true)?'selected="selected"':''; ?>>Enabled <?php echo in_array($module, $recommended)?'(Recommended)':'(Optional)'; ?></option>
				<option value="false" <?php echo ($module_en == false)?'selected="selected"':''; ?>>Disabled</option>
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
}

function output_test_db_check()
{
	?>
	<div id="loading1"><img src="/?plugin=admin_install&install_image=loading" alt="" /> Testing...</div>
	<iframe name="test" id="test" frameborder="0" width="100%" src="<?php echo generate_href('plugin=admin_install&install_step=5.1'); ?>"></iframe>
	<div id="loading2" style="display:none;"><img src="/?plugin=admin_install&install_image=loading" alt="" /> Installing...</div>
	<iframe name="test" id="install" frameborder="0" width="100%" src=""></iframe>
	</script>
	<?php
}

function output_test_site_name($result, $variable)
{
	// set site name
	?><tr><td class="title">Site Name</td>
	<td>
	<input type="text" name="HTML_NAME" value="<?php echo $variable; ?>" />
	</td>
	<td class="desc">
	<ul>
		<li>Some templates can display a name for this media server.  Set this here.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_local_base($result, $variable)
{
	// check for base template
	if($result)
	{
		?><tr><td class="title">Template Base</td>
		<td>
		<input type="text" name="LOCAL_BASE" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
			<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
			<li>The server reports that <?php echo $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . $variable; ?> does, in fact, exist.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Template Base</td>
		<td>
		<input type="text" name="LOCAL_BASE" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that the local basic template files are not where they are expected to be.</li>
			<li>The template base provides a backup/default set of template files. This template supports all possible functionality, in the simplest way.</li>
			<li>Default functionality includes things like printing out an XML file, or an M3U playlist instead of a vieable HTML list of files.</li>
			<li>The server reports that <?php echo $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . $variable; ?> does NOT EXIST.</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_local_default($result, $variable)
{
	// select default template
	?><tr><td class="title">Default Template</td>
	<td>
	<select name="LOCAL_DEFAULT">
	<?php
	foreach($GLOBALS['templates'] as $template)
	{
		?><option value="<?php echo $template; ?>" <?php echo ($variable == $template)?'selected="selected"':''; ?>><?php echo ucwords(basename($template)); ?></option><?php
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
}

function output_test_local_template($result, $variable)
{
	// select local template
	?><tr><td class="title">Local Template</td>
	<td>
	<select name="LOCAL_TEMPLATE">
	<option value="" <?php echo ($variable == '')?'selected="selected"':''; ?>>&lt;Not Set&gt;</option>
	<?php
	foreach($GLOBALS['templates'] as $template)
	{
		?><option value="<?php echo $template; ?>" <?php echo ($variable == $template)?'selected="selected"':''; ?>><?php echo ucwords(basename($template)); ?></option><?php
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
}

function output_test_cron($result, $variable)
{
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
}

function output_test_dir_seek_time($result, $variable)
{
	// set up seek time
	?><tr><td class="title">Directory Seek Time</td>
	<td>
	<select name="DIRECTORY_SEEK_TIME" style="width:100px; display:inline; margin-right:0px;">
	<?php
	for($i = 1; $i < 60; $i++)
	{
		?><option value="<?php echo $i; ?>" <?php echo ($variable == $i || $variable / 60 == $i || $variable / 360 == $i)?'selected="selected"':''; ?>><?php echo $i; ?></option><?php
	}
	?>
	</select><select name="DIRECTORY_SEEK_TIME_MULTIPLIER" style="width:100px; display:inline; margin-right:0px;">
	<option value="1" <?php echo ($variable >= 1 && $variable < 60)?'selected="selected"':''; ?>>Seconds</option>
	<option value="60" <?php echo ($variable / 60 >= 1 && $variable / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
	<option value="360" <?php echo ($variable / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>This script allows you to specify an amount of time to spend on searching directories.  This is so the script only runs for a few minutes every hour or every half hour.</li>
		<li>The directory seek time is the amount of time the script will spend searching directories for changed files.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_file_seek_time($result, $variable)
{
	?><tr><td class="title">File Seek Time</td>
	<td>
	<select name="FILE_SEEK_TIME" style="width:100px; display:inline; margin-right:0px;">
	<?php
	for($i = 1; $i < 60; $i++)
	{
		?><option value="<?php echo $i; ?>" <?php echo ($variable == $i || $variable / 60 == $i || $variable / 360 == $i)?'selected="selected"':''; ?>><?php echo $i; ?></option><?php
	}
	?>
	</select><select name="FILE_SEEK_TIME_MULTIPLIER" style="width:100px; display:inline; margin-right:0px;">
	<option value="1" <?php echo ($variable >= 1 && $variable < 60)?'selected="selected"':''; ?>>Seconds</option>
	<option value="60" <?php echo ($variable / 60 >= 1 && $variable / 60 < 60)?'selected="selected"':''; ?>>Minutes</option>
	<option value="360" <?php echo ($variable / 360 >= 1)?'selected="selected"':''; ?>>Hours</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>The file seek time is the amount of time the script will spend reading file information and putting it in to the database.</li>
	</ul>
	</td></tr>
	<?php
}


function output_test_debug_mode($result, $variable)
{
	// select debug mode
	?><tr><td class="title">Debug Mode</td>
	<td>
	<select name="DEBUG_MODE">
	<option value="true" <?php echo ($variable == true)?'selected="selected"':''; ?>>Turn Debug Mode On</option>
	<option value="false" <?php echo ($variable == false)?'selected="selected"':''; ?>>Do Not Use Debug Mode</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>Debug mode is used by many templates to display debugging options on the page.</li>
		<li>This is usefull for viewing information about file system and database problems and to test if the system is running properly.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_deep_select($result, $variable)
{
	// select recursion option
	?><tr><td class="title">Deep Select</td>
	<td>
	<select name="RECURSIVE_GET">
	<option value="true" <?php echo ($variable == true)?'selected="selected"':''; ?>>Turn Deep Select On</option>
	<option value="false" <?php echo ($variable == false)?'selected="selected"':''; ?>>Do Not Use Deep Select</option>
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
}

function output_test_robots($result, $variable)
{
	// disable robots
	?><tr><td class="title">Robots Handling</td>
	<td>
	<select name="NO_BOTS">
	<option value="true" <?php echo ($variable == true)?'selected="selected"':''; ?>>Disable Robots</option>
	<option value="false" <?php echo ($variable == false)?'selected="selected"':''; ?>>Allow Robots to Scan my Files</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>Some services like Google like to scan websites.  This option will prevent robots from downloading and scanning files on your site.</li>
		<li>This will also enable robots to view a customizable sitemap.php plugin that provides them with the information they deserve.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_tmp_files($result, $variable)
{
	// temporary directory
	if($result)
	{
		?><tr><td class="title">Temporary Files</td>
		<td>
		<input type="text" name="TMP_DIR" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>This directory will be used for uploaded files and storing temporary files like converted files and images.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Temporary Files</td>
		<td>
		<input type="text" name="TMP_DIR" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that this directory does not exist or is not writable.</li>
            <li>Please correct this error by entering a directory path that exists and is writable by the web server</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_user_files($result, $variable)
{
	if($result)
	{
		// user files
		?><tr><td class="title">User Files</td>
		<td>
		<input type="text" name="LOCAL_USERS" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>This directory will be used for uploaded user files.  This will also be included in the directories that are watched by the server.</li>
		</ul>
		</td></tr>
		<?php
	}
	else
	{
		?><tr><td class="title fail">Temporary Files</td>
		<td>
		<input type="text" name="TMP_DIR" value="<?php echo $variable; ?>" />
		</td>
		<td class="desc">
		<ul>
			<li>The system has detected that this directory does not exist or is not writable.</li>
            <li>Please correct this error by entering a directory path that exists and is writable by the web server</li>
		</ul>
		</td></tr>
		<?php
	}
}

function output_test_buffer_size($result, $variable)
{
	// buffer size
	?><tr><td class="title">Buffer Size</td>
	<td>
	<select name="BUFFER_SIZE" style="width:150px; display:inline; margin-right:0px;">
	<?php
	for($i = 0; $i < 10; $i++)
	{
		?><option value="<?php echo pow(2, $i); ?>" <?php echo ($variable / 1024 == pow(2, $i) || $variable / 1048576 == pow(2, $i) || $variable / 1073741824 == pow(2, $i))?'selected="selected"':''; ?>><?php echo pow(2, $i); ?></option><?php
	}
	?>
	</select><select name="BUFFER_SIZE_MULTIPLIER" style="width:50px; display:inline; margin-right:0px;">
		<option value="1024" <?php echo ($variable / 1024 >= 1 && $variable / 1024 < 1048576)?'selected="selected"':''; ?>>KB</option>
		<option value="1048576" <?php echo ($variable / 1048576 >= 1 && $variable / 1048576 < 1073741824)?'selected="selected"':''; ?>>MB</option>
		<option value="1073741824" <?php echo ($variable / 1073741824 >= 1)?'selected="selected"':''; ?>>GB</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>Some plugins and modules require open file streams of a specific size.  This allows you to set what size these streams should try to remain below.</li>
	</ul>
	</td></tr>
	<?php
}

function output_test_aliasing($result, $variable)
{
	// set up aliasing
	?><tr><td class="title">Aliasing</td>
	<td>
	<select name="USE_ALIAS">
	<option value="true" <?php echo ($variable == true)?'selected="selected"':''; ?>>Use Aliased Paths</option>
	<option value="false" <?php echo ($variable == false)?'selected="selected"':''; ?>>Display Actual Path to Users</option>
	</select>
	</td>
	<td class="desc">
	<ul>
		<li>Path aliasing is used to disguise the location of files on your file system.  Aliases can be set up to convert a path such as /home/share/ to /Shared/.</li>
	</ul>
	</td></tr>
	<?php
}

function output_pass_changed($result)
{
	if($result)
	{
		?>
		<table border="0" cellpadding="0" cellspacing="0">
		<?php
		
		// save config
		?><tr><td class="title warn">New Secret</td>
		<td>
		<label>Default Password Updated</label>
		</td>
		<td class="desc">
		<ul>
			<li>WARNING: The secret key for the database changes every time this script is loaded.</li>
			<li>The administrator password has been changed back to the default.</li>
		</ul>
		</td></tr>
		</table>
		<?php
	}
	else
	{
		?>
		<table border="0" cellpadding="0" cellspacing="0">
		<?php
		
		// save config
		?><tr><td class="title warn">Admin Password</td>
		<td>
		<label>Default Password Updated</label>
		</td>
		<td class="desc">
		<ul>
			<li>WARNING: The Admin password has been reset to the default.  You should change this password to something more secure as soon as possible.</li>
		</ul>
		</td></tr>
		</table>
		<?php
	}
}

function output_test_save($result, $variable)
{
	// save config
	$config = $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . 'include' . DIRECTORY_SEPARATOR . 'settings.php';
	
	// check for write permissions on the settings files
	$fh = @fopen($config, 'w');
	
	// run database::installFirstTimeUsers with new secret key
	include_once 'PEAR.php';
	include_once $GLOBALS['templates']['vars']['request']['LOCAL_ROOT'] . 'include' . DIRECTORY_SEPARATOR . 'database.php';
	
	if(isset($_SESSION)) session_write_close();
	
	$DB_CONNECT = $GLOBALS['templates']['vars']['request']['DB_TYPE'] . '://' . 
		$GLOBALS['templates']['vars']['request']['DB_USER'] . ':' . 
		$GLOBALS['templates']['vars']['request']['DB_PASS'] . '@' . 
		$GLOBALS['templates']['vars']['request']['DB_SERVER'] . '/' . 
		$GLOBALS['templates']['vars']['request']['DB_NAME']; 
	$DATABASE = new database($DB_CONNECT);
	
	$DB_SECRET = md5(microtime());
	
	$pass_changed = $DATABASE->installFirstTimeUsers($DB_SECRET);
	
	// add slashes to all the variables used below
	foreach($GLOBALS['templates']['vars']['post'] as $key)
	{
		$$key = addslashes($GLOBALS['templates']['vars']['request'][$key]);
	}
	
	$NO_TEMPLATE = ($LOCAL_TEMPLATE == '')?'#':'';
	
	$settings = <<<EOF

// the most basic settings for getting the system running
// all other settings are stored in the appropriate classes that handle each section

// database connection constants
define('USE_DATABASE', 	                  true); // set to false to make modules load information about every file on the fly

define('DB_CONNECT',			'$DB_CONNECT');

// this secrect key is prepended to all passwords before encryption
//   this is so if someone access the database, they still must know the secret key before they can get the passwords
//   this key should be very random
define('DB_SECRET', 		'$DB_SECRET');

// site constants these are used throughout the entire system
define('LOCAL_ROOT',                '$LOCAL_ROOT');

// where to put the user directory for storing user uploaded files
//   this directory must be writtable by the web server
define('LOCAL_USERS', 							'$LOCAL_USERS');

// this template folder includes all the files in pages that are accessible
//  this includes the types of list outputs so other templates don't have to reimplement them to use them
define('LOCAL_BASE',            				        '$LOCAL_BASE');

// this is the local filesystem path to the default template, this path should not be used in web pages, instead use HTML_TEMPLATE
//  this is the template that is used when a template is not specified
define('LOCAL_DEFAULT',            				        '$LOCAL_DEFAULT');

// this is the optional template that will be used
// if this is defined here, the user will not be given an option to choose a template
$NO_TEMPLATE define('LOCAL_TEMPLATE',            					 '$LOCAL_TEMPLATE');

// this is the path used by html pages to refer back to the website domain, HTML_ROOT is usually appended to this
define('HTML_DOMAIN',            			    '$HTML_DOMAIN');

// this is the root directory of the site, this is needed if the site is not running on it's own domain
// this is so HTML pages can refer to the root of the site, without making the brower validate the entire domain, this saves time loading pages
// a slash / is always preppended to this when the HTML_DOMAIN is not preceeding this
define('HTML_ROOT',                                        '$HTML_ROOT');

// extra constants
// site name is used through various templates, generally a subtitle is appended to this
define('HTML_NAME',			                                   '$HTML_NAME'); // name for the website

// commands, these are used for converting media throughout the site
// multiple definitions can be set with the extension in all caps for different file types
//  for example CONVERT_JPG, and CONVERT_ARGS_JPG can be used for converting any file with the JPG extension
//  if %IF is not found in the string the plugins and modules will assume STDIN is used, additional functionality will be enabled in this case
//  same for %0F, STDOUT will be used and this will enable much faster response times

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%FM - Format to output
%TH - Thumbnail height
%TW - Thumbnail width
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the convert.php plugin
define('CONVERT', 				   '$CONVERT_PATH'); // image magick's convert program
define('CONVERT_ARGS', 			   '"%IF" -resize "%TWx%TH" %FM:-'); // image magick's convert program

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%VC - Video Codec to be used in the conversion
%AC - Audio Codec
%VB - Video Bitrate
%AB - Audio Bitrate
%SR - Sample Rate
%SR - Scale
%CH - Number of Channels
%MX - Muxer to use for encapsulating the streams
%TO - Time Offset for resumable listening and moving time position
%FS - Frames per Second
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the encode.php plugin
// remember ffmpeg uses generally the same codec names as the default vlc, however custom commands may be needed to convert to each type
define('ENCODE', 				       '$ENCODE_PATH'); // a program that can convert video and audio streams
define('ENCODE_ARGS',                  '-I dummy - --start-time=%TO :sout=\'#transcode{vcodec=%VC,acodec=%AC,vb=%VB,ab=%AB,samplerate=%SR,channels=%CH,audio-sync,scale=%SC,fps=%FS}:std{mux=%MX,access=file,dst=-}\' vlc://quit'); // a program that can convert video and audio streams

// the arguments to use with archive are as follows
/*
%IF - Input file
%OF - Output file if necessary
*/
define('ARCHIVE_RAR',                                'C:\Program Files\WinRAR\Rar.exe'); // a program that can convert video and audio streams
define('ARCHIVE_ARGS_RAR',                           ' p %IF'); // a program that can convert video and audio streams

// finally some general options, just used to avoid hardcoding stuff

// debug mode is used by many templates to display debugging options on the page
define('DEBUG_MODE', 							$DEBUG_MODE);

// when a user tries to access a directory listing, this will load missing directories on the fly
//   this is good when there are few files in a directory, but the site hasn't scanned them all
//   don't use this when there are many complex files and the site has loaded thousands already
define('RECURSIVE_GET', 				$RECURSIVE_GET);

// this will redirect a google bot to the sitemap.php plugin or remove request options like search
//   this is recommended because GoogleBots usually look for the wrong information and this will slow down the site A LOT
define('NO_BOTS', 							$NO_BOTS);

// max amount to output when accessing a file
define('BUFFER_SIZE', 	                         $BUFFER_SIZE);

// a temporary directory to use for creating thumbnails
define('TMP_DIR', 	                             '$TMP_DIR');

// set to true in order to use aliased paths for output of Filepath
// USE_DATABASE must be enabled in order for this to be used!
define('USE_ALIAS', 							 $USE_ALIAS);

// how long to search for directories that have changed
define('DIRECTORY_SEEK_TIME',		$DIRECTORY_SEEK_TIME);

// how long to search for changed files or add new files
define('FILE_SEEK_TIME', 		   $FILE_SEEK_TIME);

// how long to clean up files
define('CLEAN_UP_BUFFER_TIME',				45);

// how many times should we be able to run the cron script before a cleanup is needed?
//  cleanup will also be fired when a directory change is detected, this may not always be accurate
define('CLEAN_UP_THREASHOLD', 				10);

ini_set('include_path', '.:/usr/share/php:/usr/share/pear:' . LOCAL_ROOT . 'include/');

// comment-out-able
ini_set('error_reporting', E_ALL);

EOF;

	output_pass_changed($pass_changed);
	
	if($result)
	{
		fwrite($fh, $settings);
		fclose($fh);
		
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
		<div style="width:100%; height:400px; overflow:scroll; border:1px solid #999;">
		<code>
		<?php
			
		include_once 'Text/Highlighter.php';
		$highlighter = Text_Highlighter::factory('php');
		$settings = $highlighter->highlight('<?php
		' . $settings . '
		?>');
		
		print $settings;
		
		?>
		</code>
		</div>
		<?php
	}

}


function output_buttons($install_step)
{
	?>
    </table>
    <br />
    <br />
    <br />
    <?php
	if($install_step == 9)
	{
		?>
		<input type="submit" name="install_next" value="View Site!" class="button" style="float:right;" />
		<?php
	}
	elseif($install_step == 5)
	{
		?>
		<input type="submit" name="install_next" value="Save and Continue" style="float:right;" />
		<input type="submit" name="install_save" value="Try Again" class="button" style="float:right;" />
		<?php
    }
	else
	{
		?>
		<input type="submit" name="install_reset" value="Reset to Defaults" class="button" />
		<input type="submit" name="install_next" value="Save and Continue" style="float:right;" />
		<input type="submit" name="install_save" value="Save" class="button" style="float:right;" />
		<?php
	}

	?>
	</form>
    <?php
}
