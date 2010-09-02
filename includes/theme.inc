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

function theme__files()
{
	if(count($GLOBALS['output']['files']) == 0)
	{
		?><b>There are no files to display</b><?php
	}
	else
	{
		$column_lengths = array();
		if($GLOBALS['output']['settings']['view'] == 'mono')
		{
			// go through files ahead of time and make them monospaced
			foreach($GLOBALS['output']['files'] as $i => $file)
			{
				// find the longest string for each column
				foreach($file as $column => $value)
				{
					if(!isset($column_lengths[$column]) || strlen($value) > $column_lengths[$column])
						$column_lengths[$column] = strlen($value);
				}
			}
			?><code><?php
			
			?><input type="checkbox" name="item" value="All" /> <?php
			print str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths['Filepath']+2) . 's', 'Filepath'));
			foreach($GLOBALS['output']['settings']['columns'] as $i => $column)
			{
				print ' | ' . str_replace(' ', '&nbsp;', sprintf('%-' . ($column_lengths[$column]+2) . 's', $column));
			}
			?> | Download<br /><?php
		}
		elseif($GLOBALS['output']['settings']['view'] == 'table')
		{
			?><table cellpadding="10" cellspacing="0" border="1"><tr><td><?php
		}
		
		foreach($GLOBALS['output']['html']['files'] as $i => $file)
		{
			$file = alter_file($GLOBALS['output']['files'][$i], $column_lengths);
			$GLOBALS['output']['html']['files'][$i] = $file;
			// make links browsable
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'archive')) $handler = 'archive';
			elseif(handles($GLOBALS['output']['files'][$i]['Filepath'], 'playlist')) $handler = 'playlist';
			elseif(handles($GLOBALS['output']['files'][$i]['Filepath'], 'diskimage')) $handler = 'diskimage';
			else $handler = $GLOBALS['output']['handler'];
			
			if($GLOBALS['output']['handler'] != $handler || $GLOBALS['output']['files'][$i]['Filetype'] == 'FOLDER') $new_handler = $handler;
			
			$link = isset($new_handler)?url('select?handler=' . $new_handler . '&dir=' . urlencode($GLOBALS['output']['files'][$i]['Filepath'])):url('file/' . $handler . '/' . $file['id'] . '/' . urlencode($GLOBALS['output']['files'][$i]['Filename']));
			?>
			<input type="checkbox" name="item[]" value="<?php print $file['id']; ?>" <?php print isset($GLOBALS['output']['selected'])?(in_array($GLOBALS['output']['files'][$i]['id'], $GLOBALS['output']['selected'])?'checked="checked"':''):''; ?> />
			<a href="<?php print $link; ?>"><?php print trim($file['Filepath'], '&nbsp;'); ?></a><?php print substr($file['Filepath'], strlen(trim($file['Filepath'], '&nbsp;'))); ?>
			<?php
			
			foreach($GLOBALS['output']['settings']['columns'] as $j => $column)
			{
				if($GLOBALS['output']['settings']['view'] == 'mono')
					print ' | ';
				elseif($GLOBALS['output']['settings']['view'] == 'table')
					print '</td><td>';
				else
					print ' - ';
				
				if(isset($file[$column]))
				{
					print $file[$column];
				}
			}
			
			if($GLOBALS['output']['settings']['view'] == 'mono')
			{
				print ' | ';
			}
			elseif($GLOBALS['output']['settings']['view'] == 'table')
			{
				print '</td><td>';
			}
			else
			{
				?> - Download: <?php
			}
			?>
			<a href="<?php print url(array(
							'module' => 'zip',
							'handler' => $GLOBALS['output']['handler'],
							'id' => $file['id'],
							'filename' => 'Files.zip'
						)); ?>">zip</a> :
			<a href="<?php print url(array(
							'module' => 'torrent',
							'handler' => $GLOBALS['output']['handler'],
							'id' => $file['id'],
							'filename' => 'Files.torrent'
						)); ?>">torrent</a>
			<?php
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'video'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp4', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mpg', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MPG</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'wmv', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMV</a>
				<?php
			}
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'audio'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp4a', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP4</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'mp3', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">MP3</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'wma', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">WMA</a>
				<?php
			}
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'image'))
			{
				?>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'jpg', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">JPG</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'gif', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">GIF</a>
				: <a href="<?php print url(array('module' => 'encode', 'encode' => 'png', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">PNG</a>
				<?php
			}
			if(handles($GLOBALS['output']['files'][$i]['Filepath'], 'code'))
			{
				?>
				: <a href="<?php print url(array('module' => 'code', 'handler' => $GLOBALS['output']['handler'],
								'id' => $file['id'], 'filename' => $file['Filename'])); ?>">view</a>
				<?php
			}
			
			if($GLOBALS['output']['settings']['view'] == 'table')
			{
				if($i < count($GLOBALS['output']['files']) - 1)
				{
					?></td></tr><tr><td><?php
				}
			}
			else
			{
				?><br /><?php
			}
		}
		if($GLOBALS['output']['settings']['view'] == 'mono')
		{
			?></code><?php
		}
		if($GLOBALS['output']['settings']['view'] == 'table')
		{
			?></td></tr></table><?php
		}
	}
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
		theme('form_object', 'status', array(
			'type' => 'fieldset',
			'options' => $GLOBALS['output']['status']
		));
		
	theme('form_object', 'setting', array(
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

function theme__select()
{
	theme('header');

	theme('search_block');

	?>
	There are <?php print $GLOBALS['output']['html']['total_count']; ?> result(s).<br />
	Displaying items <?php print $GLOBALS['output']['html']['start']; ?> to <?php print $GLOBALS['output']['html']['start'] + $GLOBALS['output']['html']['limit']; ?>.
	<br />
	<?php
	
	theme('pages');
	?>
	<br />
	<form name="select" action="<?php print $GLOBALS['output']['html']['get']; ?>" method="post">
		<input type="submit" name="select" value="All" />
		<input type="submit" name="select" value="None" />
		<p style="white-space:nowrap">
		Select<br />
		On : Off<br />
		<?php
		theme('files');
		?>
		<input type="submit" value="Save" /><input type="reset" value="Reset" /><br />
	</form>
	<?php
		
	theme('pages');
	
	theme('list_block');
	
	theme('template_block');

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

