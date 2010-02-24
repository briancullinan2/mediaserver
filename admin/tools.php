<?php

// Variables Used:
//  tools[], tool_names[], tool_paths[], tool_descs[], $error

// preffered order is a list for which order the tools should be arranged in, this is completely optional and makes the toolset a little more context aware
$preffered_order = array('Site Information', 'Log Parser', 'Ascii File Names', 'Excessive Underscores and Periods');

// each tool prints a very simple structure, the template loaded at the bottom is responsibile for 
//    manipulating that structure as well as fitting it in to the rest of the template
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// load each tool
// get directory listing
$files = fs_file::get(array('dir' => LOCAL_ROOT . 'admin' . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR, 'limit' => 32000), $count, $error, true);
if(!is_array($files))
{
	// report some error that there are no tools available
	$error = 'No tools available, either there is a problem with the installation, or the tools have been removed.';
}

// set up variables for tools to use
// tools must submit a name, path and a description to show that they conform to the tool specifications
// ----------- SEE STATISTICS FOR MORE INFORMATION ON CREATING TOOLS
$tool_names = array();
$tool_paths = array();
$tool_descs = array();

// this will be the output array from each tool
$tools = array();

// create output buffer for each tool
ob_start();

$last_count = 0;
foreach($files as $i => $file)
{
	// include tool file
	include @$file['Filepath'];
	
	// check for validity of tool, make sure stuff was added to name, path, and descs
	if(count($tool_names) - $last_count == count($tool_paths) - $last_count && count($tool_paths) - $last_count == count($tool_descs) - $last_count && count($tool_names) > $last_count)
	{
		// tools are ok
		$last_count = count($tool_names);
	}
	else
	{
		// remove all the tool entries for the last load and report an error with the tool
		if(count($tool_names) > $last_count)
		{
			for($i = 0; $i < count($tool_names - $last_count); $i++)
			{
				unset($tool_names[count($tool_names)-1]);
			}
		}
		if(count($tool_paths) > $last_count)
		{
			for($i = 0; $i < count($tool_paths - $last_count); $i++)
			{
				unset($tool_paths[count($tool_paths)-1]);
			}
		}
		if(count($tool_descs) > $last_count)
		{
			for($i = 0; $i < count($tool_descs - $last_count); $i++)
			{
				unset($tool_descs[count($tool_descs)-1]);
			}
		}
	}
}
ob_end_clean();

// tools will now be rearranged to match their preffered order
foreach($preffered_order as $i => $tool)
{
	$index = array_search($tool, $tool_names);
	if($index !== false)
	{
		$tool_names[-count($preffered_order) + $i] = $tool_names[$index];
		$tool_descs[-count($preffered_order) + $i] = $tool_descs[$index];
		$tool_paths[-count($preffered_order) + $i] = $tool_paths[$index];
		
		unset($tool_names[$index]);
		unset($tool_descs[$index]);
		unset($tool_paths[$index]);
	}
}
ksort($tool_names);
ksort($tool_descs);
ksort($tool_paths);

// do some preliminary reformatting
foreach($tools as $key => $result)
{
	$tools[$key] = preg_replace('/\<br \/\>[\r|\n| ]*\<b\>Warning\<\/b\>:  ([^\<]*) in (.*)\<br \/\>/i', '<warning label="$1">$2</warning>', $result);
}

// show template
if(realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__)
{
	if(getExt($GLOBALS['templates']['TEMPLATE_TOOLS']) == 'php')
		@include $GLOBALS['templates']['TEMPLATE_TOOLS'];
	else
	{
		header('Content-Type: ' . getMime($GLOBALS['templates']['TEMPLATE_TOOLS']));
		$GLOBALS['smarty']->display($GLOBALS['templates']['TEMPLATE_TOOLS']);
	}
}

?>