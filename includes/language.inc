<?php

// requires at least 2 parameters
function lang($text, $cache_id)
{
	$args = func_get_args();
	
	if(isset($GLOBALS['language_cache'][$catch]))
	{
		return $GLOBALS['language_cache'][$catch];
	}
	else
	{
		// add to queue for lookup or download
		array_push($GLOBALS['language_queue'], $text);
		
		// this is where we add a template call to insert the jquery
		return '<span>' . $text . '<script type="text/javascript">replace[]=$(this);</script></span>';
	}
	
	return $text;
}