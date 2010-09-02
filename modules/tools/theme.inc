<?php

function theme_live_tool_info()
{
	theme('header',
		'Tools: ' . $GLOBALS['modules'][$GLOBALS['output']['module']]['name'],
		$GLOBALS['modules'][$GLOBALS['output']['module']]['description']
	);
	
	// output configuration if it is set
	if(isset($GLOBALS['output']['options']))
		theme('admin_modules_configure');
	
	theme('form_object', 'info', array(
		'action' => $GLOBALS['output']['html']['get'],
		'options' => $GLOBALS['output']['infos'],
		'type' => 'form',
	));
	$GLOBALS['debug_errors'] = array();

	theme('footer');
}

function theme__singular($config)
{
	extract($config);
	theme('info_objects', array('type' => 'info') + $config);

	?><script language="javascript">
	if(!singular_cancel)
	{
		$.get('<?php print $href; ?>',function(data){
			$('#row_<?php print $name; ?>').replaceWith(data);
		}, 'text');
	}
	</script><?php
}
