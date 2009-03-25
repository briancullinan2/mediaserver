<?php

// handles the watch tables

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( loggedIn() )
{
}
else
{
	// redirect to login page
	header('Location: login.php?return=' . $_SERVER['REQUEST_URI']);
	
	exit();
}

$error = '';

if( isset($_REQUEST['add']) && isset($_REQUEST['addpath']) && $_REQUEST['addpath'] != '' )
{
	if($_REQUEST['addpath'][0] != '!' && $_REQUEST['addpath'][0] != '^')
		$_REQUEST['addpath'] = '^' . $_REQUEST['addpath'];
	if(db_watch::handles($_REQUEST['addpath']))
	{
			// pass file to module
			db_watch::handle($_REQUEST['addpath']);
			
			unset($_REQUEST['addpath']);
	}
	else
	{
		$error = 'Invalid path.';
	}
}
elseif( isset($_REQUEST['remove']) && is_numeric($_REQUEST['watch']) )
{
	$GLOBALS['database']->query(array('DELETE' => db_watch::DATABASE, 'WHERE' => 'id=' . $_REQUEST['watch']));

	// clear post
	unset($_REQUEST['addpath']);
}

// reget the watched and ignored because they may have changed
$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count, $error);
$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count, $error);

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo HTML_NAME?>: Watch Editor</title>
</head>
<body>

This is a list of folders on the server to watch for media files:<br />
<?php
if( $error != '' )
{
?>
	<span style="color:#990000; font-weight:bold;"><?php echo $error?></span><br />
<?php
}
?>
	<form action="" method="post">
		<select name="watch" size="10">
		
		<?php
			foreach($GLOBALS['ignored'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">ignore: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
			foreach($GLOBALS['watched'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">watch: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
		?>
		</select>
		<br />
		<input type="submit" value="Remove" name="remove" />
	</form>
	<form action="" method="post">
		<input type="text" name="addpath" size="50" value="<?php echo (isset($_REQUEST['addpath'])?$_REQUEST['addpath']:"")?>" />
		<input type="submit" value="Add" name="add" />
		<br />
	</form>
</body>
</html>
