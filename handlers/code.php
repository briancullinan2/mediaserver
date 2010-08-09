<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_code()
{
	return array(
		'name' => 'Code',
		'description' => 'Highlight and store source code files for many languages.',
		'database' => array(
			'Words' 		=> 'TEXT',
			'HTML' 			=> 'TEXT',
			'LineCount' 	=> 'INT',
			'Language' 		=> 'TEXT',
			'Filepath' 		=> 'TEXT',
		),
		'depends on' => array('highlighter'),
		'settings' => array('highlighter'),
	);
}

/** 
 * Implementation of setup_handler
 * @ingroup setup_handler
 */
function setup_code()
{
	if(setting('highlighter') == 'geshi')
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'geshi' . DIRECTORY_SEPARATOR . 'geshi.php';
}

/**
 * Implementation of dependency
 * @ingroup dependency
 */
function dependency_highlighter($settings)
{
	// get the archiver it is set to
	$settings['highlighter'] = setting('highlighter');
	$settings['local_root'] = setting_local_root();

	// if that archiver is not installed, return false
	if($settings['highlighter'] == 'pear' && dependency('pear_installed') != false && include_path('Text/Highlighter.php') !== false)
		return true;
	elseif($settings['highlighter'] == 'gheshi' && 
		file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'geshi' . DIRECTORY_SEPARATOR . 'geshi.php'))
		return true;
	else
		return false;
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_code()
{
	$status = array();

	if(dependency('highlighter') != false)
	{
		$status['code'] = array(
			'name' => lang('code status title', 'Code'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('code status description', 'Code highlighting and searching is available.'),
				),
			),
			'value' => array(
				'text' => array(
					'Code highlighting available',
				),
			),
		);
	}
	else
	{
		$status['code'] = array(
			'name' => lang('code status title', 'Code'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('code status fail description', 'Code highlighting not available.'),
				),
			),
			'value' => array(
				'text' => array(
					'Code highlighting disabled',
				),
			),
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_code($settings, $request)
{
	$settings['highlighter'] = setting('highlighter');
	
	$options = array();
	
	if(dependency('highlighter'))
	{
		$options['highlighter'] = array(
			'name' => lang('highlighter title', 'Highlighter'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('highlighter description 1', 'This script comes equiped with 2 code highlighting tools.'),
					lang('highlighter description 2', 'PEAR::Text_Highlighter may be used, or the popular GeSHi highlighter.'),
				),
			),
			'type' => 'select',
			'value' => $settings['highlighter'],
			'options' => array(
				'pear' => 'PEAR Text_Highlighter Extension',
				'geshi' => 'GeSHi Highligher',
			),
		);
	}
	else
	{
		$options['highlighter'] = array(
			'name' => lang('highlighter title', 'Highlighter Not Installed'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('highlighter description fail 1', 'Either there is no highlighter installed, or the chosen highlighter is missing.'),
					lang('highlighter description fail 2', 'PEAR::Text_Highlighter may be used, or the popular GeSHi highlighter.'),
				),
			),
			/*'value' => array(
				'link' => array(
					'url' => 'http://qbnz.com/highlighter/',
					'text' => 'Get Geshi',
				),
			),*/
			'type' => 'select',
			'value' => $settings['highlighter'],
			'options' => array(
				'pear' => 'PEAR Text_Highlighter Extension',
				'geshi' => 'GeSHi Highligher',
			),
		);
	}
	
	return $options;
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return returns pear by default
 */
function setting_highlighter($settings)
{
	$settings['local_root'] = setting_local_root();
	if(isset($settings['highlighter']) && in_array($settings['highlighter'], array('pear', 'geshi')))
		return $settings['highlighter'];
	else
	{
		if(file_exists($settings['local_root'] . 'include' . DIRECTORY_SEPARATOR . 'geshi' . DIRECTORY_SEPARATOR . 'geshi.php'))
			return 'geshi';
		elseif(dependency('pear_installed') != false && include_path('Text/Highlighter.php') !== false)
			return 'pear';
		else
			return 'geshi';
	}
}

// read in code files and cache the hilighted version
//  use highlighting library from codepaster.com

/** 
 * Implementation of handles
 * Handles all text files, and all extensions supported by the get language function
 * @ingroup handles
 */
function handles_code($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	$type = getExtType($file);
	
	return ( code_get_language(basename($file)) || $type == 'text' );
}

/**
 * Helper function for db_code
 */
function code_get_language($file)
{
	switch($file)
	{
		case 'error.log':
		case 'access.log':
		case 'other_vhosts_access.log':
			return 'apache';
			break;
		case 'sources.list':
			return 'apt_sources';
			break;
		case 'robots.txt':
			return 'robots';
			break;
		case 'xorg.conf':
			return 'xorg_conf';
			break;
	}
	
	$ext_to_lang = array(
		// extension            //language
		'as'			=>		'actionscript',
		'abap'			=>		'abap',
		'a'			    =>		'ada',
		'applescript'	=>		'applescript',
		'x86'			=>		'asm',
		'asm'			=>		'asm',
		'm68k'			=>		'm68k',
		'pic16'			=>		'pic16',
		'z80'			=>		'z80',
		'log'			=>		'apache',
		'list'			=>		'apt_sources',
		'asp'			=>		'asp',
		'aspx'			=>		'asp',
		'aut'			=>		'autoit',
		'abnf'			=>		'bnf',
		'bnf'			=>		'bnf',
		'sh'			=>		'bash',
		'bash'			=>		'bash',
		'bgm'			=>		'basic4gl',
		'bb'			=>		'blitzbasic',
		'b'				=>		'bf',
		'bf'			=>		'bf',
		'c'				=>		'cpp',
		'h'				=>		'cpp',
		'cpp'			=>		'cpp',
		'cs'			=>		'csharp',
		'csharp'		=>		'csharp',
		'dcl'			=>		'caddcl',
		'lsp'			=>		'cadlisp',
		'cfdg'			=>		'cfdg',
		'cil'			=>		'cil',
		'cob'			=>		'cobol',
		'cbl'			=>		'cobol',
		'cobol'			=>		'cobol',
		'css'			=>		'css',
		'd'				=>		'd',
		'pas'			=>		'delphi',
		'dpr'			=>		'delphi',
		'patch'			=>		'diff',
		'diff'			=>		'diff',
		'batch'			=>		'dos',
		'dot'			=>		'dot',
		'eff'			=>		'eiffel',
		'f77'			=>		'fortran',
		'f95'			=>		'fortran',
		'ftn'			=>		'fortran',
		'4gl'			=>		'genero',
		'fbl'			=>		'freebasic',
		'mo'			=>		'gettext',
		'glsl'			=>		'glsl',
		'gml'			=>		'gml',
		'plt'			=>		'gnuplot',
		'groovy'		=>		'groovy',
		'gs'			=>		'haskell',
		'hs'			=>		'haskell',
		'hq9+'			=>		'hq9plus',
		'htm'			=>		'html',
		'html'			=>		'html',
		'ini'			=>		'ini',
		'ino'			=>		'inno',
		'myp'			=>		'inno',
		'i'				=>		'intercal',
		'io'			=>		'io',
		'java'			=>		'java',
		'js'			=>		'javascript',
		'javascript'	=>		'javascript',
		'kix'			=>		'kixtart',
		'tex'			=>		'latex',
		'lisp'			=>		'lisp',
		'lsp'			=>		'lisp',
		'lols'			=>		'lolcode',
		'lol'			=>		'lolcode',
		'lsx'			=>		'lotusscript',
		'lscript'		=>		'lscript',
		'lua'			=>		'lua',
		'makefile'		=>		'make',
		'make'			=>		'make',
		'mrc'			=>		'mirc',
		'mxml'			=>		'mxml',
		'mysql'			=>		'mysql',
		'nsh'			=>		'nsis',
		'nsi'			=>		'nsis',
		'cma'			=>		'ocaml',
		'p'				=>		'pascal',
		'pl'			=>		'perl',
		'php'			=>		'php',
		'php4'			=>		'php',
		'php5'			=>		'php',
		'phps'			=>		'php',
		'pbk'			=>		'pixelbender',
		'pl'			=>		'plsql',
		'pov'			=>		'povray',
		'ps'			=>		'powershell',
		'pl'			=>		'prolog',
		'pvx'			=>		'providex',
		'py'			=>		'python',
		'qbasic'		=>		'qbasic',
		'rb'			=>		'ruby',
		'ruby'			=>		'ruby',
		'sas'			=>		'sas',
		'sas'			=>		'scala',
		'scm'			=>		'scheme',
		'sce'			=>		'scilab',
		'st'			=>		'smalltalk',
		'sql'			=>		'sql',
		'tk'			=>		'tcl',
		'tcl'			=>		'tcl',
		'tclx'			=>		'tcl',
		'tbasic'		=>		'thinbasic',
		'typo3'			=>		'typoscript',
		'idl'			=>		'idl',
		'vb'			=>		'vbnet',
		'v'				=>		'verilog',
		'vhdl'			=>		'vhdl',
		'vim'			=>		'vim',
		'wbt'			=>		'winbatch',
		'reg'			=>		'reg',
		'xml'			=>		'xml',
		'x++'			=>		'xpp',
		'vbs'			=>		'vb',
	);
	
	$ext = getExt($file);
	if(isset($ext_to_lang[$ext]))
		return $ext_to_lang[$ext];
}

/** 
 * Implementation of handle
 * @ingroup handle
 */
function add_code($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	if(handles($file, 'code'))
	{
		// check to see if it is in the database
		$db_code = db_query('SELECT id FROM code WHERE Filepath = "?" LIMIT 1', array($file));
		
		if( count($db_code) == 0 || $force )
		{
			// pull information from $info
			$fileinfo = get_code_info($file);
			$lines = $fileinfo['lines'];
			unset($fileinfo['lines']);
			
			if(count($db_code) == 0)
			{
				raise_error('Adding code: ' . $file, E_DEBUG);
				
				// add to database
				$id = db_query('INSERT INTO code (Filepath, Words, LineCount, Language) VALUES ("?", "?", "?", "?")', array(
					$fileinfo['Filepath'],
					$fileinfo['Words'],
					$fileinfo['LineCount'],
					$fileinfo['Language'],
				));
			}
			else
			{
				raise_error('Modifying code: ' . $file, E_DEBUG);
				
				// update database
				$return = db_query('UPDATE code SET Words = "?", LineCount = "?", Language = "?" WHERE id = ?', array(
					$fileinfo['Words'],
					$fileinfo['LineCount'],
					$fileinfo['Language'],
					$db_code[0]['id'],
				));
				
				$id = $db_code[0]['id'];
			}
			
			// now add the HTML so if this takes too long and fails it won't happen again
			// don't even bother if there are too many words
			if($fileinfo['Words'] < 4096)
			{
				$fileinfo = get_code_html($lines, $fileinfo['Language']);
				
				$return = db_query('UPDATE code SET HTML = "?" WHERE id = ?', array(
					$fileinfo['HTML'],
					$id,
				));
			}
			
			return $id;
		}

	}
	return false;
}

function get_code_info($file)
{
	$fileinfo = array();
	$fileinfo['Filepath'] = addslashes($file);
	
	$lines = array();
	$words = array('');
	if($fp = @fopen(str_replace('/', DIRECTORY_SEPARATOR, $file), 'rb'))
	{
		$word_count = 0;
		while(!feof($fp))
		{
			$buffer = trim(fgets($fp, 4096));
			if($word_count < 4096)
			{
				$symbols = split('[^a-zA-Z0-9]', strtolower($buffer), 512);
				$words = array_merge($symbols, $words);
				$word_count = count($words);
			}
			$lines[] = $buffer;
		}
		fclose($fp);
		
		// remove empty word
		unset($words[0]);
		
		$fileinfo['LineCount'] = count($lines);
		$fileinfo['Words'] = addslashes(join(' ', $words));
			
		$fileinfo['lines'] = join("\n", $lines);
		
		$lang = code_get_language(basename($file));
		if($lang !== false && $lines != '')
		{
			$fileinfo['Language'] = $lang;
		}
		else
		{
			$fileinfo['Language'] = '';
		}
	}
	
	return $fileinfo;
}

function get_code_html($lines, $lang)
{
	$fileinfo = array();
	if($lang !== '' && $lines != '')
	{
		$geshi = new GeSHi($lines, $lang);
		
		$fileinfo['HTML'] = addslashes($geshi->parse_code());
	}
	else
	{
		$fileinfo['HTML'] = addslashes(htmlspecialchars($lines));
	}
	return $fileinfo;
}

/** 
 * Implementation of output_handler
 * @ingroup output_handler
 */
function output_code($file)
{
	$file = str_replace('\\', '/', $file);
	
	if(setting('admin_alias_enable') == true)
		$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	header('Content-Disposition: ');
	return @fopen('code://' . $file, 'rb');
}

/** 
 * Implementation of get_handler
 * @ingroup get_handler
 */
function get_code($request, &$count)
{
	$request['cat'] = validate(array('cat' => 'code'), 'cat');
	$files = get_files($request, $count, 'files');
	
	foreach($files as $i => $file)
	{
		$files[$i]['Filemime'] = 'text/html';
	}
	
	return $files;
}

// source code handler
class code
{
       
    protected $internal_text = NULL;
    protected $internal_length = NULL;
    protected $internal_pos = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, strlen('code://')) == 'code://')
			$path = substr($path, strlen('code://'));

		$files = db_query('SELECT * FROM code WHERE Filepath = "?" AND ' . sql_users() . ' LIMIT 1', array($path));
		
		if(count($files) > 0)
		{
			$this->internal_text = $files[0]['HTML'];
			$this->internal_length = strlen($this->internal_text);
			$this->internal_pos = 0;
			return true;
		}
		
		return false;
    }
    function stream_read($count){
		if($this->internal_pos + $count > $this->internal_length)
			$count = $this->internal_length - $this->internal_pos;
		$buffer = substr($this->internal_text, $this->internal_pos, $count);
		$this->internal_pos += $count;
        return $buffer;
    }
    function stream_eof(){
        return $this->internal_pos >= $this->internal_length;
    }
    function stream_tell(){
        return $this->internal_pos;
    }
    function stream_seek($position){
		if($position > $this->internal_length)
		{
			$position = $this->internal_length;
		}
		$this->internal_pos = $position;
        return 0;
    }

}
