<?php
header('Content-Type: text/xml');

echo '<?xml version="1.0" encoding="utf-8"?>';
?>

<request>
<?php
if(isset($error) && $error != '')
{
	?>
	<success>false</success>
	<error><?php echo $error; ?></error>
	<?php
}
else
{
	// get number of songs in first album
	$result = $GLOBALS['database']->query(array('SELECT' => 'audio', 'COLUMNS' => 'count(*)', 'WHERE' => 'Album = "' . $files[0]['Album'] . '"'));
	
	// calculate starting offset
	$album_count = 0;
	$current_album = strtolower($files[0]['Album']);
	
	// loop through first album and figure out how many files are being displayed from the album
	$tmp_count = 0;
	foreach($files as $index => $file)
	{
		if($current_album != strtolower($file['Album']))
		{
			$album_count = $result[0]['count(*)'] - $tmp_count;
		}
		else
		{
			$tmp_count++;
		}
	}
	
	?>
	<count><?php echo $total_count; ?></count><?php
	foreach($files as $index => $file)
	{
		if($current_album != strtolower($file['Album']))
		{
			$album_count = 1;
			$current_album = strtolower($file['Album']);
		}
		else
		{
			$album_count++;
		}
		?>

	<file>
		<index><?php echo $index + $_REQUEST['start']; ?></index>
		<id><?php echo $file['id']; ?></id>
		<?php
		// print out part of the album
		if($album_count == 1)
		{
			?><class>album</class><?php
		}
		elseif($album_count == 2)
		{
			?><class>artist</class><?php
		}
		elseif($album_count == 3)
		{
			?><class>genre</class><?php
		}
		elseif($album_count == 4)
		{
			?><class>year</class><?php
		}
		elseif($album_count == 5)
		{
			?><class>last</class><?php
		}
		else
		{
			?><class>none</class><?php
		}
		
		?>
		<name><?php echo htmlspecialchars(utf8_encode($file['Filename'])); ?></name>
		<icon><?php echo HTML_DOMAIN . HTML_ROOT . HTML_PLUGINS . 'convert.php?file=' . htmlspecialchars(urlencode(utf8_encode(dirname($file['Filepath']) . '/folder.jpg'))) . '&amp;convert=jpg&amp;%TH=100&amp;%TW=100'; ?></icon>
		<path><?php echo htmlspecialchars(utf8_encode($file['Filepath'])); ?></path>
		<link><?php echo HTML_DOMAIN . HTML_ROOT . HTML_PLUGINS . 'file.php/' . $_REQUEST['cat'] . '/' . $file['id'] . '/' . htmlspecialchars(urlencode(utf8_encode(basename($file['Filepath'])))); ?></link>
		<short><?php echo htmlspecialchars(utf8_encode(substr($file['Filename'], 0, 13))) . '...'; ?></short>
		<?php
			if(handles($file['Filepath'], 'archive'))
			{
				?><cat><?php echo (USE_DATABASE)?'db_':'fs_'; ?>archive</cat><?php
			}
			elseif(handles($file['Filepath'], 'diskimage'))
			{
				?><cat><?php echo (USE_DATABASE)?'db_':'fs_'; ?>diskimage</cat><?php
			}
			else
			{
				?><cat><?php echo (USE_DATABASE)?'db_':'fs_'; ?>file</cat><?php
			}
			
			foreach($columns as $i => $column)
			{
				if($column == 'Title' && (!isset($file['Title']) || $file['Title'] == ''))
				{
					?>

		<info-Title><?php echo htmlspecialchars(utf8_encode(basename($file['Filepath']))); ?></info-Title><?php
				}
				else
				{
					?>

		<info-<?php echo $column; ?>><?php echo isset($file[$column])?htmlspecialchars(utf8_encode($file[$column])):''; ?></info-<?php echo $column; ?>><?php
				}
			}
		?>

	</file><?php
	}
}
?>

</request>
