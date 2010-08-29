<?php

function theme__confirmation()
{
	theme('header');
	
	?>Thanks for signing up!<?php
	
	theme('footer');
}

function theme__default()
{
	theme('header',
		'Module: ' . $GLOBALS['modules'][$GLOBALS['output']['module']]['name'],
		'This page requires special parameters that have not been set.  This default page is a placeholder.'
	);

	theme('footer');
}

function theme__errors()
{
	theme('header',
		'Module: ' . $GLOBALS['modules'][$GLOBALS['output']['module']]['name'],
		'There were errors while processing your request.'
	);

	theme('footer');
}

function theme__errors_block()
{
	if(count($GLOBALS['user_errors']) > 0)
	{
		?><span style="color:#C00"><?php
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	if(count($GLOBALS['warn_errors']) > 0)
	{
		?><span style="color:#CC0"><?php
		foreach($GLOBALS['warn_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	if(count($GLOBALS['note_errors']) > 0)
	{
		?><span style="color:#00C"><?php
		foreach($GLOBALS['note_errors'] as $i => $error)
		{
			?><b><?php print $error; ?></b><br /><?php
		}
		?></span><?php
	}
	$GLOBALS['note_errors'] = array();
	$GLOBALS['warn_errors'] = array();
	$GLOBALS['user_errors'] = array();
	$GLOBALS['debug_errors'] = array();
}

function theme__footer()
{
	if($GLOBALS['output']['module'] != 'users')
		theme('login_block', $GLOBALS['output']['username']);
	
	?>
	Modules:
<ul>
<?php
foreach(get_modules() as $name => $module)
{
	if($module['privilage'] > $GLOBALS['output']['user']['Privilage'])
		continue;
										
	if(!function_exists('output_' . $name))
		$link = 'admin/modules/' . $name;
	else
		$link = $name;
									
	?><li><a href="<?php print url($link); ?>"><?php echo $module['name']; ?></a></li><?php
}
?>
</ul>
</body>
</html>
	<?php
}

function theme__header($title = NULL, $description = NULL)
{
	if(!isset($title))
		$title = htmlspecialchars($GLOBALS['modules'][$GLOBALS['output']['module']]['name']) . ' : ' . setting('html_name');
	
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print $title; ?></title>
</head>

<body>
<h1><?php print $title; ?></h1><?php
	
	theme('errors_block');
}

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


function theme__login()
{
	theme('header');
	
	if($GLOBALS['output']['user']['Username'] != 'guest' || setting('database_enable') == false)
		theme('logout_block');
	else
		theme('login_block', $GLOBALS['output']['username']);
	
	theme('footer');
}

function theme__login_block()
{
	if($GLOBALS['output']['user']['Username'] != 'guest' || setting('database_enable') == false)
	{
		theme('logout_block');
		return;
	}
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>	
	<form action="<?php echo url('users/login?return=' . urlencode($return)); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Login" /><input type="reset" value="Reset" />
		
	</form>
	<?php
}

function theme__logout_block()
{
	if(isset($GLOBALS['output']['return']))
		$return = $GLOBALS['output']['return'];
	else
		$return = $GLOBALS['output']['get'];
	?>
	<form action="<?php echo url('users/logout?return=' . urlencode($return)); ?>" method="post">
		<input type="submit" value="Logout" />
	</form>
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
	header('Content-Disposition: attachment; filename="' . (isset($GLOBALS['output']['filename'])?$GLOBALS['output']['filename']:$GLOBALS['modules'][$_REQUEST['cat']]['name'] . '.m3u"')); 

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


function theme__menu()
{
	theme('header');
	
	theme('menu_block');
	
	theme('footer');
}

function theme__menu_block()
{
	if(isset($GLOBALS['output']['menus']))
	{
		?>
		Menu:<br />
		<ul>
		<?php
		
		foreach($GLOBALS['output']['menus'] as $path => $config)
		{
			// this path actually leads to output as is, no need to validate it by using url()
			if($config['type'] == MENU_NORMAL || $config['type'] == MENU_LIST)
			{
				?><li><a href="<?php print url($path); ?>"><?php echo $config['name']; ?></a></li><?php
			}
		}
		
		?></ul><?php
	}
}

function theme__module()
{
	theme('header');

	// if the status is avaiable print that out first
	if(isset($GLOBALS['output']['status']))
		print_form_object('status', array(
			'type' => 'fieldset',
			'options' => $GLOBALS['output']['status']
		));
		
	print_form_object('setting', array(
		'action' => url('admin/modules/' . $GLOBALS['output']['configure_module'], true),
		'options' => $GLOBALS['output']['options'],
		'type' => 'form',
	));
	
	theme('footer');
}

function theme__pages()
{
	$item_count = count($GLOBALS['output']['files']);
	$page_int = $GLOBALS['output']['start'] / $GLOBALS['output']['limit'];
	$lower = $page_int - 8;
	$upper = $page_int + 8;
	$GLOBALS['output']['total_count']--;
	$pages = floor($GLOBALS['output']['total_count'] / $GLOBALS['output']['limit']);
	$prev_page = $GLOBALS['output']['start'] - $GLOBALS['output']['limit'];
	if($pages > 0)
	{
		if($lower < 0)
		{
			$upper = $upper - $lower;
			$lower = 0;
		}
		if($upper > $pages)
		{
			$lower -= $upper - $pages;
			$upper = $pages;
		}
		
		if($lower < 0)
			$lower = 0;
		
		if($GLOBALS['output']['start'] > 0)
		{
			if($GLOBALS['output']['start'] > $GLOBALS['output']['limit'])
			{
			?>
			<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=0'); ?>">First</a>
			<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=' . $prev_page); ?>">Prev</a>
			<?php
			}
			else
			{
			?>
			<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=0'); ?>">First</a>
			<?php
			}
			?> | <?php
		}
		
		for($i = $lower; $i < $upper + 1; $i++)
		{
			if($i == $page_int)
			{
				?><b><?php print $page_int + 1; ?></b><?
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=' . ($i * $GLOBALS['output']['limit'])); ?>"><?php print $i + 1; ?></a>
				<?php
			}
		}
		
		if($GLOBALS['output']['start'] <= $GLOBALS['output']['total_count'] - $GLOBALS['output']['limit'])
		{
			?> | <?php
			$last_page = floor($GLOBALS['output']['total_count'] / $GLOBALS['output']['limit']) * $GLOBALS['output']['limit'];
			$next_page = $GLOBALS['output']['start'] + $GLOBALS['output']['limit'];
			if($GLOBALS['output']['start'] < $GLOBALS['output']['total_count'] - 2 * $GLOBALS['output']['limit'])
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=' . $next_page); ?>">Next</a>
				<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
			else
			{
				?>
				<a class="pageLink" href="<?php print url($GLOBALS['output']['get'] . '&start=' . $last_page); ?>">Last</a>
				<?php
			}
		}
	}
}

function theme__redirect()
{
	theme('header');
	
	?>You are being redirected...<?php
	
	theme('footer');
}

function theme__redirect_block()
{
	if(isset($GLOBALS['output']['html']['redirect']))
	{
		?><META HTTP-EQUIV="refresh" CONTENT="1;URL=<?php print $GLOBALS['output']['html']['redirect']; ?>"><?php
	}
}

function theme__register()
{
	theme('header');
	
	?>	
	<form action="<?php echo url('users/register'); ?>" method="post">
	
		Username: <input type="text" name="username" value="<?php print isset($GLOBALS['output']['username'])?$GLOBALS['output']['username']:''; ?>" /><br />
		E-mail: <input type="text" name="email" value="<?php print isset($GLOBALS['output']['email'])?$GLOBALS['output']['email']:''; ?>" /><br />
		Password: <input type="password" name="password" value="" /><br />
		<input type="submit" value="Register" /><input type="reset" value="Reset" />
		
	</form>
	<?php
	
	theme('footer');
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

function theme__search_block()
{
	print $GLOBALS['modules'][$GLOBALS['output']['cat']]['name']; ?> Search:<br />
	<form action="<?php print $GLOBALS['output']['get']; ?>" method="get" id="search">
		<input type="text" name="search" value="<?php print isset($GLOBALS['output']['search']['search'])?$GLOBALS['output']['search']['search']:''; ?>" id="searchInput" />
		<input type="submit" value="Search" id="searchButton" /> <a href="<?php print url('search'); ?>">Advanced Search</a>
	</form>
	<br />
	<?php
}

function theme__search()
{
	theme('header');
	
	?>
	<form action="<?php print url(''); ?>" method="get">
		<h3>Search All Available Fields:</h3>
		Search: <input type="text" name="search" size="40" value="<?php print isset($GLOBALS['output']['search'])?$GLOBALS['output']['search']:''; ?>" /><br /><br />
		Directory: <input type="text" name="dir" size="40" value="<?php print isset($GLOBALS['output']['dir'])?$GLOBALS['output']['dir']:''; ?>" />
		<h3>Search Individual Fields:</h3>
		Category: <select name="cat">
		<?php
		foreach(get_handlers() as $handler => $config)
		{
			?><option value="<?php print $handler; ?>" <?php print ($GLOBALS['output']['cat'] == $handler)?'selected="selected"':''; ?>><?php print $config['name']; ?></option><?php
		}
		?>
		</select><br /><br />
		<?php
		foreach($GLOBALS['output']['columns'] as $column)
		{
			?>
			<?php print $column; ?>:
			<input type="text" name="search_<?php print $column; ?>" size="40" value="<?php print isset($GLOBALS['output']['search']['search_' . $column])?$GLOBALS['output']['html']['search']['search_' . $column]:''; ?>" />
			<br />
			<?php
		}
		?>
		<input type="submit" value="Search" /><input type="reset" value="Reset" />
	</form>
	<?php
	
	theme('footer');
}

function theme__scripts($scripts = array())
{
	if(!isset($scripts))
		return;
		
	if(is_string($scripts)) $scripts = array($scripts);
	
	foreach($scripts as $link)
	{
		?>
		<script language="javascript" type="text/javascript" src="<?php print url($link); ?>"></script>
		<?php
	}
}

function theme__styles($styles = array())
{
	if(!isset($styles))
		return;
		
	if(is_string($styles)) $styles = array($styles);
	
	foreach($styles as $link)
	{
		?>
		<link rel="stylesheet" href="<?php print url($link); ?>" type="text/css"/>
		<?php
	}
}


function theme__template_block()
{
	?>
	<br /><br />Select a Template:<br />
	<?php
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print url('template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><br /><?php
		}
	}
}

function theme__template()
{
	theme('header');
	
	theme('template_block');

	theme('footer');
}

function theme__users()
{
	switch($GLOBALS['output']['users'])
	{
		case 'login':
			theme('login');
		break;
		default:
			theme('default');
	}
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
			$type_arr = split('/', $file['Filemime']);
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
