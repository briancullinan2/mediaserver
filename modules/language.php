<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_language()
{
	return array(
		'name' => 'Language Select',
		'description' => lang('language description', 'Allow users to choose the language.'),
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('language'),
		'notemplate' => true,
	);
}

function setting_language($settings)
{
	if(isset($settings['language']) && in_array($settings['language'], array('english')))
		return $settings['language'];
	return 'english';
}

function lang($keys, $text)
{
	$GLOBALS['settings']['language'] = setting_language($GLOBALS['settings']);
	
	// use language saved in sesssion
	if($GLOBALS['settings']['language'] == 'english')
	{
		return $text;
	}
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_language($request)
{
}