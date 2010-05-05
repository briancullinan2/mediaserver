<?php

// read in code files and cache the hilighted version
//  use highlighting library from codepaster.com
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db_file.php';

// source code handler
class db_code extends db_file
{
	const DATABASE = 'code';
	
	const NAME = 'Code from Database';
	
    const PROTOCOL = 'code'; /* Underscore not allowed */
       
    protected $internal_text = NULL;
    protected $internal_length = NULL;
    protected $internal_pos = NULL;
	
    function stream_open($path, $mode, $options, &$opened_path)
    {
		if(substr($path, 0, strlen(self::PROTOCOL . '://')) == self::PROTOCOL . '://')
			$path = substr($path, strlen(self::PROTOCOL . '://'));

		$files = $GLOBALS['database']->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($path) . '"', 'LIMIT' => 1), true);
		
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
	
	static function init()
	{
		include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'geshi' . DIRECTORY_SEPARATOR . 'geshi.php';
	}

	static function columns()
	{
		return array('id', 'Words', 'HTML', 'LineCount', 'Language', 'Filepath');
	}
	
	static function struct()
	{
		return array(
			'Words' 		=> 'TEXT',
			'HTML' 			=> 'TEXT',
			'LineCount' 	=> 'INT',
			'Language' 		=> 'TEXT',
			'Filepath' 		=> 'TEXT'
		);
	}
	
	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		if(setting('use_alias') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		$type = getExtType($file);
		
		return ( self::getLanguage(basename($file)) || $type == 'text' );
	}

	static function getLanguage($file)
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
		
		switch(getExt($file))
		{
			case 'as':
				return 'actionscript';
				break;
			case 'abap':
				return 'abap';
				break;
			case 'a':
				return 'ada';
				break;
			case 'applescript':
				return 'applescript';
				break;
			case 'x86':
			case 'asm':
				return 'asm';
				break;
			case 'm68k':
				return 'm68k';
				break;
			case 'pic16':
				return 'pic16';
				break;
			case 'z80':
				return 'z80';
				break;
			case 'log':
				return 'apache';
				break;
			case 'list':
				return 'apt_sources';
				break;
			case 'asp':
			case 'aspx':
				return 'asp';
				break;
			case 'aut':
				return 'autoit';
				break;
			case 'abnf':
			case 'bnf':
				return 'bnf';
				break;
			case 'sh':
			case 'bash':
				return 'bash';
				break;
			case 'bgm':
				return 'basic4gl';
				break;
			case 'bb':
				return 'blitzbasic';
				break;
			case 'b':
			case 'bf':
				return 'bf';
				break;
			case "c":
			case "h":
			case "cpp":
				return 'cpp';
				break;
			case "cs":
			case "csharp":
				return 'csharp';
				break;
			case "dcl":
				return 'caddcl';
				break;
			case "lsp":
				return 'cadlisp';
				break;
			case "cfdg":
				return 'cfdg';
				break;
			case "cil":
				return 'cil';
				break;
			case "cob":
			case "cbl":
			case "cobol":
				return 'cobol';
				break;
			case "css":
				return 'css';
				break;
			case "d":
				return 'd';
				break;
			case "pas":
			case "dpr":
				return 'delphi';
				break;
			case "patch":
			case "diff":
				return 'diff';
				break;
			case "batch":
				return 'dos';
				break;
			case "dot":
				return 'dot';
				break;
			case "eff":
				return 'eiffel';
				break;
			case "f77":
			case "f95":
			case "ftn":
				return 'fortran';
				break;
			case "4gl":
				return 'genero';
				break;
			case "fbl":
				return 'freebasic';
				break;
			case "mo":
				return 'gettext';
				break;
			case "glsl":
				return 'glsl';
				break;
			case "gml":
				return 'gml';
				break;
			case "plt":
				return 'gnuplot';
				break;
			case "groovy":
				return 'groovy';
				break;
			case "gs":
			case "hs":
				return 'haskell';
				break;
			case "hq9+":
				return 'hq9plus';
				break;
			case "htm":
			case "html":
				return 'html';
				break;
			case "ini":
				return 'ini';
				break;
			case "ino":
			case "myp":
				return 'inno';
				break;
			case "i":
				return 'intercal';
				break;
			case "io":
				return 'io';
				break;
			case "java":
				return 'java';
				break;
			case "js":
			case "javascript":
				return 'javascript';
				break;
			case "kix":
				return 'kixtart';
				break;
			case "tex":
				return 'latex';
				break;
			case "lisp":
			case "lsp":
				return 'lisp';
				break;
			case "lols":
			case "lol":
				return 'lolcode';
				break;
			case "lsx":
				return 'lotusscript';
				break;
			case "lscript":
				return 'lscript';
				break;
			case "lua":
				return 'lua';
				break;
			case "makefile":
			case "make":
				return 'make';
				break;
			case "mrc":
				return 'mirc';
				break;
			case "mxml":
				return 'mxml';
				break;
			case "mysql":
				return 'mysql';
				break;
			case "nsh":
			case "nsi":
				return 'nsis';
				break;
			case "cma":
				return 'ocaml';
				break;
			case "p":
				return 'pascal';
				break;
			case "pl":
				return 'perl';
				break;
			case "php":
			case "php4":
			case "php5":
			case "phps":
				return 'php';
				break;
			case "pbk":
				return 'pixelbender';
				break;
			case "pl":
				return 'plsql';
				break;
			case "pov":
				return 'povray';
				break;
			case "ps":
				return 'powershell';
				break;
			case "pl":
				return 'prolog';
				break;
			case "pvx":
				return 'providex';
				break;
			case "py":
				return 'python';
				break;
			case "qbasic":
				return 'qbasic';
				break;
			case "rb":
			case "ruby":
				return 'ruby';
				break;
			case "sas":
				return 'sas';
				break;
			case "sas":
				return 'scala';
				break;
			case "scm":
				return 'scheme';
				break;
			case "sce":
				return 'scilab';
				break;
			case "st":
				return 'smalltalk';
				break;
			case "sql":
				return 'sql';
				break;
			case "tk":
			case "tcl":
			case "tclx":
				return 'tcl';
				break;
			case "tbasic":
				return 'thinbasic';
				break;
			case "typo3":
				return 'typoscript';
				break;
			case "idl":
				return 'idl';
				break;
			case "vb":
				return 'vbnet';
				break;
			case "v":
				return 'verilog';
				break;
			case "vhdl":
				return 'vhdl';
				break;
			case "vim":
				return 'vim';
				break;
			case "wbt":
				return 'winbatch';
				break;
			case "reg":
				return 'reg';
				break;
			case "xml":
				return 'xml';
				break;
			case "x++":
				return 'xpp';
				break;
			case "vbs":
				return 'vb';
				break;
			default:
				return false;
		}

	}

	static function handle($file, $force = false)
	{
		$file = str_replace('\\', '/', $file);
		
		if(self::handles($file))
		{
			// check to see if it is in the database
			$db_code = $GLOBALS['database']->query(array(
					'SELECT' => self::DATABASE,
					'COLUMNS' => 'id',
					'WHERE' => 'Filepath = "' . addslashes($file) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($db_code) == 0 )
			{
				return self::add($file);
			}
			elseif($force)
			{
				return self::add($file, $db_code[0]['id']);
			}

		}
		return false;
	}
	
	static function getInfo($file)
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
			
			$lang = self::getLanguage(basename($file));
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

	static function getHTML($lines, $lang)
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

	static function add($file, $code_id = NULL)
	{
		if(!class_exists('GeSHi'))
			self::init();
			
		// pull information from $info
		$fileinfo = self::getInfo($file);
		$lines = $fileinfo['lines'];
		unset($fileinfo['lines']);
	
		if( $code_id == NULL )
		{
			PEAR::raiseError('Adding code: ' . $file, E_DEBUG);
			
			// add to database
			$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo), false);
		}
		else
		{
			PEAR::raiseError('Modifying code: ' . $file, E_DEBUG);
			
			// update database
			$return = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $code_id), false);
		
			$id = $code_id;
		}
		
		// now add the HTML so if this takes too long and fails it won't happen again
		// don't even bother if there are too many words
		if($fileinfo['Words'] < 4096)
		{
			$fileinfo = self::getHTML($lines, $fileinfo['Language']);
			$return = $GLOBALS['database']->query(array('UPDATE' => self::DATABASE, 'VALUES' => $fileinfo, 'WHERE' => 'id=' . $id), false);
		}
		
		return $id;
	}

	static function out($file)
	{
		$file = str_replace('\\', '/', $file);
		
		if(setting('use_alias') == true)
			$file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		header('Content-Disposition: ');
		return @fopen(self::PROTOCOL . '://' . $file, 'rb');
	}
	
	static function get($request, &$count)
	{
		$files = parent::get($request, $count, get_class());
		
		foreach($files as $i => $file)
		{
			$files[$i]['Filemime'] = 'text/html';
		}
		
		return $files;
	}
	
	static function remove($file)
	{
		parent::remove($file, get_class());
	}
	
	static function cleanup()
	{
		parent::cleanup(get_class());
	}

}

stream_wrapper_register(
    db_code::PROTOCOL,
   'db_code'
);


?>
