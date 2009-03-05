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
						$pieces = array_unique($pieces);
						$empty = array_search('', $pieces, true);
						if($empty !== false) unset($pieces[$empty]);
						$pieces = array_values($pieces);
						$before_pieces = array();
						$after_pieces = array_values($pieces);
						for($i = count($pieces)-1; $i >= 0; $i--)
						{
							$before_str = join('', $before_pieces);
							array_pop($after_pieces);
							$after_str = join('', $after_pieces);
							if(strpos(strtolower($before_str), strtolower($pieces[$i])) === false && strpos(strtolower($after_str), strtolower($pieces[$i])) === false)
							{
								// remove single characters and common words
								if(strlen($pieces[$i]) > 1 && !in_array(strtolower($pieces[$i]), array('and', 'the', 'in', 'of', 'an', 'lp')))
								{
									$valid_pieces[] = $pieces[$i];
									array_unshift($before_pieces, $pieces[$i]);
								}
							}
						}
						// remove things seperately so we can prioritize
						// remove common edition words
						if(count($valid_pieces) > 5)
						{
							foreach($valid_pieces as $i => $piece)
							{
								if(in_array(strtolower($valid_pieces[$i]), array('version', 'unknown', 'compilation', 'compilations', 'remastered', 'itunes')))
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
								if(strtoupper($valid_pieces[$i]) == $valid_pieces[$i])
								{
									unset($valid_pieces[$i]);
								}
							}
							if(count($common_pieces) == 0)
							{
								$common_pieces = array_values($valid_pieces);
							}
							else
							{
								$common_pieces = array_intersect($valid_pieces, $common_pieces);
								foreach($valid_pieces as $i => $piece)
								{
									if(in_array($valid_pieces[$i], $common_pieces))
									{
										unset($valid_pieces[$i]);
									}
								}
							}
							$valid_pieces = array_values($valid_pieces);
						}
						
						// if there are no valid parts then discard
						if(count($valid_pieces) == 0)
							unset($tmp_files[$i]);
						
						// check 5 parts of the filename
						$min = '';
						for($i = 0; $i < min(count($valid_pieces), 5); $i++)
						{
							$min .= 'LOCATE("' . $valid_pieces[$i] . '", Filepath) > 0';
							if($i != 0)
								$min .= ' AND LOCATE("' . $valid_pieces[$i] . '", Filepath) < LOCATE("' . $valid_pieces[$i-1] . '", Filepath)';
							$min .= ' AND ';
						}
						// remove last and
						$min = substr($min, 0, strlen($min)-5);
						if($min != '')
							$result = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => '(LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video") AND ' . $min, 'LIMIT' => 1));
						if(count($result) > 0)
						{
							$files[] = $result[0];
							continue;
						}
						
						// check 3 parts of the filename
						$min = '';
						for($i = 0; $i < min(count($valid_pieces), 3); $i++)
						{
							$min .= 'LOCATE("' . $valid_pieces[$i] . '", Filepath) > 0';
							if($i != 0)
								$min .= ' AND LOCATE("' . $valid_pieces[$i] . '", Filepath) < LOCATE("' . $valid_pieces[$i-1] . '", Filepath)';
							$min .= ' AND ';
						}
						// remove last and
						$min = substr($min, 0, strlen($min)-5);
						if($min != '')
							$result = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => '(LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video") AND ' . $min, 'LIMIT' => 1));
						if(count($result) > 0)
						{
							$files[] = $result[0];
							continue;
						}
						
						// check 5 parts of the filename none concurrent
						$min = '';
						$tmp_count = 0;
						for($i = 0; $i < min(count($valid_pieces), 5); $i++)
						{
							$min .= 'LOCATE("' . $valid_pieces[$i] . '", Filepath) > 0 AND ';
							if($tmp_count == 4)
								break;
							else
								$tmp_count++;
						}
						// remove last and
						$min = substr($min, 0, strlen($min)-5);
						if($min != '')
							$result = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => '(LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video") AND ' . $min, 'LIMIT' => 1));
						if(count($result) > 0)
						{
							$files[] = $result[0];
							continue;
						}
						
						// check 3 parts of the filename none concurrent
						$min = '';
						$tmp_count = 0;
						for($i = 0; $i < count($valid_pieces); $i++)
						{
							// for this case, skip numbers
							if(!is_numeric($valid_pieces[$i]))
							{
								$min .= 'LOCATE("' . $valid_pieces[$i] . '", Filepath) > 0 AND ';
								if($tmp_count == 2)
									break;
								else
									$tmp_count++;
							}
						}
						// remove last and
						$min = substr($min, 0, strlen($min)-5);
						if($min != '')
							$result = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => '(LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video") AND ' . $min, 'LIMIT' => 1));
						if(count($result) > 0)
						{
							$files[] = $result[0];
							continue;
						}						
						
						// check 3 from a different position
						$min = '';
						$tmp_count = 0;
						for($i = 2; $i < count($valid_pieces); $i++)
						{
							// for this case, skip numbers
							if(!is_numeric($valid_pieces[$i]))
							{
								$min .= 'LOCATE("' . $valid_pieces[$i] . '", Filepath) > 0 AND ';
								if($tmp_count == 2)
									break;
								else
									$tmp_count++;
							}
						}
						// remove last and
						$min = substr($min, 0, strlen($min)-5);
						if($min != '')
							$result = $database->query(array('SELECT' => self::DATABASE, 'WHERE' => '(LEFT(Filemime, 5) = "audio" OR LEFT(Filemime, 5) = "video") AND ' . $min, 'LIMIT' => 1));
						if(count($result) > 0)
						{
							$files[] = $result[0];
							continue;
						}
						
						// file can't be found
						log_error('Can\'t fine file from playlist: ' . $file);
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