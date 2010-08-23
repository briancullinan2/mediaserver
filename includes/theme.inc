<?php

function theme__confirmation()
{
	theme('header');
	
	?>Thanks for signing up!<?php
	
	theme('footer');
}

function theme__default()
{
	theme('header');
	
	?><h1>Module: <?php print $GLOBALS['modules'][$GLOBALS['output']['module']]['name']; ?></h1>
	This page requires special parameters that have not been set.  This default page is a placeholder.<?php
	
	theme('errors_block');

	theme('footer');
}

function theme__errors()
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

function theme__errors_block()
{
	theme('errors');
}

function theme__footer()
{
	if($GLOBALS['output']['module'] != 'users')
		theme('login_block');
	
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

function theme__header()
{
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print setting('html_name'); ?> : <?php print htmlspecialchars($GLOBALS['modules'][$GLOBALS['output']['module']]['name']); ?></title>
</head>

<body>
<h1><?php print htmlspecialchars($GLOBALS['modules'][$GLOBALS['output']['module']]['name']); ?></h1>
	<?php
	
	theme('errors');
}

function theme__login()
{
	theme('header');
	
	if($GLOBALS['output']['user']['Username'] != 'guest' || setting('database_enable') == false)
		theme('logout_block');
	else
		theme('login_block');
	
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
			if($config['type'] == MENU_NORMAL)
			{
				?><li><a href="<?php print $path; ?>"><?php echo $config['name']; ?></a></li><?php
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

function theme__search_block()
{
	print $GLOBALS['modules'][$GLOBALS['templates']['vars']['cat']]['name']; ?> Search:<br />
	<form action="<?php print $GLOBALS['templates']['vars']['get']; ?>" method="get" id="search">
		<input type="text" name="search" value="<?php print isset($GLOBALS['templates']['vars']['search']['search'])?$GLOBALS['templates']['vars']['search']['search']:''; ?>" id="searchInput" />
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
		Search: <input type="text" name="search" size="40" value="<?php print isset($GLOBALS['templates']['vars']['search'])?$GLOBALS['templates']['vars']['search']:''; ?>" /><br /><br />
		Directory: <input type="text" name="dir" size="40" value="<?php print isset($GLOBALS['templates']['vars']['dir'])?$GLOBALS['templates']['vars']['dir']:''; ?>" />
		<h3>Search Individual Fields:</h3>
		Category: <select name="cat">
		<?php
		foreach(get_handlers() as $handler => $config)
		{
			?><option value="<?php print $handler; ?>" <?php print ($GLOBALS['templates']['vars']['cat'] == $handler)?'selected="selected"':''; ?>><?php print $config['name']; ?></option><?php
		}
		?>
		</select><br /><br />
		<?php
		foreach($GLOBALS['templates']['vars']['columns'] as $column)
		{
			?>
			<?php print $column; ?>:
			<input type="text" name="search_<?php print $column; ?>" size="40" value="<?php print isset($GLOBALS['templates']['vars']['search']['search_' . $column])?$GLOBALS['templates']['html']['search']['search_' . $column]:''; ?>" />
			<br />
			<?php
		}
		?>
		<input type="submit" value="Search" /><input type="reset" value="Reset" />
	</form>
	<?php
	
	theme('footer');
}

function theme__scripts($scripts)
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

function theme__styles($styles)
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

