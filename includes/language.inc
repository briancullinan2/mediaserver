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
		// always validate language
		'validate' => 'language',
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
	$GLOBALS['LANG_CODE'] = $LANG_CODE;
	$GLOBALS['LANG_ISO'] = $LANG_ISO;
	
	//include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Translator.php';
	
	$GLOBALS['language_queue'] = array();

	if(setting('debug_mode'))
		translation::$style = ' style="background-color:#CC3;border:1px solid #C00;"';
}

function load_language_cache($prefix = NULL, $lang = NULL)
{
	// load language from file and only translate new stuff
	$GLOBALS['language_cache'] = array();
	
	// languages hasn't been loaded yet
	if(!isset($GLOBALS['LANG_CODE']))
	{
		raise_error('load_language_cache() being called before setup_language().', E_DEBUG);
		
		setup_language();
	}
	// get language cache from database
	if($lang == NULL)
		$lang = validate($_REQUEST, 'language');
	else
		$lang = validate(array('language' => $lang), 'language');
	
	if($prefix == NULL)
		$translations = db_assoc('SELECT * FROM language WHERE Language=? OR Language="en"', array($lang));
	else
		$translations = db_assoc('SELECT * FROM language WHERE (Language=? OR Language="en") AND SUBSTR(Filepath, 1, ' . strlen($prefix) . ')=?', array($lang, $prefix));
		
	// reorganize results
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
		
	// check in path
	if(isset($request['path_info']) && preg_match('/^(' . implode('|', array_keys($GLOBALS['LANG_ISO'])) . ')($|\/.*$)/i', $request['path_info'], $matches))
		return strtolower($matches[1]);
	
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
				unset($request['to_translate'][$key]);
			
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
	
	if(!isset($GLOBALS['language_cache']))
		load_language_cache(NULL, $language);
	
	$return = array();
	
	// process arguments
	foreach($args as $i => $arg)
	{
		if($arg instanceof translation)
			// already a lang object
			$return[] = $arg;
		
		if(is_string($arg))
			// check if translation is in the cache
			if(isset($GLOBALS['language_cache'][$cache_id]) && $GLOBALS['language_cache'][$cache_id]->language == $language)
				$return[] = $GLOBALS['language_cache'][$cache_id];
			// set language to NULL to replace with javascript
			else
				$return[] = new translation($cache_id, ($language == 'en')?'en':NULL, $text);
		
		// process T_IN_ATTRIBUTE option that allows for placing lang inside value="" for buttons and stuff
		if($arg === T_IN_ATTRIBUTE && is_object($return[count($return)-1]))
		{
			if(isset($args[$i+1]) && is_array($args[$i+1]))
				$return[count($return)-1]->in_attribute = $args[$i+1];
			else
				$return[count($return)-1]->in_attribute = true;
		}
		
		// this tells the translation objext not to output a surrounding <span> element, this is useful for placing language inside javascript, but it cannot update with the page
		if($arg === T_NO_SPAN && is_object($return[count($return)-1]))
		{
			$return[count($return)-1]->no_span = true;
			if(isset($args[$i+1]) && is_array($args[$i+1]))
				$return[count($return)-1]->in_attribute = $args[$i+1];
			else
				$return[count($return)-1]->in_attribute = true;
		}
	}
	// if the language cache isn't set then not even english is in the database
	// the translation is not in the database
	if(!isset($GLOBALS['language_cache'][$cache_id]))
	{
		$GLOBALS['language_cache'][$cache_id] = new translation($cache_id, 'en', $text);
		$GLOBALS['language_queue'][] = &$GLOBALS['language_cache'][$cache_id];
	}
	
	// if no translation is available return the input text and it is queued up
	if(!count($return))
		return;
	elseif(count($return) < 2)
		return $return[0];
	else
		return $return;
}

function end_language()
{
	if(count($GLOBALS['language_queue']))
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
}

function configure_language($settings)
{
	$options = array();
	
	$options['language_cache'] = array(
		'name' => 'Clear language cache',
		'status' => '',
		'description' => array(
			'list' => array(
				'Clear the stored languages.  The translations will be regenerated the next time a user visits that page in the selected language.',
			),
		),
		'type' => 'submit',
		'value' => 'Clear now',
	);
	
	$options['default_language'] = array(
		'name' => 'Default language',
		'status' => '',
		'description' => array(
			'list' => array(
				'Select the default languages users will see when they visit your site.',
			),
		),
		'type' => 'select',
		'options' => array(),
	);
	
	$options['ajaxify'] = array(
		'name' => 'Use AJAX',
		'status' => '',
		'description' => array(
			'list' => array(
				'Translate the page with AJAX so the user doesn\'t have to refresh the page when they switch languages.',
			),
		),
		'type' => 'boolean',
		'options' => array('Turn AJAX on', 'Do not use AJAX'),
	);
	
	return array('language' => array(
		'name' => 'Languages',
		'type' => 'fieldset',
		'options' => $options
	));
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
	
	if(isset($request['to_translate']) && $request['language'] != 'en')
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

			$queries[] = $translation['Translation'];
			$keys[] = $translation['Filepath'];
		}
		
		$phrases = translate($queries, $request['language']);
		$translations = array_merge($translations, array_combine($keys, $phrases));
	
		// store in database
		if(!empty($keys))
		{
			$insert = array();
			foreach(array_combine($keys, $phrases) as $cache_id => $translation)
			{
				if(!isset($GLOBALS['language_cache'][$cache_id]) || $GLOBALS['language_cache'][$cache_id]->language != $request['language'])
				{
					$insert[] = $cache_id;
					$insert[] = $translation;
					$insert[] = $request['language'];
				}
				print '<span class="' . machine($cache_id) . '">' . $translation . '</span>';
			}
		}
		
		$result = db_query('INSERT INTO language (Filepath, Translation, Language) VALUES (' . implode('),(', array_fill(0, count($insert)/3, '?,?,?')) . ')', $insert);
	}
}


function utf8_html_decode($matches){
   return html_entity_decode('&#x'.$matches[1].';', ENT_QUOTES, 'UTF-8');
}

function unescapeUTF8EscapeSeq($str) {
   $str=preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'utf8_html_decode', $str);
   $str=html_entity_decode($str, ENT_QUOTES,"UTF-8");
   $str=stripslashes(trim($str));
   $str=str_replace('<s/>', " %s ", $str);
   $str=str_replace('</s>', " %s ", $str);
   //$str=str_replace('<br />',"\n", $str);
   return $str;
}

function translate($text,$lang_code,$source_lang='en'){
   if(is_array($text)){
      $is_array=true;
      //If the array is larger than 100 items then google may reject it.
      if(sizeof($text)>100){
		  $length = 0;
		  $slice_q = array();
         foreach($text as $i => $translation){
			if(count($slice_q) == 100 || ($length + strlen($translation) > 5000))
			{
				$tran=translate($slice_q,$lang_code,$source_lang);
	
				foreach($tran as $t){
				   $ret[]=$t;
				}
				
				$slice_q = array();
				$length = 0;
			}
			
			$length += strlen($translation);
			$slice_q[] = $translation;
         }
		 
		 // translate anything left in slice_q
		 if(count($slice_q))
		 {
			$tran=translate($slice_q,$lang_code,$source_lang);

			foreach($tran as $t){
			   $ret[]=$t;
			}
		 }
		 
         return $ret;
      }
      /*$dex=0;
      foreach($text as $dex=>$t){
         $e=execute("select translated_text from translate_cache where origonal_text=? and origonal_lang=? and translated_lang=?",array($t,$source_lang,$lang_code));
         $r=$e->FetchRow();
         if($r){
            $cache[$dex]=$r[0];
            $text[$dex]='';
         }else{
            execute("insert into translate_cache (origonal_text,origonal_lang,translated_lang) values (?,?.?)",array($t,$source_lang,$lang_code));
         }
      }*/
      $post=implode("&q=", array_map('urlencode', $text));
      $post="q=".$post;
   }else{
      $post="q=".urlencode($text);
   }
   
   $post=str_replace(array("\r\n", "\n"),"<br />",$post);
   $post=str_replace("%s","<s/>",$post);

   $url="http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair=$source_lang%7C$lang_code&callback=foo&context=bar";
   $data = fetch($url,$post);
   if(strpos($data['content'], 'translatedText') === false)
      raise_error('Tranlation failed: ' . $data['content'], E_DEBUG);
   $data = explode('translatedText":"', $data['content']);

   $translated = array();
   if($is_array){
      array_shift($data);
      foreach($data as $d){
         $t=explode('"},',$d);
         $translated[]=unescapeUTF8EscapeSeq($t[0]);
      }
      if(sizeof($translated)!=sizeof($text)){
         die(sizeof($translated)."!=".sizeof($text));
      }
      //Check for translation errors,  do not leave blank on error.
      for($x=sizeof($translated)-1;$x>=0;$x--){
         $translated[$x]=trim($translated[$x]);
         if(!$translated[$x]){
            if($text[$x]){
              $translated[$x]=$text[$x];
            }else{
              $translated[$x]=$cache[$x];
            }
         }
      }
   }else{
      $data=explode('"},',$data[1]);
      $translated=unescapeUTF8EscapeSeq($data[0]);
      if(!$translated){
         $translated=$text;
      }
   }
   return $translated;
}

function theme__language_block()
{
	?><form id="language-block" action="<?php print $GLOBALS['output']['get']; ?>" method="get">
	<?php print lang('Language', 'language label'); ?>: <select id="language_select" name="language">
	<?php
	foreach($GLOBALS['output']['languages'] as $code => $language)
	{
		?><option value="<?php print $code; ?>" <?php print ($GLOBALS['output']['language'] == $code)?'selected="selected"':''; ?>><?php print $language; ?></option><?php
	}
	
	?></select><?php print lang('Go', T_IN_ATTRIBUTE, array('input','type'=>'submit','value'=>'%s'), 'translate go'); ?>
	</form>
	<script type="text/javascript">
		$('#language_select').change(function () { $('#language-block').submit(); }).next('input').hide();
	</script>
	<div id="tmp_translations" style="display:none;"></div>
	<?php
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
			var has_translations = false;
			for(var i in $replace)
			{
				translate['to_translate[' + i + ']'] = $replace[i];
				has_translations = true;
			}
			if(has_translations)
				$.post('<?php print url('translations_only/', true); ?>', translate, function(data, status, xhr){
					
					$('#tmp_translations').html(data);
					
					for(var i in $replace)
					{
						var cache_id = $replace[i].replace(/[^a-z0-9_\[\]]/gi, '_');
						if($('#tmp_translations > span.' + cache_id).length > 0)
						{
							var isDefined = eval('(typeof $' + i + '==\'function\')');
							if(isDefined)
								eval('$' + i + '("' + $('#tmp_translations > span.' + cache_id).html() + '");');
							else if($('#' + i).is('span'))
								$('#' + i).replaceWith($('#tmp_translations > span.' + cache_id).html());
							else if($('#' + i).is('input'))
								$('#' + i).val($('#tmp_translations > span.' + cache_id).html());
							else
								$('#' + i).html($('#tmp_translations > span.' + cache_id).html());
						}
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
	var $js_ids = array();
	var $in_attribute = false;
	var $no_span = false;
	
	static $style = '';
	static $javascript_queue = array();
	
	function translation($id, $language, $translation = NULL)
	{
		$this->id = $id;
		$this->language = $language;
		$this->translation = $translation;
	}
	
	function __toString()
	{
		// if the id attribute is set use that for the id instead
		if(isset($this->in_attribute['id']))
			$js_id = $this->in_attribute['id'];
		else
		{
			do
			{
				$js_id = md5(microtime());
			} while (in_array($js_id, $this->js_ids));
			$this->js_ids[] = $js_id;
		}
		
		if($this->language == NULL)
			translation::$javascript_queue[$js_id] = $this->id;

		// if the style is equal to false this means do not insert <span>
		if($this->no_span)
		{
			// we still need to request the translation
			return escape($this->translation);
		}
		
		// if attribute is not an array or if there is only and id element do this
		elseif($this->in_attribute === true || (count($this->in_attribute) === 1 && isset($this->in_attribute['id'])))
		{
			return escape($this->translation) . '" id="' . $js_id . '"' . translation::$style;
		}
		
		// contruct the element from the in_attribute property
		elseif(is_array($this->in_attribute))
		{
			$return = '<' . $this->in_attribute[0];
			foreach($this->in_attribute as $name => $value)
			{
				if(strpos($value, '%s') !== false)
					$return .= ' ' . $name . '="' . sprintf($value, escape($this->translation)) . '"';
				elseif($name)
					$return .= ' ' . $name . '="' . $value . '"';
			}
			if(isset($this->in_attribute['id']))
				return $return . '"' . translation::$style . ' />';
			else
				return $return . '" id="' . $js_id . '"' . translation::$style . ' />';
		}
		
		// if the language is set, then the text is already translated
		elseif($this->language != NULL)
			return '<span' . translation::$style . '>' . escape($this->translation) . '</span>';
		
		// translate the text in-line!
		else
			// this is where we add a template call to insert the jquery
			return '<span' . translation::$style . ' id="' . $js_id . '">' . escape($this->translation) . '</span>';
	}
}


