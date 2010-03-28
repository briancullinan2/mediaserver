<?php
// other stuff can be used here
if(!isset($_REQUEST['dir']))
	$_REQUEST['dir'] = '/';
if(!isset($_REQUEST['limit']))
	$_REQUEST['limit'] = 50;

$GLOBALS['templates']['TEMPLATE_INDEX'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'index.html';
$GLOBALS['templates']['TEMPLATE_SELECT'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'index.html';
$GLOBALS['templates']['TEMPLATE_SEARCH'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'search.html';
$GLOBALS['templates']['TEMPLATE_PAGES'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'pages.html';
$GLOBALS['templates']['TEMPLATE_HEADER'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'header.html';
$GLOBALS['templates']['TEMPLATE_FOOTER'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'footer.html';
$GLOBALS['templates']['TEMPLATE_ADMIN_INSTALL'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'install.php';
$GLOBALS['templates']['TEMPLATE_ADMIN_PLUGINS'] = LOCAL_ROOT . LOCAL_TEMPLATE . 'plugins.php';

include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'header.php';

if(function_exists('register_live_header'))
{
	$template = call_user_func_array('register_live_header', array());
	if(isset($template['scripts']))
	{
		if(is_array($template['scripts']))
		{
			foreach($template['scripts'] as $script)
				register_script($script);
		}
		elseif(is_string($template['scripts']))
			register_script($template['scripts']);
	}
	
	if(isset($template['styles']))
	{
		if(is_array($template['styles']))
		{
			foreach($template['styles'] as $style)
				register_style($style);
		}
		elseif(is_string($template['styles']))
			register_style($template['styles']);
	}
}
