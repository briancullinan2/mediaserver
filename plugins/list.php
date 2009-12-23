<?php

// query the database based on search stored in session
define('LIST_PRIV', 				1);

// load template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// make sure user in logged in
if( $_SESSION['privilage'] < LIST_PRIV )
{
	// redirect to login page
	header('Location: /' . HTML_PLUGINS . 'login.php?return=' . $_SERVER['REQUEST_URI'] . '&required_priv=' . LIST_PRIV);
	
	exit();
}

include_once LOCAL_ROOT . 'plugins' . DIRECTORY_SEPARATOR . 'type.php';

// get these listed items over the ones saved in the session!
if(!isset($_REQUEST['id']) && isset($_SESSION['selected']))
{
	$_REQUEST['item'] = join(',', $_SESSION['selected']);
}

// if none of the following is defined, tokenize and search
if(!isset($_REQUEST['id']) && !isset($_REQUEST['item']) && !isset($_REQUEST['on']) && !isset($_REQUEST['file']) && !isset($_REQUEST['search']))
{
	$request_tokens = tokenize(join('&', $_REQUEST));
	$_REQUEST['search'] = join(' ', $request_tokens['All']);
}

// add category
if(!isset($_REQUEST['cat']) || !in_array($_REQUEST['cat'], $GLOBALS['modules']) || constant($_REQUEST['cat'] . '::INTERNAL') == true)
	$_REQUEST['cat'] = USE_DATABASE?'db_file':'fs_file';

// select type of output
if(!isset($_REQUEST['list']) || !isset($types[$_REQUEST['list']]))
	$_REQUEST['list'] = 'rss';

// make select call
$files = call_user_func_array($_REQUEST['cat'] . '::get', array($_REQUEST, &$count, &$error));

// the ids module will do the replacement of the ids
if(count($files) > 0)
	$files = db_ids::get(array('cat' => $_REQUEST['cat']), $tmp_count, $tmp_error, $files);

// get all the other information from other modules
foreach($files as $index => $file)
{
	$tmp_request = array();
	$tmp_request['file'] = $file['Filepath'];

	// merge with tmp_request to look up more information
	$tmp_request = array_merge(array_intersect_key($file, getIDKeys()), $tmp_request);
	
	// merge all the other information to each file
	foreach($GLOBALS['modules'] as $i => $module)
	{
		if($module != $_REQUEST['cat'] && constant($module . '::INTERNAL') == false && call_user_func_array($module . '::handles', array($file['Filepath'])))
		{
			$return = call_user_func_array($module . '::get', array($tmp_request, &$tmp_count, &$tmp_error));
			if(isset($return[0])) $files[$index] = array_merge($return[0], $files[$index]);
		}
	}
}

$GLOBALS['smarty']->assign('files', $files);


header('Cache-Control: no-cache');
if($_REQUEST['list'] == 'rss')
{
	header('Content-Type: application/rss+xml');
}
elseif($_REQUEST['list'] == 'wpl')
{
	header('Content-Type: application/vnd.ms-wpl');
}
else
{
	header('Content-Type: ' . getMime($types[$_REQUEST['list']]['file']));
}

// output entire template
// if this is the file being called as opposed to an include
// this is how we implement FRAME functionality! very tricky
// this makes the caller in charge of using the output whereever it wants
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($types[$_REQUEST['list']]['file']) == 'php')
		@include $types[$_REQUEST['list']]['file'];
	else
		$GLOBALS['smarty']->display($types[$_REQUEST['list']]['file']);
}
	


?>