<?php

function register_plain_watch()
{
	return array(
		'name' => 'Plain Watch'
	);
}

function theme_plain_watch()
{
	?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<title><?php echo setting('html_name')?>: Watch Editor</title>
</head>
<body>

This is a list of folders on the server to watch for media files:<br />
<?php
if( count($GLOBALS['user_errors']) > 0 )
{
?>
	<span style="color:#990000; font-weight:bold;"><?php foreach($GLOBALS['templates']['vars']['user_errors'] as $i => $error) { echo $error->message . '<br />'; } ?></span><br />
<?php
}
?>
	<form action="" method="post">
		<select name="wremove" size="10">
		
		<?php
			foreach($GLOBALS['templates']['vars']['ignored'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">ignore: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
			foreach($GLOBALS['templates']['vars']['watched'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">watch: <?php echo $watch['Filepath']; ?></option>
			<?php
			}
		?>
		</select>
		<br />
		<input type="submit" value="Remove" />
	</form>
	<form action="" method="post">
		<input type="text" name="waddpath" size="50" value="<?php echo (isset($GLOBALS['templates']['vars']['waddpath'])?$GLOBALS['templates']['vars']['waddpath']:"")?>" />
		<input type="submit" value="Add" />
		<br />
	</form>
</body>
</html>
<?php

}
