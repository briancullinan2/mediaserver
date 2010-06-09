<?php

function register_mobile()
{
	return array(
		'name' => 'Mobile',
		'description' => 'Mobile theme for use of devices with small screens.',
		'privilage' => 1,
		'alter request' => true,
		'path' => __FILE__,
	);
}

function alter_request_mobile($request)
{
	// other stuff can be used here
	if(!isset($request['dir']))
		$request['dir'] = '/';
	if(!isset($request['limit']))
		$request['limit'] = 50;
	if(!isset($request['group_by']) && !isset($request['group_index']))
	{
		$request['group_by'] = 'Filename';
		$request['group_index'] = true;
	}
		
	return $request;
}

function theme_mobile_select()
{
	if(!isset($GLOBALS['templates']['vars']['group_index']) || !is_bool($GLOBALS['templates']['vars']['group_index']))
	{
		foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
		{
			// make links browsable
			if(handles($file['Filepath'], 'archive')) $cat = 'archive';
			elseif(handles($file['Filepath'], 'playlist')) $cat = 'playlist';
			elseif(handles($file['Filepath'], 'diskimage')) $cat = 'diskimage';
			else $cat = $GLOBALS['templates']['vars']['cat'];
			
			if($GLOBALS['templates']['vars']['cat'] != $cat || $file['Filetype'] == 'FOLDER')
			{
				if(substr($file['Filepath'], -1) != '/') $file['Filepath'] .= '/';
				$new_cat = $cat;
			}
			$link = isset($new_cat)?url('module=select&cat=' . $new_cat . '&dir=' . urlencode($file['Filepath'])):url('module=file&cat=' . $cat . '&id=' . $file['id'] . '&filename=' . $file['Filename']);
			unset($new_cat);
			
			?><a href="<?php print $link; ?>"><?php print $GLOBALS['templates']['html']['files'][$i]['Filename']; ?></a><?php
		}
		
		return;
	}
	
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php print setting('html_name'); ?> : <?php print $GLOBALS['modules'][$GLOBALS['templates']['vars']['module']]['name'];?></title>
	<link rel="stylesheet" href="<?php print url('module=template&template=mobile&tfile=mobile.css'); ?>" type="text/css"/>
	<script language="javascript" type="text/javascript" src="<?php print url('module=template&template=mobile&tfile=mobile.js'); ?>"></script>
	<script language="javascript" type="text/javascript" src="<?php print url('module=template&template=mobile&tfile=jquery.js'); ?>"></script>
	<script language="javascript" type="text/javascript">
		var directory = "<?php print urlencode($GLOBALS['templates']['vars']['dir']); ?>";
	</script>
	</head>
	<body onload="init()">
	<div id="debug"></div>
	<div id="topbuffer"></div>
	<div id="files">
		<?php
		foreach($GLOBALS['templates']['vars']['files'] as $i => $file)
		{
			// make links browsable
			if(handles($file['Filepath'], 'archive')) $cat = 'archive';
			elseif(handles($file['Filepath'], 'playlist')) $cat = 'playlist';
			elseif(handles($file['Filepath'], 'diskimage')) $cat = 'diskimage';
			else $cat = $GLOBALS['templates']['vars']['cat'];
			
			if($GLOBALS['templates']['vars']['cat'] != $cat || $file['Filetype'] == 'FOLDER')
			{
				if(substr($file['Filepath'], -1) != '/') $file['Filepath'] .= '/';
				$new_cat = $cat;
			}
			$link = isset($new_cat)?url('module=select&cat=' . $new_cat . '&dir=' . urlencode($file['Filepath'])):url('module=file&cat=' . $cat . '&id=' . $file['id'] . '&filename=' . $file['Filename']);
			unset($new_cat);
			
			?>
			<div class="file char_<?php print substr($file['Filename'], 0, 1); ?>">
				<a href="<?php print $link; ?>"><?php print $GLOBALS['templates']['html']['files'][$i]['Filename']; ?></a>
			</div>
			<?php
		}
		?>
	</div>
	<div id="bottombuffer"></div>
	</body>
	</html>
	<?php

}