<?php


// just like with the way zip files should work, return the list of files that are in a playlist by parsing through their path
//  maybe use aliases to parse any path leading to the same place?
$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'fs_file.php';

// music handler
class db_playlist extends db_file
{
	const DATABASE = 'files';
	
	const NAME = 'Playlists from Database';
	
	static function columns()
	{
		return array('id', 'SongCount', 'Filename', 'Filepath');
	}

	static function handles($file)
	{
				
		// get file extension
		$ext = getExt($file);
		
		switch($ext)
		{
			case 'wpl':
			case 'm3u':
			case 'txt':
				return true;
			default:
				return false;
		}
		
		return false;

	}

	static function handle($database, $file)
	{
	}
	
	static function get($database, $request, &$count, &$error, $module = NULL)
	{
		if(isset($request['dir']))
		{
			$request['file'] = str_replace('\\', '/', $request['dir']);
			if(USE_ALIAS == true)
				$request['file'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $request['file']);
			
			$files = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => 'Filepath = "' . addslashes($request['file']) . '"'));
			
			if(count($files) > 0)
			{
				$database->validate($request, $props, get_class());
			
				// open playlist and parse out paths
				if($fp = fopen($request['file'], 'rb'))
				{
					$tmp_files = array();
					switch($files[0]['Filetype'])
					{
						case 'WPL':
							while(!feof($fp))
							{
								$buffer = trim(fgets($fp));
								$count = preg_match('/\<media src="([^"]*)"( ?(t|c)id=| ?\/\>)/i', $buffer, $matches);
								if($count > 0 && trim($matches[1]) != '')
								{
									$tmp_files[] = str_replace(array('&apos;', '&amp;'), array('\'', '&'), strip_tags(trim($matches[1])));
								}
							}
						case 'M3U':
							while(!feof($fp))
							{
								$buffer = trim(fgets($fp));
								$count = preg_match('/^\s*([^#])(.*)/i', $buffer, $matches);
								if($count > 0 && trim($matches[1] . $matches[2]) != '')
								{
									$tmp_files[] = urldecode(trim($matches[1] . $matches[2]));
								}
							}
						case 'TXT':
							// try and find some paths or something
							$buffer = fgets($fp);
							while(!feof($fp))
							{
								$buffer = trim(fgets($fp));
								$buffer = str_replace(chr(0), '', $buffer);
								$count = preg_match('/(([^\\\\\\/\\:\\*\\?\\<\\>\\|]+[\\\\\\/])*[^\\\\\\/\\:\\*\\?\\<\\>\\|]+\.[a-z0-9]+)([^a-z0-9]|$)/i', $buffer, $matches);
								if($count > 0 && trim($matches[1]) != '')
								{
									$tmp_files[] = trim($matches[1]);
								}
							}
							
					}
					fclose($fp);
					
					// now process the matches
					$files = array();
					
					$common_pieces = array();
					if(isset($tmp_files[0])) $common_pieces = array_unique(split('[^a-zA-Z0-9]', $tmp_files[0]));
					// remove some common parts
					for($i = 0; $i < min(6, count($tmp_files)); $i++)
					{
						$common_pieces = array_intersect($common_pieces, array_unique(split('[^a-zA-Z0-9]', $tmp_files[$i])));
						if(count($common_pieces) / count(array_unique(split('[^a-zA-Z0-9]', $tmp_files[$i]))) > .40)
						{
							// remove some
							unset($common_pieces[count($common_pieces)-1]);
						}
					}
					
					// go through each file and do multiple steps from most presice to most general and try to find the file
					foreach($tmp_files as $i => $file)
					{
						if(count($files) > $request['start'] + $request['limit'])
							break;
							
						// remove extension we don't care what format it is
						$file = substr($file, 0, strrpos($file, '.'));
						$file = str_replace('\\', '/', $file);
						$dir1 = substr($file, strrpos($file, '/'));
						$dir1 = substr($file, strrpos(substr($file, 0, strlen($file) - strlen($dir1)), '/'));
						$dir2 = substr($file, strrpos(substr($file, 0, strlen($file) - strlen($dir1)), '/'));
						
						// TODO put alias stuff here
						$result = array();
						
						// check minimized filename and directories
						$valid_pieces = array();
						$pieces = split('[^a-zA-Z0-9]', $file);
						$empty = array_search('', $pieces, true);
						if($empty !== false) unset($pieces[$empty]);
						$pieces = array_values($pieces);
						for($i = 0; $i < count($pieces); $i++)
						{
							// remove single characters and common words
							if(strlen($pieces[$i]) > 1 && !in_array(strtolower($pieces[$i]), array('and', 'the', 'of', 'an', 'lp')))
							{
								$valid_pieces[] = $pieces[$i];
							}
						}
						// remove things seperately so we can prioritize
						// remove common edition words
						if(count($valid_pieces) > 5)
						{
							foreach($valid_pieces as $i => $piece)
							{
								if(in_array(strtolower($valid_pieces[$i]), array('version', 'unknown', 'compilation', 'compilations', 'remastered', 'itunes', 'music')))
								{
									unset($valid_pieces[$i]);
								}
							}
							$valid_pieces = array_values($valid_pieces);
						}
						// remove common other common words
						if(count($valid_pieces) > 5)
						{
							foreach($valid_pieces as $i => $piece)
							{
								if(in_array(strtolower($valid_pieces[$i]), array('album', 'artist', 'single', 'clean', 'box', 'boxed', 'set', 'live', 'band', 'hits', 'other')))
								{
									unset($valid_pieces[$i]);
								}
							}
							$valid_pieces = array_values($valid_pieces);
						}
						// remove other wierdness
						if(count($valid_pieces) > 5)
						{
							foreach($valid_pieces as $i => $piece)
							{
								if(strtoupper($valid_pieces[$i]) == $valid_pieces[$i] || in_array($valid_pieces[$i], $common_pieces))
								{
									unset($valid_pieces[$i]);
								}
							}
						}
						
						// if there are no valid parts then discard
						if(count($valid_pieces) == 0)
							unset($tmp_files[$i]);
							
						// search for file using terms
						$result = db_audio::get($database, array('search' => join(' ', $valid_pieces), 'limit' => 1), $tmp_count, $tmp_error);
						if($tmp_count > 0)
						{
							$files[] = $result[0];
							continue;
						}

						// search for file using terms
						$result = db_video::get($database, array('search' => join(' ', $valid_pieces), 'limit' => 1), $tmp_count, $tmp_error);
						if($tmp_count > 0)
						{
							$files[] = $result[0];
							continue;
						}
						
						// search for file using terms
						$result = db_file::get($database, array('search' => join(' ', $valid_pieces), 'limit' => 1), $tmp_count, $tmp_error);
						if($tmp_count > 0)
						{
							$files[] = $result[0];
							continue;
						}

						// file can't be found
						log_error('Can\'t find file from playlist: ' . $file);
					}
					
					$count = count($tmp_files);
					
					// now pull out the start and limit
					$tmp_files = array();
					for($i = $request['start']; $i < $request['start'] + $request['limit']; $i++)
					{
						if(isset($files[$i]))
							$tmp_files[] = $files[$i];
					}
					$files = $tmp_files;
				}
			}
		}
		elseif(!isset($request['file']))
		{
			$request['search_Filename'] = '.wpl$';
			$files = parent::get($database, $request, $count, $error, get_class());
		}
		else
		{
			$files = parent::get($database, $request, $count, $error, get_class());
		}
		
		return $files;
	}


	static function cleanup($database, $watched)
	{
	}
}

?>