<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_image_browser()
{
	return array(
		'name' => 'Browser Images',
		'description' => 'Wrapper for all the images that are compatible with displaying in a browser.',
		'wrapper' => 'image',
	);
}

/** 
 * Implementation of handles
 * @ingroup handles
 */
function handles_image_browser($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
			
	// get file extension
	$type = getExtType($file);
	$ext = getExt(basename($file));
	
	if( $type == 'image' )
	{
		switch($ext)
		{
			case 'bmp':
			case 'gif':
			case 'png':
			case 'jpeg':
			case 'jpg':
				return true;
			default:
				return false;
		}
	}
	
	return false;

}

/** 
 * Implementation of output_handler
 * @ingroup output_handler
 */
function output_image_browser($file)
{
	// check to make sure file is valid
	header('Content-Disposition: ');
	return output_files($file);
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_image_browser($request, &$count)
{
	// change the cat to the table we want to use
	$request['cat'] = validate(array('cat' => 'image'), 'cat');
	
	if(isset($request['file']))
		return array(); // nothing to add
		
	return get_files($request, $count, 'image');
}

