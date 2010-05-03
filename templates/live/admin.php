<?php

function register_live_admin()
{
	return array(
		'name' => 'Administration Menu',
	);
}

function theme_live_admin()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Administration</h1>
			<span class="subText">Select the administration module you would like to use below.</span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	foreach($GLOBALS['modules']['admin']['modules'] as $name => $module)
	{
		if($module['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
			continue;
			
		if(!function_exists('output_admin_' . $name))
			$link = 'module=admin_modules&configure_module=admin_' . $name;
		else
			$link = 'module=admin_' . $name;
			
		?>
		<div class="nothover" onMouseOver="this.className='hover';" onMouseOut="this.className='nothover';">
			<a href="<?php print url($link); ?>" style="font-size:14px;"><?php print $module['name']; ?></a><br /><br />
			Description: <?php print $module['description']; ?>
			<br /><br />
		</div>
		<?php
	}
	
	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_template()
{
	$recommended = array('live');
	$required = array('plain');
	
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Templates</h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_template']['description']; ?></span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	foreach($GLOBALS['templates'] as $key => $template)
	{
		if(!isset($template['name']))
			continue;
			
		?>
		<div class="nothover" onMouseOver="this.className=this.className.replace('nothover', 'hover');" onMouseOut="this.className='nothover';">
			<?php
			if(!in_array($key, $required))
			{
				?><a href="" class="disablebtn" onmouseover="this.parentNode.className='hover red';" onmouseout="this.parentNode.className='hover'">&nbsp;</a><?php
			}
			?>
			<a href="<?php print url('template=' . $key); ?>" style="font-size:14px;"><?php print $template['name']; ?></a><?php print in_array($key, $required)?' (Required)':(in_array($key, $recommended)?' (Recommended)':''); ?><br /><br />
			Description: <?php print $template['description']; ?><br />
			Other Files:<br />
			<?php
			foreach($template['files'] as $i => $file)
			{
				print $file . (($i < count($template['files']) - 1)?', ':'');
				print ($i > 0 && $i % 5 == 0)?'<br />':'';
			}
			?>
			<br /><br />
		</div>
		<?php
	}

	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_watch()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title"><?php print $GLOBALS['modules']['admin_watch']['name']; ?></h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_watch']['description']; ?></span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php

	?>
	<form action="" method="post">
		<select name="wremove" size="10">
		
		<?php
			foreach($GLOBALS['templates']['vars']['ignored'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">ignore: <?php print $watch['Filepath']; ?></option>
			<?php
			}
			foreach($GLOBALS['templates']['vars']['watched'] as $i => $watch)
			{
			?>
				<option value="<?php echo $watch['id']; ?>">watch: <?php print $watch['Filepath']; ?></option>
			<?php
			}
		?>
		</select>
		<br />
		<input type="submit" value="Remove" />
	</form>
	<form action="" method="post">
		<input type="text" name="waddpath" size="50" value="<?php print isset($GLOBALS['templates']['vars']['waddpath'])?$GLOBALS['templates']['vars']['waddpath']:(isset($GLOBALS['templates']['vars']['dir'])?$GLOBALS['templates']['vars']['dir']:''); ?>" />
		<input type="submit" value="Add" />
		<br />
	</form>
	<?php
	?>Select a Folder:<br /><?php
	
	theme('select_block');

	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}

function theme_live_alias()
{
	theme('header');
	
	?>
	<div class="contentSpacing">
			<h1 class="title">Path Aliasing</h1>
			<span class="subText"><?php print $GLOBALS['modules']['admin_alias']['description']; ?></span>
	<?php
	
	theme('errors');
	
	?><div class="titlePadding"></div><?php
	
	?>
	Alaises are very complex and should only be used by advanced users with a deep understanding of php and regular expressions.<br />
	Aliases are used in preg_replace() functions in order to replace specified parts of the filepath with a different path; sort of like a symbolic link on linux filesystems.<br />
	Here are some basic rules that allow aliases to work correctly:<br />
	<ul>
		<li>There must be an alias for returning a root path, for example when the dir=/ request variable is used the / is the root which must match aliased paths, otherwise no files will be returned and it will make the site unbrowsable.</li>
		<li>All processed aliased paths must be accessible, when a path resolves it must start with / (&lt;-root) and each directory in the tree should be accessible.</li>
		For example, if a path /home/share/Pictures/ is resolved with the alias to /Shared/Pictures/, this will break the site because there is no way to access Pictures when / root is browsed.<br />
	Instead the proper solution for this is to match /home/share/ and resolve it to /Shared/, however the site will still be broken because the /home/share/ folder is not watched and will never be added to the database.<br />
		<li>When aliases are used the paths leading up to the watched directories are NOT added to the database automatically, therefore a special modifier must be used on the alias matching.</li>
		<li>At least one alias must match the / root directory, in order to access the other aliased paths, for example the /Shared/ alias will also match just a / which is the root directory.</li>
		Now if /Shared/ is browsed, /home/share/ will be resolved, AND if / is browsed it will still resolve to /home/share/.<br />
		<li>All aliases should be url friendly!</li>
		<li>Finally, the 4 columns for aliases are described below:</li>
	</ul>
	Paths - This is the path on the local filesystem, this is the path to replace with an alias.<br />
	Paths_regexp - This is a regular expression that can match all the folders in the Paths definition.<br />
	Alias - This is the name of the alias, or path to replace with.<br />
	Alias_regexp - This is a regular expression that matches the entire Alias path.<br />
	It is suggested that at least one alias match the / root directory for browsing.  If this is not done, some template will not function properly.<br />
	Here is a full example of an alias in use:<br />
	<code>
	+--------------+----------+-----------------------+---------------------+------+<br />
	| Paths &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Alias &nbsp;&nbsp; | Paths_regexp &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Alias_regexp &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Hard |<br />
	+--------------+----------+-----------------------+---------------------+------+<br />
	| /home/share/ | /Shared/ | /^\/home\/(share\/)?/i | /^\/$|^\/Shared\//i | &nbsp;&nbsp; 0 |<br />
	+--------------+----------+-----------------------+---------------------+------+<br />
	</code>
	The breakdown:<br />
	The Paths_regexp column will match /home/ or /home/share/ at the beginning of the filepath.<br />
	The Alias_regexp column will match / (the root directory) and /Shared/ at the beginning of the filepath.<br />
	Additionally, the site will automatically add paths between the defined alias and the watched directories.<br />
	For exmaple, if an alias /home/share/ is defined, and there is a watch directory /home/share/Pictures/Other/, /home/share/Pictures/Other/ will be added to the database because it is a watched directory,<br />
	Additionally /home/share/Picture/ will be added to the database to simplify browsing, but /home/share/ will not be added, so matching the / root directory is still required.<br />
	Hard and Soft links explained:<br />
	Contrary to linux symbolic links, Soft links are only meant to replace paths on output of the Filepath.<br />
	Hard links are meant to replace paths when inputting to the database, this can be usefull when adding files across network shares.<br />
	For example, if a Windows or Samba share is mounted on /home/share/Remote/, and that path exists on the remote computer under C:\Documents\Shared Files\<br />
	An alias can be used to replace C:\Documents\Shared Files\ with /home/share/Remote/, then a cron job can be run on the remote system, but files can still be accessed by the webserver using the /home/share/Remote/ path.<br />
	For security reasons, only the Soft aliases are accessible from the site, Hard aliases are only used internally.<br />
	Note: Hard aliases can also be used to help some path processing like in the db_playlist handler.
	<?php

	?><div class="titlePadding"></div>
	</div><?php
	
	theme('footer');
}