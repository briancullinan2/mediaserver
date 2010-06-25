<?php
// query the database based on search stored in session

/**
 * Implementation of register
 * @ingroup register
 */
function register_list()
{
	return array(
		'name' => 'Playlist',
		'description' => 'Allow users to download different types of lists of files they have selected, such as RSS, XML, and M3U.',
		'privilage' => 1,
		'path' => __FILE__,
		'template' => false,
		'depends on' => array('template'),
		'template' => false,
		'lists' => array(
			'rss' => array(
				'name' => 'RSS Feed',
				'file' => __FILE__,
				'encoding' => 'XML',
			),
			'xml' => array(
				'name' => 'XML List',
				'file' => __FILE__,
				'encoding' => 'XML',
			),
			'm3u' => array(
				'name' => 'M3U Playlist',
				'file' => __FILE__,
				'encoding' => 'TEXT',
			),
			'wpl' => array(
				'name' => 'Windows Media Playlist',
				'file' => __FILE__,
				'encoding' => 'XML',
			),
		),
	);
}

/**
 * Set up a list of different types of lists that can be outputted from any theme at any time
 * @ingroup setup
 */
function setup_list()
{	
	// get all the possible types for a list from templates directory
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['lists']))
		{
			foreach($template['lists'] as $i => $list)
			{
				if(file_exists(setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php'))
				{
					include_once setting('local_root') . 'templates' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $list . '.php';
					
					if(isset($GLOBALS['lists'][$list]))
						PEAR::raiseError('List already defined!', E_DEBUG|E_WARN);
					
					if(function_exists('register_' . $name . '_' . $list))
					{
						$GLOBALS['modules']['list']['lists'][$list] = call_user_func_array('register_' . $name . '_' . $list, array());
					}
				}
			}
		}
	}
	
	$GLOBALS['lists'] = &$GLOBALS['modules']['list']['lists'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, accepts any valid list name
 */
function validate_list($request)
{
	if(isset($request['list']) && in_array($request['list'], array_keys($GLOBALS['lists'])))
		return $request['list'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_list($request)
{
	$request['cat'] = validate($request, 'cat');
	
	$request['list'] = validate($request, 'list');
	
	// if there isn't a list specified show the list template
	if(!isset($request['list']))
	{
		theme('list');
		
		return;
	}	
	else
	{
		header('Cache-Control: no-cache');
		if($request['list'] == 'rss')
		{
			header('Content-Type: application/rss+xml');
		}
		elseif($request['list'] == 'wpl')
		{
			header('Content-Type: application/vnd.ms-wpl');
		}
		else
		{
			header('Content-Type: ' . getMime($request['list']));
		}
	
		// set some output variables
		register_output_vars('list', $request['list']);
		
		if($session_select = session('select'))
		{
			$request['selected'] = $session_select['selected'];
		}
	
		// use the select.php module file selector to generate a list from the request
		//   should be the same list, and it will register the files output
		output_select($request);

		//   then the list template will be used
		theme($request['list']);
	}
}

function theme_list()
{
	theme('header');
	
	theme('list_block');
	
	theme('footer');
}


function theme_list_block()
{
	?>
    <div id="type">
        Get the list:
        <br />
        <form action="<?php print url('module=list'); ?>" method="get">
            <input type="hidden" name="cat" value="<?php print $GLOBALS['templates']['html']['cat']; ?>" />
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

function theme_xml()
{
	$ext_icons = array();
	$ext_icons['FOLDER'] = url('template=' . setting('local_template') . '&file=images/filetypes/folder_96x96.png');
	$ext_icons['FILE'] = url('template=' . setting('local_template') . '&file=images/filetypes/file_96x96.png');
	
	$type_icons = array();
	$type_icons['audio'] = url('template=' . setting('local_template') . '&file=images/filetypes/music_96x96.png');

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
	?><count><?php print $GLOBALS['templates']['html']['total_count']; ?></count><?php
	foreach($GLOBALS['templates']['html']['files'] as $i => $file)
	{
		?>
		<file>
			<index><?php print $GLOBALS['templates']['vars']['start'] + $i; ?></index>
			<id><?php print $file['id']; ?></id>
			<name><?php print $file['Filename']; ?></name>
			<text><?php print $file['Filename']; ?></text>
			<?php
			$type_arr = split('/', $file['Filemime']);
			$type = $type_arr[0];
			?><icon><?php print isset($ext_icons[$file['Filetype']])?$ext_icons[$file['Filetype']]:(isset($type_icons[$type])?$type_icons[$type]:$ext_icons['FILE']); ?></icon>
			<ext><?php print $file['Filetype']; ?></ext>
			<tip><?php
			foreach($GLOBALS['templates']['vars']['columns'] as $j => $column)
			{
				if(isset($file[$column]))
				{
					print $column . ': ' . $file[$column] . '&lt;br /&gt;';
				}
			}
			?></tip>
			<path><?php print $file['Filepath']; ?></path>
			<link><?php print url('module=file&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&filename=' . urlencode($file['Filename']), false, true); ?></link>
			<short><?php print htmlspecialchars(substr($GLOBALS['templates']['vars']['files'][$i]['Filename'], 0, 13)); ?>...</short>
			<?php
			if(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'archive'))
			{
				?><cat>archive</cat><?php
			}
			elseif(handles($GLOBALS['templates']['vars']['files'][$i]['Filepath'], 'diskimage'))
			{
				?><cat>diskimage</cat><?php
			}
			else
			{
				?><cat><?php print $GLOBALS['templates']['html']['cat']; ?></cat><?php
			}
			
			foreach($GLOBALS['templates']['vars']['columns'] as $i => $column)
			{
				?><info-<?php print $column; ?>><?php print isset($file[$column])?$file[$column]:''; ?></info-><?php print $column; ?>><?php
			}
			?>
		</file>
		<?php
	}
	
	?></request><?php
}


function theme_wpl()
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
					?><media src="<?php print url('module=file&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&filename=' . urlencode($file['Filename']), false, true); ?>" /><?php
				}
				?>
			</seq>
		</body>
	</smil>
	<?php
}


function theme_rss()
{
	print '<?xml version="1.0" encoding="utf-8"?>';
	?>
	<rss version="2.0">
		<channel>
			<title><?php print setting('html_name'); ?> - <?php print $GLOBALS['templates']['vars']['cat']; ?></title>
			<link><?php print url('', false, true); ?></link>
			<description></description>
            <?php
			foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
			{
				?>
				<item>
					<title><?php print basename($file['Filepath']); ?></title>
					<link><?php print url('module=file&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&filename=' . basename($file['Filepath']), false, true); ?></link>
					<description></description>
				</item>
                <?php
			}
			?>
		</channel>
	</rss>
    <?php
}


function theme_m3u()
{
	if(!isset($GLOBALS['templates']['vars']['extra']))
	{
		header('Content-Type: text/html');
		if(!isset($GLOBALS['templates']['vars']['selected']))
		{
			goto('list=m3u&module=list&cat=' . $_REQUEST['cat']);
		}
		else
		{
			$ids = implode(',', $GLOBALS['templates']['vars']['selected']);
			
			theme('header');
			
			?>
			Note: All non-media types will be filtered out using this list type.<br />
			Select your audio/video format:<br />
			<a href="<?php print url('module=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&item=' . $ids . '&extra=mp3&filename=Files.m3u'); ?>">mp4</a>
			: <a href="<?php print url('module=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&item=' . $ids . '&extra=mpg&filename=Files.m3u'); ?>">mpg/mp3</a>
			: <a href="<?php print url('module=list&list=m3u&cat=' . $GLOBALS['templates']['vars']['cat'] . '&item=' . $ids . '&extra=wm&filename=Files.m3u'); ?>">wmv/wma</a>
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
			
			theme('footer');

			return;
		}
	}

	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: attachment; filename="' . (isset($GLOBALS['templates']['vars']['filename'])?$GLOBALS['templates']['vars']['filename']:$GLOBALS['handlers'][$_REQUEST['cat']]['name'] . '.m3u"')); 

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
?>#EXTINF:<?php print $length; ?>,<?php print $title . "\r\n"; ?>
<?php print url('module=encode&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&encode=' . $audio . '&filename=' . basename($file['Filepath']), true, true) . "\r\n";
		}
		elseif(handles($file['Filepath'], 'video'))
		{
?>#EXTINF:<?php print $length; ?>,<?php print $title . "\r\n"; ?>
<?php print url('module=encode&cat=' . $GLOBALS['templates']['vars']['cat'] . '&id=' . $file['id'] . '&encode=' . $video . '&filename=' . basename($file['Filepath']), true, true) . "\r\n";
		}
	}
}

