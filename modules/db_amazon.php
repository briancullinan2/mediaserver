<?php

// provide links to amazon
// handles audio files, but after information is loaded search amazon for artist and album and compare track listings
// ignore stuff that can't be found, or add it to database and unknown entry for files
// store an album in 1 row with the album art, and save the songId in a cell
// handle movies, and search for movieId
//  use parseFilename to search with
define('AMAZON_DEV_KEY', '1D9T2665M4N4A7ACEZR2');
define('AMAZON_SERVER', 'ecs.amazonaws.com');

$no_setup = true;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_file.php';
require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_audio.php';
require_once LOCAL_ROOT . 'modules' . DIRECTORY_SEPARATOR . 'db_video.php';

// include the id handler
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';

// include snoopy to download pages
require_once LOCAL_ROOT . 'include' . DIRECTORY_SEPARATOR . 'Snoopy.class.php';

// set up id3 reader incase any files need it
$GLOBALS['snoopy'] = new Snoopy();

// music handler
class db_amazon extends db_file
{
	const DATABASE = 'amazon';
	
	const NAME = 'Amazon from Database';

	static function columns()
	{
		return array('id', 'AmazonId', 'AmazonLink', 'AmazonTitle', 'AmazonType', 'AmazonInfo', 'Thumbnail', 'Filetype', 'Filepath');
	}

	static function handles($file)
	{
		$file = str_replace('\\', '/', $file);
		if(USE_ALIAS == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
		
		if(db_audio::handles($file))
		{
			// make sure it isn't already in the database and wasn't found
			$audio = $GLOBALS['database']->query(array(
					'SELECT' => db_audio::DATABASE,
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			if(count($audio) > 0)
			{
				$amazon = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($audio[0]['Artist']) . ' - ' . addslashes($audio[0]['Album']) . '"'
					)
				);
				if(count($amazon) == 0 || $amazon[0]['AmazonId'] != '')
					return true;
			}
		}
		elseif(db_movies::handles($file))
		{
			// make sure it isn't already in the database and wasn't found
			$movie = $GLOBALS['database']->query(array(
					'SELECT' => db_movies::DATABASE,
					'WHERE' => 'Filepath = "' . addslashes($file) . '"'
				)
			);
			if(count($movie) > 0)
			{
				$amazon = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($movie[0]['Title']) . '"'
					)
				);
				if(count($amazon) == 0 || $amazon[0]['AmazonId'] != '')
					return true;
			}
		}
		elseif(is_dir($file))
		{
			// check if it is a directory that can be found
			if($file[strlen($file)-1] == '/') $file = substr($file, 0, strlen($file)-1);
						
			$dirs = split('/', $file);
			
			$album = @$dirs[count($dirs)-1];
			$artist = @$dirs[count($dirs)-2];
			
			if(isset($album) && isset($artist) && $album != '' && $artist != '' && $artist != 'Music')
			{
				// check if it is in database already
				$amazon = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($artist) . ' - ' . addslashes($album) . '"'
					)
				);
				if(count($amazon) == 0 || $amazon[0]['AmazonId'] != '')
					return true;
			}
		}
		
		return false;
	}

	static function handle($file)
	{
		$file = str_replace('\\', '/', $file);
		
		if(self::handles($file))
		{
			if(db_audio::handles($file) || is_dir($file))
			{
				if(is_dir($file))
				{
					if($file[strlen($file)-1] == '/') $file = substr($file, 0, strlen($file)-1);
					$dirs = split('/', $file);
					
					$album = @$dirs[count($dirs)-1];
					$artist = @$dirs[count($dirs)-2];
				}
				else
				{
					// get information from database
					$audio = $GLOBALS['database']->query(array(
							'SELECT' => db_audio::DATABASE,
							'WHERE' => 'Filepath = "' . addslashes($file) . '"'
						)
					);
					if(count($audio) > 0)
					{
						$artist = $audio[0]['Artist'];
						$album = $audio[0]['Album'];
					}
					// try and get information from file
					else
					{
						$info = $GLOBALS['getID3']->analyze(str_replace('/', DIRECTORY_SEPARATOR, $file));
						getid3_lib::CopyTagsToComments($info);
						
						$artist = @$info['comments_html']['artist'][0];
						$album = @$info['comments_html']['album'][0];
					}
				}
				
				$amazon = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($artist) . ' - ' . addslashes($album) . '"'
					)
				);
				
				if( count($amazon) == 0 )
				{
					$id = self::add_music($artist, $album);
				}
				else
				{
					// don't modify because amazon information doesn't change
				}
			}
			elseif(db_movies::handles($file))
			{
				// get information from database
				// try and get information from file
			}
		}
	}
	
	static function getMusicInfo($artist, $album)
	{

		$fileinfo = array();
		$fileinfo['AmazonTitle'] = addslashes($artist) . ' - ' . addslashes($album);
		$fileinfo['AmazonId'] = '';
		$fileinfo['AmazonType'] = 'Music';
		$fileinfo['AmazonInfo'] = '';
		$fileinfo['Thumbnail'] = '';
		
		// create url
        $url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
		$url .= '&SearchIndex=Music&Artist=' . urlencode($artist) . '&Title=' . urlencode($album) . '&ResponseGroup=Images,Similarities,Small,Tracks';
		
        $GLOBALS['snoopy']->fetch($url);
        $contents = $GLOBALS['snoopy']->results;
		
		// just search album title without artist
		if(preg_match('/\<Errors\>/i', $contents) !== 0)
		{
			$url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
			$url .= '&SearchIndex=Music&Title=' . urlencode($album) . '&ResponseGroup=Images,Similarities,Small,Tracks';
			
			$GLOBALS['snoopy']->fetch($url);
			$contents = $GLOBALS['snoopy']->results;
		}
		
		// check if it was found
		if(preg_match('/\<Errors\>/i', $contents) !== 0)
		{
			return $fileinfo;
		}
		else
		{
			$match = preg_match('/\<Item\>(.*)\<\/Item\>/i', $contents, $matches);
			$items = preg_split('/\<\/Item\>\<Item\>/i', $matches[1]);
			
			// parse single result
			$match = preg_match('/\<ASIN\>([a-z0-9]*)\<\/ASIN\>/i', $items[0], $matches);
			$fileinfo['AmazonId'] = addslashes($matches[1]);
			
			// parse tracks for later use
			$match = preg_match('/\<Tracks[^\>]*\>(.*)\<\/Tracks\>/i', $items[0], $matches);
			if(isset($matches[1]))
			{
				$match = preg_match('/\<Disc[^\>]*\>(.*)\<\/Disc\>/i', $matches[1], $matches);
				$disks = preg_split('/\<\/Disc\>\<Disc[^\>]*\>/i', $matches[1]);
				foreach($disks as $i => $disk)
				{
					$match = preg_match('/\<Track[^\>]*\>(.*)\<\/Track\>/i', $disk, $matches);
					$disks[$i] = preg_split('/\<\/Track\>\<Track[^\>]*\>/i', $matches[1]);
				}
				$fileinfo['AmazonInfo'] = addslashes(serialize($matches[1]));
			}
			
			$match = preg_match('/\<LargeImage\>\<URL\>(.*)\<\/URL\>/i', $items[0], $matches);
			if(isset($matches[1]))
			{
				$GLOBALS['snoopy']->fetch($matches[1]);
				$fileinfo['Thumbnail'] = addslashes($GLOBALS['snoopy']->results);
			}
		}
		
		return $fileinfo;
	}

	static function add_music($artist, $album)
	{
		// pull information from $info
		$fileinfo = self::getMusicInfo($artist, $album);
	
		log_error('Adding Amazon Music: ' . $artist . ' - ' . $album);
		
		// add to database
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
		return $id;
	}
	
	static function add_movie($title)
	{
		// pull information from $info
		$fileinfo = self::getMovieInfo($title);

		log_error('Adding Amazon Movie: ' . $title);
		
		// add to database
		$id = $GLOBALS['database']->query(array('INSERT' => self::DATABASE, 'VALUES' => $fileinfo));
		
		return $id;
	}
	
	static function get($request, &$count, &$error)
	{
		// modify the request
		if(isset($request['file']))
		{
			if(db_audio::handles($request['file']))
			{
				$audio = db_audio::get(array('file' => $request['file']), $tmp_count, $error);
				if(count($audio) > 0)
				{
					$files = $GLOBALS['database']->query(array(
							'SELECT' => self::DATABASE,
							'WHERE' => 'AmazonTitle = "' . addslashes($audio[0]['Artist']) . ' - ' . addslashes($audio[0]['Album']) . '"'
						)
					);
				}
				else
				{
					$files = array();
				}
			}
			elseif(db_movies::handles($request['file']))
			{
				$movie = db_movies::get(array('file' => $request['file']), $tmp_count, $error);
				if(count($movie) > 0)
				{
					$files = $GLOBALS['database']->query(array(
							'SELECT' => self::DATABASE,
							'WHERE' => 'AmazonTitle = "' . addslashes($movie[0]['Title']) . '"'
						)
					);
				}
				else
				{
					$files = array();
				}
			}
			elseif(is_dir($request['file']))
			{
				if($file[strlen($request['file'])-1] == '/') $request['file'] = substr($request['file'], 0, strlen($request['file'])-1);
				$dirs = split('/', $request['file']);
				
				$album = $dirs[count($dirs)-1];
				$artist = @$dirs[count($dirs)-2];
				$files = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($artist) . "\n" . addslashes($album) . '"'
					)
				);
			}
			else
			{
				$files = array();
			}
			$count = count($files);
		}
		else
		{
			// change some request vars
			if(isset($request['dir']))
			{
				$request['dir'] = str_replace('\\', '/', $request['dir']);
				if($request['dir'][0] == '/') $request['dir'] = substr($request['dir'], 1);
				if($request['dir'][strlen($request['dir'])-1] == '/') $request['dir'] = substr($request['dir'], 0, strlen($request['dir'])-1);
				
				$title = $request['dir'];
				unset($request['dir']);
				
				$amazon = $GLOBALS['database']->query(array(
						'SELECT' => self::DATABASE,
						'WHERE' => 'AmazonTitle = "' . addslashes($title) . '"'
					)
				);
				
				if(count($amazon) > 0)
				{
					if($amazon[0]['AmazonType'] = 'Music')
					{
						$title = split(' - ', $title);
						$request['search_Artist'] = '"' . $title[0] . '"';
						unset($title[0]);
						$request['search_Album'] = join(' - ', $title);
						$files = db_audio::get($request, $count, $error);
					}
				}
				else
				{
					$files = array();
					$count = 0;
				}
			}
			else
			{
				// get files
				$files = parent::get($request, $count, $error, get_class());
			}
		}
		
		// make some changes
		foreach($files as $i => $file)
		{
			if($file['AmazonId'] != '')
				$files[$i]['AmazonLink'] = 'http://www.amazon.com/dp/' . $file['AmazonId'] . '/?SubscriptionId=' . AMAZON_DEV_KEY;
			$files[$i]['Filepath'] = '/' . $file['AmazonTitle'] . '/';
			$files[$i]['Filetype'] = 'FOLDER';
		}
		
		return $files;
	}


	static function cleanup()
	{
	}
	
}
?>