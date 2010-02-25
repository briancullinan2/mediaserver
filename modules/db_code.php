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
		require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'Text' . DIRECTORY_SEPARATOR . 'Highlighter.php';
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
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		$ext = getExt(basename($file));
		$type = getExtType($file);
		
		return ( self::getLanguage($ext) || $type == 'text' );
	}

	static function getLanguage($ext)
	{
		switch($ext)
		{
			case "c":
			case "h":
			case "cpp":
				return 'cpp';
				break;
			case "css":
				return 'css';
				break;
			case "dtd":
				return 'dtd';
				break;
			case "htm":
			case "html":
				return 'html';
				break;
			case "java":
				return 'java';
				break;
			case "js":
				return 'javascript';
				break;
			case "mysql":
				return 'mysql';
				break;
			case "pl":
				return 'perl';
				break;
			case "php":
				return 'php';
				break;
			case "py":
				return 'python';
				break;
			case "rb":
				return 'ruby';
				break;
			case "sql":
				return 'sql';
				break;
			case "xml":
				return 'xml';
				break;
			case "vbs":
				return 'vbscript';
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
			$fileinfo['Words'] = join(' ', $words);
				
			$fileinfo['lines'] = join("\n", $lines);
			
			$lang = self::getLanguage(getExt(basename($file)));
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
			$highlighter = Text_Highlighter::factory($lang);
			
			$fileinfo['HTML'] = addslashes($highlighter->highlight($lines));
		}
		else
		{
			$fileinfo['HTML'] = addslashes(htmlspecialchars($lines));
		}
		return $fileinfo;
	}

	static function add($file, $code_id = NULL)
	{
		if(!class_exists('Text_Highlighter'))
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
		
		if(USE_ALIAS == true)
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
