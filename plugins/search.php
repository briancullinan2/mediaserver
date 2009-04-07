<?php

// search form for selecting files from the database


// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load template to create output
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty = new Smarty();
$smarty->compile_dir = LOCAL_ROOT . 'templates_c' . DIRECTORY_SEPARATOR;
	
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

// get all columns from every module
$columns = getAllColumns();
$smarty->assign('columns', $columns);

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
	$smarty->assign('search', $_SESSION['search']);

// parse out internal modules
$out_modules = array();
foreach($GLOBALS['modules'] as $i => $module)
{
	if(constant($module . '::INTERNAL') == false)
	{
		$out_modules[$module] = constant($module . '::NAME');
	}
}
$smarty->assign('modules', $out_modules);

$smarty->assign('templates', $GLOBALS['templates']);
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
	$smarty->display($GLOBALS['templates']['TEMPLATE_SEARCH']);



?>