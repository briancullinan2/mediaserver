<?php

/** Convert the input text to a link provided in the next spot after translating */
define('T_URL', 2);
/** Skip translating this element */
define('T_SKIP', 4);
/** 
 * Replace the text in the middle of a sentance for contextual translation then use the original text
 * like 'path name' would create the wanted context, but then replace path name with the actual path
 */
define('T_REPLACE', 8);
/** Create new textual context but do it in the same lang call and group it all together */
define('T_NEW_CONTEXT', 16);

/**
 * Implementation of setup
 * @ingroup setup
 */
function setup_lang()
{
	global $LANG_CODE;
	
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'SupportedLanguages.php';
	
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'Translator.php';

	if(!isset($_SESSION['translated'])) $_SESSION['translated'] = array();

	// load language from file and only translate new stuff

	register_output_vars('languages', $LANG_ISO);
}

/**
 * Implementation of register
 * @ingroup register
 */
function register_lang()
{
	return array(
		'name' => lang('language title', 'Language Select'),
		'description' => lang('language description', 'Allow users to choose the language.'),
		'privilage' => 1,
		'path' => __FILE__,
		'session' => array('language'),
		'output' => true,
		'package' => 'core',
	);
}

/**
 * Implementation of setting
 * Allows the administrator to set a language for himself
 * @ingroup setting
 */
function setting_lang($settings)
{
	if(isset($settings['language']) && in_array($settings['language'], array_keys($GLOBALS['LANG_CODE'])))
		return $settings['language'];
	return 'en';
}

/**
 * Implementation of validate, allows the user to input a language
 * @ingroup validate
 * @return english by default
 */
function validate_language($request)
{
	// get the language specified explicitly in the request
	if(isset($request['language']) && in_array($request['language'], array_keys($GLOBALS['LANG_CODE'])))
		return $request['language'];
		
	// check language specified in the session
	if(isset($_SESSION['language']) && $_SESSION['language'] != '')
		return $_SESSION['language'];
	
	// guess the preffered language specified by request headers
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	{
		$lang = split(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$lang_codes = split(',', $lang[0]);
		if(isset($lang_code[1]))
			return $lang_code[1];
	}
	
	return 'en';
}

/**
 * Implementation of session
 */
function session_lang($request)
{
	// remove old language information
	$_SESSION['translated'] = array();
	return $request['language'];
}

/**
 * Do all caching of languages, and returning of translation
 */
function lang($keys, $text)
{
	$args = func_get_args();
	
	if(!isset($GLOBALS['language_buffer'][$keys]))
		$GLOBALS['language_buffer'][$keys] = $text;
	else
		raise_error('Warning: The input language translation \'' . $keys . '\' has already been register for translation.', E_DEBUG);
		
	// use language saved in sesssion, make sure it has been set up before calling this
	if(isset($GLOBALS['LANG_CODE']) && validate($_REQUEST, 'language') == 'en')
	{
		return $text;
	}
	
	// get language cache
	if(isset($_SESSION['translated'][$keys]))
		return $_SESSION['translated'][$keys];
	else
		return $text;
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_lang($request)
{
	$request['language'] = validate($request, 'language');
	
	// always display selected language
	register_output_vars('language', $request['language']);
}


function theme_lang_block()
{
	?><form id="language-block" action="<?php print $GLOBALS['templates']['html']['get']; ?>" method="get">
	Language: <select name="language">
	<?php
	foreach($GLOBALS['templates']['vars']['languages'] as $code => $language)
	{
		?><option value="<?php print $code; ?>" <?php print ($GLOBALS['templates']['vars']['language'] == $code)?'selected="selected"':''; ?>><?php print $language; ?></option><?php
	}
	
	?></select><input type="submit" value="Go" /></form><?php
}
