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
