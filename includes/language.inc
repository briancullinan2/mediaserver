<?php


function register_language()
{
	return array(
		'name' => 'Language/Locale',
		'description' => 'Language settings and caching options.',
		'privilage' => 1,
		'settings' => 'language',
		'depends_on' => array('database', 'curl_installed'), /* because there is no other way to store
			 all the needed translations by the end of the script becaus the
			  session is closed to allow for the template stuff to not be held up 
			  TODO: find a way, make page javascript request the translation
			  TODO: no javascript? provide [t] links to bookmarks at the bottom of the page in an iframe
			  */
		'session' => array('language'),
		'output' => 'output_language',
		'internal' => true,
		'package' => 'core',
		'database' => array(
			'Language' => 'TEXT',
			'Translation' => 'TEXT',
			'Filepath' => 'TEXT',
		),
	);
}

// space hold function for table
function handles_language()
{
	return false;
}

function menu_language()
{
	return array(
		'translations_only' => array(
			'callback' => 'output_translations',
			'type' => MENU_CALLBACK,
		),
	);
}

/**
 * Implementation of setup
 * @ingroup setup
 */
function setup_language()
{
	global $LANG_CODE, $LANG_ISO;
	
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
	
	define('T_IN_ATTRIBUTE', 32);
	define('T_NO_SPAN', 64);
	
	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SupportedLanguages.php';
	
	//include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Translator.php';

	// load language from file and only translate new stuff
	$GLOBALS['language_queue'] = array();
	
	// get language cache from database
	$translations = db_assoc('SELECT * FROM language WHERE Language=? OR Language="en"', array(validate($_REQUEST, 'language')));
	
	// reorganize results
	$GLOBALS['language_cache'] = array();
	foreach($translations as $i => $translation)
	{
		if(!isset($GLOBALS['language_cache'][$translation['Filepath']]) || $translation['Language'] == validate($_REQUEST, 'language'))
			$GLOBALS['language_cache'][$translation['Filepath']] = new translation($translation['Filepath'], $translation['Language'], $translation['Translation'], true);
	}
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
	if(($language = session('language')))
		return $language;
	
	// guess the preffered language specified by request headers
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	{
		$lang = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$lang_codes = explode(',', $lang[0]);
		if(isset($lang_code[1]))
			return $lang_code[1];
	}
	
	return 'en';
}

function validate_to_translate($request)
{
	if(isset($request['to_translate']))
	{
		if(is_string($request['to_translate']))
			$request['to_translate'] = explode(';', $request['to_translate']);
		// that's all the validation needed because it is parameterized below
		if(is_array($request['to_translate']))
		{
			$request['to_translate'] = array_values(array_unique($request['to_translate']));
			if($key = array_search('', $request['to_translate']))
				unset($request[$key]);
			
			return $request['to_translate'];
		}
	}
}

/**
 * Implementation of session
 */
function session_language($request)
{
	// remove old language information
	$_SESSION['language_queue'] = array();
	return $request['language'];
}

function settings_language()
{
	return array();
}

// requires at least 2 parameters
function lang($text, $cache_id)
{
	$args = func_get_args();
	$cache_id = array_pop($args);
	$language = validate($_REQUEST, 'language');
	
	$return = array();
	
	// process arguments
	foreach($args as $i => $arg)
	{
		if(is_a($arg, 'translation'))
			// already a lang object
			$return[] = $arg;
		
		if(is_string($arg))
			// check if translation is in the cache
			if(isset($GLOBALS['language_cache'][$cache_id]) && $GLOBALS['language_cache'][$cache_id]->language == $language)
				$return[] = $GLOBALS['language_cache'][$cache_id];
			// set language to NULL to replace with javascript
			else
				$return[] = new translation($cache_id, NULL, $text);
		
		// process T_IN_ATTRIBUTE option that allows for placing lang inside value="" for buttons and stuff
		if($arg === T_IN_ATTRIBUTE && is_object($return[count($return)-1]))
		{
			if(isset($args[$i+1]) && is_array($args[$i+1]))
				$return[count($return)-1]->in_attribute = $args[$i+1];
			else
				$return[count($return)-1]->in_attribute = true;
		}
		
		// this tells the translation opbjext not to output a surrounding <span> element, this is useful for placing language inside javascript, but it cannot update with the page
		if($arg === T_NO_SPAN && is_object($return[count($return)-1]))
			$return[count($return)-1]->style = '';
	}
	// if the language cache isn't set then not even english is in the database
	// the translation is not in the database
	if(!isset($GLOBALS['language_cache'][$cache_id]))
		$GLOBALS['language_queue'][] = new translation($cache_id, 'en', $text);
	
	// if no translation is available return the input text and it is queued up
	if(count($return) == 0)
		return;
	elseif(count($return) == 1)
		return $return[0];
	else
		return $return;
}

function end_language()
{
	// store all remaining translations in the database
	$insert = array();
	foreach($GLOBALS['language_queue'] as $i => $translation)
	{
		$insert[] = $translation->id;
		$insert[] = $translation->translation;
		$insert[] = $translation->language;
	}

	$result = db_query('INSERT INTO language (Filepath, Translation, Language) VALUES (' . implode('),(', array_fill(0, count($insert)/3, '?,?,?')) . ')', $insert);
}

function translate()
{
	// translate everything in queue and store
}

function output_language($request)
{
	register_output_vars('languages', $GLOBALS['LANG_ISO']);
	
	$request['language'] = validate($request, 'language');
	
	// always display selected language
	register_output_vars('language', $request['language']);
}

function sql_to_translate($translations)
{
	return 'Filepath=' . implode(' OR Filepath=', array_fill(0, count($translations), '?'));
}

function output_translations($request)
{
	// get needed translations from session
	$request['to_translate'] = validate($request, 'to_translate');
	$request['language'] = validate($request, 'language');
	
	if(isset($request['to_translate']))
	{
		// select english first
		$to_translate = db_assoc('SELECT * FROM language WHERE (' . sql_to_translate($request['to_translate']) . ') AND Language="en" LIMIT ' . count($request['to_translate']), $request['to_translate']);
		
		// try to do translations otherwise use english text
		$queries = array();
		$keys = array();
		$translations = array();
		foreach($to_translate as $i => $translation)
		{
			if(isset($GLOBALS['language_cache'][$translation['Filepath']]) && $GLOBALS['language_cache'][$translation['Filepath']]->language == $request['language'])
			{
				$translations[$translation['Filepath']] = $GLOBALS['language_cache'][$translation['Filepath']]->translation;
				continue;
			}

			$queries[] = 'q=' . $translation['Translation'];
			$keys[] = $translation['Filepath'];
		}
		
		$results = fetch('http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair=en%7C' . $request['language'] . '&callback=foo&context=bar', implode('&', $queries));
		$phrases = explode('translatedText":"', $results['content']);
		// discard header to the JSON document
		array_shift($phrases);
		// loop through each query and collect and store translations
		foreach($phrases as $i => $phrase)
		{
			$phrase = explode('"}', $phrase);
			$translations[$keys[$i]] = $phrase[0];
		}
	
		// store in database
		$insert = array();
		foreach($translations as $cache_id => $translation)
		{
			if(!isset($GLOBALS['language_cache'][$cache_id]) || $GLOBALS['language_cache'][$cache_id]->language != $request['language'])
			{
				$insert[] = $cache_id;
				$insert[] = $translation;
				$insert[] = $request['language'];
			}
			print '<span class="' . machine($cache_id) . '" style="background-color:#CC3;border:2px solid #C00;">' . $translation . '</span>';
		}
		
		$result = db_query('INSERT INTO language (Filepath, Translation, Language) VALUES (' . implode('),(', array_fill(0, count($insert)/3, '?,?,?')) . ')', $insert);
	}
}

function theme__language_block()
{
	?><form id="language-block" action="<?php print $GLOBALS['output']['get']; ?>" method="get">
	<?php print lang('Language', 'language label'); ?>: <select name="language">
	<?php
	foreach($GLOBALS['output']['languages'] as $code => $language)
	{
		?><option value="<?php print $code; ?>" <?php print ($GLOBALS['output']['language'] == $code)?'selected="selected"':''; ?>><?php print $language; ?></option><?php
	}
	
	?></select><?php print lang('Go', T_IN_ATTRIBUTE, array('input','type'=>'submit','value'=>'%s'), 'translate go'); ?></form><div id="tmp_translations"></div><?php
}

function theme__language_footer()
{
	?><script type="text/javascript"><?php
	// do stuff for no JS here
	foreach(translation::$javascript_queue as $js_id => $cache_id)
	{
		print '$replace["' . $js_id . '"]="' . $cache_id . '";';
	}
	
	?></script><?php
}

function theme__language_header()
{
	if(is_module('language')) 
	{
		?>
		<script type="text/javascript">
		var $replace = [];
		$(document).ready(function() {
			var translate = {no_redirect:true};
			for(var i in $replace)
			{
				translate['to_translate[' + i + ']'] = $replace[i];
			}
			$.post('<?php print url('translations_only/', true); ?>', translate, function(data, status, xhr){
				
				$('#tmp_translations').html(data);
				
				for(var i in $replace)
				{
					var cache_id = $replace[i].replace(/[^a-z0-9_\[\]]/gi, '_');
					if($('#t_' + i).is('span'))
						$('#t_' + i).replaceWith($('#tmp_translations > span.' + cache_id).html());
					else
						$('#t_' + i).val($('#tmp_translations > span.' + cache_id).html());
				}
				
				$('#tmp_translations').remove();
			}, 'html');
		});
		</script>
		<?php
	}
}


class translation
{
	var $id = '';
	var $language = '';
	var $translation = NULL;
	var $style = ' style="background-color:#CC3;border:2px solid #C00;"';
	var $js_ids = array();
	var $in_attribute = false;
	static $javascript_queue = array();
	
	function translation($id, $language, $translation = NULL)
	{
		$this->id = $id;
		$this->language = $language;
		$this->translation = $translation;
	}
	
	function __toString()
	{
		do
		{
			$js_id = md5(microtime());
		} while (in_array($js_id, $this->js_ids));
		$this->js_ids[] = $js_id;
	
		if($this->in_attribute === true)
		{
			if($this->language == NULL)
				translation::$javascript_queue[$js_id] = $this->id;
			return $this->translation . '" id="t_' . $js_id . '"' . $this->style;
		}
		elseif(is_array($this->in_attribute))
		{
			if($this->language == NULL)
				translation::$javascript_queue[$js_id] = $this->id;
			$return = '<' . $this->in_attribute[0];
			foreach($this->in_attribute as $name => $value)
			{
				if(strpos($value, '%s') !== false)
					$return .= ' ' . $name . '="' . sprintf($value, $this->translation) . '"';
				elseif($name)
					$return .= ' ' . $name . '="' . $value . '"';
			}
			return $return . '" id="t_' . $js_id . '"' . $this->style . ' />';
		}
		elseif($this->language != NULL)
			return '<span' . $this->style . '>' . $this->translation . '</span>';
		else
			// this is where we add a template call to insert the jquery
			return '<span' . $this->style . ' id="t_' . $js_id . '">' . $this->translation . '</span><script type="text/javascript" style="display:none;">$replace["' . $js_id . '"]="' . $this->id . '";</script>';
	}
}


