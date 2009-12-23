<?php

// search form for selecting files from the database
define('SEARCH_PRIV', 				1);

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < SEARCH_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . SEARCH_PRIV);
	
	exit();
}

// process search query and save in session
if(isset($_REQUEST['search']))
{

	if(isset($_POST['clear']) || isset($_GET['clear']))
		unset($_REQUEST['search']);

	// store this query in the session
	$_SESSION['search'] = array();
	$_SESSION['search']['cat'] = @$_REQUEST['cat'];
	$_SESSION['search']['search'] = @$_REQUEST['search'];
	$_SESSION['search']['lim'] = @$_REQUEST['lim'];
	$_SESSION['search']['dir'] = @$_REQUEST['dir'];
	$_SESSION['search']['order_by'] = @$_REQUEST['order_by'];
	
	// redirect back to self to clear post
	if(isset($_POST['search']))
	{
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit();
	}

}

// set the search vars in the template
if(isset($_SESSION['search']))
	$GLOBALS['smarty']->assign('search', $_SESSION['search']);

// parse out internal modules
$out_modules = array();
foreach($GLOBALS['modules'] as $i => $module)
{
	if(constant($module . '::INTERNAL') == false)
	{
		$out_modules[$module] = constant($module . '::NAME');
	}
}
$GLOBALS['smarty']->assign('modules', $out_modules);

if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_SEARCH']) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_SEARCH'];
	else
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_SEARCH']);
}


?>