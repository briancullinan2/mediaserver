<?php

// attempt to pass all requests to the appropriate plugins

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

if(substr(selfURL(), 0, strlen(HTML_DOMAIN)) != HTML_DOMAIN)
{
	header('Location: ' . HTML_DOMAIN . HTML_ROOT);
	exit();
}

// check if plugin exists
if(isset($GLOBALS['plugins'][$_REQUEST['plugin']]))
{
	// output plugin
	call_user_func_array('output_' . $_REQUEST['plugin'], array($_REQUEST));
	
	// select template for the current plugin
	if(getExt($GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['plugin'])]) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['plugin'])];
	else
	{
		set_output_vars();
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_' . strtoupper($_REQUEST['plugin'])]);
	}
}

?>
Errors:<br />
<?php
foreach($GLOBALS['errors'] as $i => $error)
{
	?><a onclick="javascript:document.getElementById('error_<?php echo $i; ?>').style.display='block';"><?php echo $error->message; ?></a><br /><div id="error_<?php echo $i; ?>" style="display:none;"><code><pre><?php echo htmlspecialchars(print_r($error, true)); ?></pre></code></div><?php
}

?>