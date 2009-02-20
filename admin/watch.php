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

// get the current list of watches
$mysql = new sql(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$watched = db_watch::get($mysql, array(), $count, $error);

if( isset($_REQUEST['add']) )
{
	if($_REQUEST['addpath'][0] != '!' && $_REQUEST['addpath'][0] != '^')
		$_REQUEST['addpath'] = '^' . $_REQUEST['addpath'];
	if(db_watch::handles($_REQUEST['addpath']))
	{
			// pass file to module
			db_watch::handle($mysql, $_REQUEST['addpath']);
			
			// and reget the full list
			$watched = db_watch::get($mysql, array(), $count, $error);
			
			unset($_REQUEST['addpath']);
	}
	else
	{
		$error = 'Invalid path.';
	}
}
elseif( isset($_REQUEST['remove']) && is_numeric($_REQUEST['watch']) )
{
	$mysql->query(array('DELETE' => 'watch', 'WHERE' => 'id=' . $_REQUEST['watch']));
	
	// and reget the full list
	$watched = db_watch::get($mysql, array(), $count, $error);

	// clear post
	unset($_REQUEST['addpath']);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo HTML_NAME?></title>
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
			foreach($watched as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>"><?php echo $watch['Filepath']; ?></option>
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
