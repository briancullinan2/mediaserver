<?php

// handles the watch tables

// Variables Used:
//  watched, ignored, error
// Shared Variables:

define('WATCH_PRIV', 				10);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < WATCH_PRIV )
{
	// redirect to login page
	header('Location: ' . HTML_ROOT . 'plugins/login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . WATCH_PRIV);
	
	exit();
}

$error = '';

if( isset($_REQUEST['add']) && isset($_REQUEST['addpath']) && $_REQUEST['addpath'] != '' )
{
	if($_REQUEST['addpath'][0] != '!' && $_REQUEST['addpath'][0] != '^')
		$_REQUEST['addpath'] = '^' . $_REQUEST['addpath'];
	if(db_watch::handles($_REQUEST['addpath']))
	{
			// pass file to module
			db_watch::handle($_REQUEST['addpath']);
			
			unset($_REQUEST['addpath']);
	}
	else
	{
		$error = 'Invalid path.';
	}
}
elseif( isset($_REQUEST['remove']) && is_numeric($_REQUEST['watch']) )
{
	$GLOBALS['database']->query(array('DELETE' => db_watch::DATABASE, 'WHERE' => 'id=' . $_REQUEST['watch']), false);

	// clear post
	unset($_REQUEST['addpath']);
}

// reget the watched and ignored because they may have changed
$GLOBALS['ignored'] = db_watch::get(array('search_Filepath' => '/^!/'), $count, $error);
$GLOBALS['watched'] = db_watch::get(array('search_Filepath' => '/^\\^/'), $count, $error);
$GLOBALS['watched'][] = array('id' => 0, 'Filepath' => str_replace('\\', '/', LOCAL_USERS));

// assign variables for a smarty template to use
$GLOBALS['smarty']->assign('watched', $GLOBALS['watched']);

$GLOBALS['smarty']->assign('ignored', $GLOBALS['ignored']);

$GLOBALS['smarty']->assign('error', $error);

// show watch template
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_WATCH']) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_WATCH'];
	else
	{
		header('Content-Type: ' . getMime($GLOBALS['templates']['TEMPLATE_WATCH']));
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_WATCH']);
	}
}

?>