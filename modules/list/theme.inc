<?php

function theme__list()
{
	theme('header');
	
	theme('list_block');
	
	theme('footer');
}


function theme__list_block()
{
	?>
    <div id="type">
        Get the list:
        <br />
        <form action="<?php print url('list'); ?>" method="get">
            <input type="hidden" name="cat" value="<?php print $GLOBALS['output']['html']['handler']; ?>" />
            <input type="hidden" name="module" value="list" />
            Type <select name="list">
            	<?php
				foreach($GLOBALS['lists'] as $type => $list)
				{
					?><option value="<?php print $type; ?>"><?php print $list['name']; ?></option><?php
				}
				?>
            </select>
            <input type="submit" value="Go" />
        </form>
    </div>
	<?php
}

function theme__m3u()
{
	if(!isset($GLOBALS['output']['extra']))
	{
		header('Content-Type: text/html');
		if(!isset($GLOBALS['output']['selected']))
		{
			goto('list/m3u/' . $_REQUEST['cat']);
		}
		else
		{
			$ids = implode(',', $GLOBALS['output']['selected']);
			
			theme('header');
			
			?>
			Note: All non-media types will be filtered out using this list type.<br />
			Select your audio/video format:<br />
			<a href="<?php print url('list/m3u/' . $GLOBALS['output']['cat'] . '/' . $ids . '/mp3/Files.m3u'); ?>">mp4</a>
			: <a href="<?php print url('list/m3u/' . $GLOBALS['output']['cat'] . '/' . $ids . '/mpg/Files.m3u'); ?>">mpg/mp3</a>
			: <a href="<?php print url('list/m3u/' . $GLOBALS['output']['cat'] . '/' . $ids . '/wm/Files.m3u'); ?>">wmv/wma</a>
			<br />
			Some files that will be listed: <br />
			<?php
			$count = 0;
			foreach($GLOBALS['output']['files'] as $i => $file)
			{
				$type = explode('/', $file['Filemime']);
				if($type[0] == 'audio' || $type[0] == 'video')
				{
					print $file['Filename'] . '<br />';
					$count++;
				}
				if($count == 10)
					break;
			}
			
			theme('footer');

			return;
		}
	}

	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: attachment; filename="' . (isset($GLOBALS['output']['filename'])?$GLOBALS['output']['filename']:get_module($GLOBALS['output']['handler'], 'name') . '.m3u"')); 

	if($GLOBALS['output']['extra'] == 'mp4')
	{
		$audio = 'mp4a';
		$video = 'mp4';
	}
	elseif($GLOBALS['output']['extra'] == 'wm')
	{
		$audio = 'wma';
		$video = 'wmv';
	}
	elseif($GLOBALS['output']['extra'] == 'mpg')
	{
		$audio = 'mp3';
		$video = 'mpg';
	}
	
	// display m3u file
	
?>
#EXTM3U
<?php
	foreach($GLOBALS['output']['files'] as $i => $file)
	{
		$length = isset($file['Length'])?$file['Length']:0;
		$title = (isset($file['Artist']) && isset($file['Title']))?($file['Artist'] . ' - ' . $file['Title']):basename($file['Filepath']);
		if(handles($file['Filepath'], 'audio'))
		{
?>#EXTINF:<?php print $length; ?>,<?php print $title . "\r\n"; ?>
<?php print url('encode/' . $GLOBALS['output']['cat'] . '/' . $file['id'] . '/' . $audio . '/' . basename($file['Filepath']), true, true) . "\r\n";
		}
		elseif(handles($file['Filepath'], 'video'))
		{
?>#EXTINF:<?php print $length; ?>,<?php print $title . "\r\n"; ?>
<?php print url('encode/' . $GLOBALS['output']['cat'] . '/' . $file['id'] . '/' . $video . '/' . basename($file['Filepath']), true, true) . "\r\n";
		}
	}
}

function theme__rss()
{
	print '<?xml version="1.0" encoding="utf-8"?>';
	?>
	<rss version="2.0">
		<channel>
			<title><?php print setting('html_name'); ?> - <?php print $GLOBALS['output']['cat']; ?></title>
			<link><?php print url('', false, true); ?></link>
			<description></description>
            <?php
			foreach($GLOBALS['output']['files'] as $i => $file)
			{
				?>
				<item>
					<title><?php print basename($file['Filepath']); ?></title>
					<link><?php print url('file/' . $GLOBALS['output']['cat'] . '/' . $file['id'] . '/' . basename($file['Filepath']), false, true); ?></link>
					<description></description>
				</item>
                <?php
			}
			?>
		</channel>
	</rss>
    <?php
}

function theme__wpl()
{
	?>
	<smil>
		<head>
			<meta name="Generator" content="Microsoft Windows Media Player -- 11.0.5721.5230"/>
			<meta name="ContentPartnerListID"/>
			<meta name="ContentPartnerNameType"/>
			<meta name="ContentPartnerName"/>
			<meta name="Subtitle"/>
			<author/>
			<title><?php print setting('html_name');?> - <?php print $GLOBALS['module']; ?></title>
		</head>
		<body>
			<seq>
				<?php
				foreach($files as $i => $file)
				{
					?><media src="<?php print url('file/' . $GLOBALS['output']['cat'] . '/' . $file['id'] . '/' . urlencode($file['Filename']), false, true); ?>" /><?php
				}
				?>
			</seq>
		</body>
	</smil>
	<?php
}


function theme__xml()
{
	$ext_icons = array();
	$ext_icons['FOLDER'] = url('template/live/images/filetypes/folder_96x96.png');
	$ext_icons['FILE'] = url('template/live/images/filetypes/file_96x96.png');
	
	$type_icons = array();
	$type_icons['audio'] = url('template/live/images/filetypes/music_96x96.png');

	print '<?xml version="1.0" encoding="utf-8"?>
	
	';
	
	?><request><?php
	
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><success>false</success>
		<error><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			print $error . "\n";
		}
		?><error><?php
	}
	?><count><?php print $GLOBALS['output']['html']['total_count']; ?></count><?php
	foreach($GLOBALS['output']['html']['files'] as $i => $file)
	{
		?>
		<file>
			<index><?php print $GLOBALS['output']['start'] + $i; ?></index>
			<id><?php print $file['id']; ?></id>
			<name><?php print $file['Filename']; ?></name>
			<text><?php print $file['Filename']; ?></text>
			<?php
			$type_arr = explode('/', $file['Filemime']);
			$type = $type_arr[0];
			?><icon><?php print isset($ext_icons[$file['Filetype']])?$ext_icons[$file['Filetype']]:(isset($type_icons[$type])?$type_icons[$type]:$ext_icons['FILE']); ?></icon>
			<ext><?php print $file['Filetype']; ?></ext>
			<tip><?php
			foreach($GLOBALS['output']['columns'] as $j => $column)
			{
				if(isset($file[$column]))
				{
					print $column . ': ' . $file[$column] . '&lt;br /&gt;';
				}
			}
			?></tip>
			<path><?php print $file['Filepath']; ?></path>
			<link><?php print url('file/' . $GLOBALS['output']['cat'] . '/' . $file['id'] . '/' . urlencode($file['Filename']), false, true); ?></link>
			<short><?php print htmlspecialchars(substr($GLOBALS['output']['files'][$i]['Filename'], 0, 13)); ?>...</short>
			<?php
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'archive'))
			{
				?><cat>archive</cat><?php
			}
			elseif(handles($GLOBALS['output']['files'][$i]['Filepath'], 'diskimage'))
			{
				?><cat>diskimage</cat><?php
			}
			else
			{
				?><cat><?php print $GLOBALS['output']['html']['cat']; ?></cat><?php
			}
			
			foreach($GLOBALS['output']['columns'] as $i => $column)
			{
				?><info-<?php print $column; ?>><?php print isset($file[$column])?$file[$column]:''; ?></info-><?php print $column; ?>><?php
			}
			?>
		</file>
		<?php
	}
	
	?></request><?php
}
