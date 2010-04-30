<?php

function register_live_install()
{
	return array(
		'name' => 'Live Install',
	);
}

function theme_live_install()
{
	theme('head');
	
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
											<li><img src="/?module=admin_install&install_image=carat" class="crumbsep" alt="&gt;" /></li>
											<li><a href="<?php echo url('module=admin_install&install_step=' . $i); ?>">Step <?php echo $i; ?></a></li>
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
		$count = 11 + count($GLOBALS['handlers']);
		break;
		case 7:
		$count = 11 + count($GLOBALS['handlers']) + 4;
		break;
		case 8:
		$count = 11 + count($GLOBALS['handlers']) + 6;
		break;
		case 9:
		$count = 11 + count($GLOBALS['handlers']) + 13;
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
            <p>Before the site can't function properly, we must define some paths for templates and modules to use.</p>
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
            <h2>Select Handlers</h2>
            <p>Below is a list of available handlers.  Handlers can be added or removed at any time, but with large file-structures, inserting new handlers could take a very long time.  Therefore, all handlers are enabled by default, with the recommended handlers marked as such.</p>
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
	
	?><table border="0" cellpadding="0" cellspacing="0" class="install"><?php
}

function output_tests($install_step)
{
	$output = array();
	switch($install_step)
	{
		case 1:
		$output = array(
		);
		break;
		case 2:
		$output = array(
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
	<form action="<?php echo url('module=admin_install&install_step=3'); ?>" method="post" target="_top">
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
    <form action="<?php echo url('module=admin_install&install_step=3'); ?>" method="post" target="_top">
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
<body onLoad="top.document.getElementById('loading1').style.display = 'none'; top.document.getElementById('test').style.height=document.getElementById('testtable').clientHeight+'px'; top.document.getElementById('loading2').style.display='inline'; top.document.getElementById('install').src='<?php echo url('module=admin_install&install_step=5.2'); ?>'">
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

function output_test_db_check()
{
	?>
	<div id="loading1"><img src="/?module=admin_install&install_image=loading" alt="" /> Testing...</div>
	<iframe name="test" id="test" frameborder="0" width="100%" src="<?php echo url('module=admin_install&install_step=5.1'); ?>"></iframe>
	<div id="loading2" style="display:none;"><img src="/?module=admin_install&install_image=loading" alt="" /> Installing...</div>
	<iframe name="test" id="install" frameborder="0" width="100%" src=""></iframe>
	</script>
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
define('USE_DATABASE', 	                  true); // set to false to make handlers load information about every file on the fly

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
//  if %IF is not found in the string the modules and handlers will assume STDIN is used, additional functionality will be enabled in this case
//  same for %0F, STDOUT will be used and this will enable much faster response times

// the arguments to use with encode are as follows
/*
%IF - Input file, the filename that will be inserted for transcoding
%FM - Format to output
%TH - Thumbnail height
%TW - Thumbnail width
%OF - Output file if necissary
*/
// More options can be added but you will have to do some scripting in the convert.php module
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
// More options can be added but you will have to do some scripting in the encode.php module
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

// this will redirect a google bot to the sitemap.php module or remove request options like search
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
