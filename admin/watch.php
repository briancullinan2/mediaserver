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

$watched = $mysql->get(array('TABLE' => 'watch', 'SELECT' => array('id','Filepath')));

if( isset($_REQUEST['add']) )
{
	$addpath = $_REQUEST['addpath'];
	$exclude = false;
	if($addpath[0] == '!')
	{
		$addpath = substr($addpath, 1);
		$exclude = true;
	}
	// make sure addpath doesn't already exist in the list and is not a symbolic link
	if( file_exists($addpath) )
	{
		if( is_dir($addpath) )
		{
			$addpath = realpath($addpath);
			// add ending backslash
			if( substr($addpath, strlen($addpath)-1) != DIRECTORY_SEPARATOR ) $addpath .= DIRECTORY_SEPARATOR;
			$dont_add = false;
			foreach($watched as $i => $watch)
			{
				if( $watch['Filepath'] == $addpath )
				{
					$error = 'Path already exists in list.';
					$dont_add = true;
					break;
				}
			}
			if($dont_add == false)
			{
				// finally add the path to the database
				$mysql->set('watch', array('Filepath' => ($exclude?'!':'') . addslashes($addpath)));
				
				// and reget the full list
				$watched = $mysql->get(array('TABLE' => 'watch', 'SELECT' => array('id','Filepath')));
				
				// clear post
				unset($_REQUEST['addpath']);
			}
		}
		else
		{
			$error = 'Path must be to a directory.';
		}
	}
	else
	{
		$error = 'Path doesn\'t exist on server.';
	}
}
elseif( isset($_REQUEST['remove']) )
{
	$mysql->set('watch', NULL, array('id' => $_REQUEST['watch']));
	
	// and reget the full list
	$watched = $mysql->get(array('TABLE' => 'watch', 'SELECT' => array('id','Filepath')));

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
