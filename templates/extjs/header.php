<?php

function theme_extjs_head($title)
{
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php theme('redirect_block'); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php print $title . ' : ' . setting('html_name'); ?></title>
<meta name="google-site-verification" content="K3Em8a7JMI3_1ry5CNVKIHIWofDt-2C3ohovDq3N2cQ" />
<script type="text/javascript" src="<?php print url('templates/extjs/scripts'); ?>"></script>
<link rel="stylesheet" href="<?php print url('templates/extjs/ext-3.3.1/resources/css/style'); ?>" type="text/css"/>
</head>
	<?php
}

function theme_extjs_header($title = NULL, $description = NULL, $html_title = NULL)
{
	if(!isset($title))
		$title = htmlspecialchars(get_module($GLOBALS['output']['module'], 'name'));
	
	if(!isset($GLOBALS['output']['extra']) || $GLOBALS['output']['extra'] != 'inneronly')
	{
		theme('head', $title);
	
		theme('body');
	}
	
	
	theme('container', isset($html_title)?$html_title:$title, $description);
}

function theme_extjs_breadcrumbs($breadcrumbs = array(), $crumb = NULL)
{
	?>
	<li><a href="<?php print url('select'); ?>"><?php print setting('html_name'); ?></a></li>
	<li><img src="<?php print url('templates/extjs/images/carat.gif'); ?>" class="crumbsep" alt="&gt;" /></li>
	<?php
	if(count($breadcrumbs) == 0)
	{
		?><li><strong><?php print $crumb; ?></strong></li><?php
	}
	else
	{
		$count = 0;
		foreach($breadcrumbs as $path  => $menu)
		{
			if($count != count($breadcrumbs) - 1)
			{
				?>
				<li><a href="<?php print url($path); ?>"><?php print $menu['name']; ?></a></li>
				<li><img src="<?php print url('templates/extjs/images/carat.gif'); ?>" class="crumbsep" alt="&gt;" /></li>
				<?php
			}
			else
			{
				?><li><strong><?php print $menu['name']; ?></strong></li><?php
			}
			$count++;
		}
	}
}

function theme_extjs_template_block()
{
	?><div class="template_box"><?php
	foreach($GLOBALS['templates'] as $name => $template)
	{
		if(isset($template['name']))
		{
			?><a href="<?php print url('select?template=' . $name, false, true); ?>"><?php print $template['name']; ?></a><?php
		}
	}
	?></div><?php
}

function theme_extjs_body()
{
	$scheme = extjs_get_colors();
?>
<body class="colors_<?php print $scheme; ?>">
	<script type="text/javascript">Ext.QuickTips.init();</script>
<?php if(is_module('list')) theme('list_block'); ?>
	<div id="expander">
		<table id="header" cellpadding="0" cellspacing="0" class="colors_bg header">
			<tr>
				<td id="siteTitle"><a href="<?php print url('select'); ?>"><?php print setting('html_name'); ?></a></td>
				<td>
					<?php if(dependency('search') != false) theme('search_block'); ?>
				</td>
				<td id="templates"><?php theme('template_block'); ?></td>
			</tr>
		</table>
		<?php if(setting('debug_mode')) theme('debug_block'); ?>
		<?php if(dependency('language') != false) theme('language_block'); ?>
		<div id="loading">&nbsp;</div>
		<?php theme('context_menu'); ?>
		<div id="container">
	<?php
}


function theme_extjs_container($title = NULL, $description = NULL)
{
	$scheme = extjs_get_colors();

	?>
	<div id="breadcrumb">
		<ul>
		<?php
		theme('breadcrumbs', $GLOBALS['output']['breadcrumbs'], $title);
		?>
		</ul>
	</div>
	<div id="mainColumn" class="colors_<?php print $scheme; ?>" cellpadding="0" cellspacing="0">
		<?php
		
		if(isset($title))
		{
			?><div class="x-window-header" id="title"><?php print $title; ?></div><?php
		}
		if(isset($description))
		{
			// this should go in a little help bubble
			?><script type="text/javascript">
				Ext.QuickTips.register({
					title: 'My Tooltip',
					text: '<?php print $description; ?>',
				});
			</script><?php
		}
		
		theme('errors_block');
}