<?php

// handles the watch tables

require_once '../include/common.php';

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

$watched = $mysql->get('watch', array('SELECT' => array('id','Filepath')));

if( isset($_REQUEST['add']) )
{
	$addpath = $_REQUEST['addpath'];
	// make sure addpath doesn't already exist in the list and is not a symbolic link
	if( file_exists($addpath) )
	{
		if( is_dir($addpath) )
		{
			$addpath = realpath($addpath);
			// add ending backslash
			if( substr($addpath, strlen($addpath)-1) != '/' ) $addpath .= '/';
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
				$mysql->set('watch', array('Filepath' => $addpath));
				
				// and reget the full list
				$watched = $mysql->get('watch', array('SELECT' => array('id','Filepath')));
				
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
	$watched = $mysql->get('watch', array('SELECT' => array('id','Filepath')));

	// clear post
	unset($_REQUEST['addpath']);
}

?>

This is a list of folders on the server to watch for media files:<br />
<?
if( $error != '' )
{
?>
	<span style="color:#990000; font-weight:bold;"><?=$error?></span><br />
<?
}
?>

<form action="" method="post">
	<select name="watch" size="10">
	
	<?
		foreach($watched as $i => $watch)
		{
		?>
			<option value="<?=$watch['id']?>"><?=$watch['Filepath']?></option>
		<?
		}
	?>
	</select>
	<br />
	<input type="submit" value="Remove" name="remove" />
	<br />
	<input type="text" name="addpath" value="<?=(isset($_REQUEST['addpath'])?$_REQUEST['addpath']:"")?>" />
	<input type="submit" value="Add" name="add" />
	<br />
</form>
