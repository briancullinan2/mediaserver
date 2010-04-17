<?php

function register_plain_m3u()
{
	return array(
		'name' => 'M3U Playlist',
		'file' => __FILE__,
		'encoding' => 'TEXT'
	);
}

function theme_plain_m3u()
{
	if(!isset($GLOBALS['templates']['vars']['extra']))
	{
		if(!isset($GLOBALS['templates']['vars']['selected']))
		{
			header('Location: ' . url('list=m3u&plugin=list&cat=' . $_REQUEST['cat']));
		}
		else
		{
			$ids = implode(',', $GLOBALS['templates']['vars']['selected']);
			?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print HTML_NAME; ?>: M3U List</title>
</head>
<body>
Note: All non-media types will be filtered out using this list type.<br />
Select your audio/video format:<br />
<a href="<?php print url('plugin=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&selected=' . $ids . '&extra=mp3&filename=Files.m3u'); ?>">mp4</a>
: <a href="<?php print url('plugin=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&selected=' . $ids . '&extra=mpg&filename=Files.m3u'); ?>">mpg/mp3</a>
: <a href="<?php print url('plugin=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&selected=' . $ids . '&extra=wm&filename=Files.m3u'); ?>">wmv/wma</a>
<br />
Some files that will be listed: <br />
<?php
$count = 0;
foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
{
	$type = split('/', $file['Filemime']);
	if($type[0] == 'audio' || $type[0] == 'video')
    {
    	print $file['Filename'] . '<br />';
        $count++;
    }
    if($count == 10)
    	break;
}
?>
</body>
</html>
<?php

			return;
		}
	}
	
	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: attachment; filename="' . (isset($_REQUEST['filename'])?$_REQUEST['filename']:constant($_REQUEST['cat'] . '::NAME') . '.m3u"')); 

	if($GLOBALS['templates']['vars']['extra'] == 'mp4')
	{
		$audio = 'mp4a';
		$video = 'mp4';
	}
	elseif($GLOBALS['templates']['vars']['extra'] == 'wm')
	{
		$audio = 'wma';
		$video = 'wmv';
	}
	elseif($GLOBALS['templates']['vars']['extra'] == 'mpg')
	{
		$audio = 'mp3';
		$video = 'mpg';
	}
	
	// display m3u file
	
	?>
	#EXTM3U
	<?php
	foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
	{
		$length = isset($file['Length'])?$file['Length']:0;
		$title = (isset($file['Artist']) && isset($file['Title']))?($file['Artist'] . ' - ' . $file['Title']):basename($file['Filepath']);
		if(handles($file['Filepath'], 'audio'))
		{
			?>#EXTINF:<?php print $length; ?>,<?php print $title; ?>
			<?php print url('plugin=encode&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&encode=' . $audio . '&filename=' . basename($file['Filepath']), true, true);
		}
		elseif(handles($file['Filepath'], 'video'))
		{
			?>#EXTINF:<?php print $length; ?>,<?php print $title; ?>
			<?php print url('plugin=encode&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&encode=' . $video . '&filename=' . basename($file['Filepath']), true, true);
		}
	}
}

