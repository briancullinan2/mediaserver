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
		
		$artist_tokens = tokenize($artist);
		$album_tokens = tokenize($album);
		
		// create url
		// do soundtracks seperately because they will already have a very limit selection
		if(in_array('soundtrack', $album_tokens['All']))
		{
			$tmp_some_tokens = array_unique(array_merge($artist_tokens['Some'], $album_tokens['Some']));
			$url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
			$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $tmp_some_tokens)) . '&ResponseGroup=Images,Similarities,Small,Tracks';
			$GLOBALS['snoopy']->fetch($url);
			$contents = $GLOBALS['snoopy']->results;
		}
		else
		{
			$url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
			$url .= '&SearchIndex=Music&Artist=' . urlencode(join(' ', $artist_tokens['Some'])) . '&Title=' . urlencode(join(' ', $album_tokens['Few'])) . '&ResponseGroup=Images,Similarities,Small,Tracks';
			
			$GLOBALS['snoopy']->fetch($url);
			$contents = $GLOBALS['snoopy']->results;
			
			if(preg_match('/\<Errors\>/i', $contents) !== 0)
			{
				$url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
				$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $artist_tokens['Some'])) . '&Title=' . urlencode(join(' ', $album_tokens['Few'])) . '&ResponseGroup=Images,Similarities,Small,Tracks';
				$GLOBALS['snoopy']->fetch($url);
				$contents = $GLOBALS['snoopy']->results;
			}
			
			if(preg_match('/\<Errors\>/i', $contents) !== 0)
			{
				$tmp_some_tokens = array_unique(array_merge($artist_tokens['Some'], $album_tokens['Some']));
				$url = 'http://' . AMAZON_SERVER . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . AMAZON_DEV_KEY;
				$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $tmp_some_tokens)) . '&ResponseGroup=Images,Similarities,Small,Tracks';
				$GLOBALS['snoopy']->fetch($url);
				$contents = $GLOBALS['snoopy']->results;
			}
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
			
			// pick out best match
			$best = '';
			$best_tracks = '';
			$best_count = 0;
			foreach($items as $i => $item)
			{
				// first get the album and match that
				$match = preg_match('/\<Title\>(.*)\<\/Title\>/i', $item, $matches);
				$title = $matches[1];
				$tmp_album_tokens = tokenize($title);
				
				$album_count = count(array_intersect($tmp_album_tokens['All'], $album_tokens['All']));
				
				// now match artists
				$match = preg_match('/\<Artist\>(.*)\<\/Artist\>/i', $item, $matches);
				if(!isset($matches[1]))
				{
					$artist_count = 0;
				}
				else
				{
					$artists = preg_split('/\<\/Artist\>\<Artist\>/i', $matches[1]);
					
					$artist_count = 0;
					foreach($artists as $i => $tmp_artist)
					{
						$tmp_artist_tokens = tokenize($tmp_artist);
						$tmp_count = count(array_intersect($tmp_artist_tokens['All'], $artist_tokens['All']));
						if($tmp_count > $artist_count) $artist_count = $tmp_count;
					}
				}
				
				// track differences negatively affect the count
				$tracks = db_audio::get(array('search_Artist' => '+' . join(' +', $artist_tokens['Few']), 'search_Album' => '+' . join(' +', $album_tokens['Few'])), $tmp_count, $tmp_error);
				
				// track counts are only affected if there is matching audio
				$track_count = 0;
				$match = preg_match('/\<Tracks[^\>]*\>(.*)\<\/Tracks\>/i', $item, $matches);
				$disks = array();
				if(isset($matches[1]))
				{
					$match = preg_match('/\<Disc[^\>]*\>(.*)\<\/Disc\>/i', $matches[1], $matches);
					$disks = preg_split('/\<\/Disc\>\<Disc[^\>]*\>/i', $matches[1]);
					foreach($disks as $i => $disk)
					{
						$match = preg_match('/\<Track[^\>]*\>(.*)\<\/Track\>/i', $disk, $matches);
						$disks[$i] = preg_split('/\<\/Track\>\<Track[^\>]*\>/i', $matches[1]);
						
						if(count($tracks) > 0)
						{
							if(count($disks[$i]) == count($tracks)) $track_count = 1;
							elseif($track_count == 0) $track_count = -1;
						}
					}
				}
				else
				{
					$track_count = -1;
				}
				
				// if there are multiple disks add the words to the album tokens and recount
				if(count($disks) > 1)
				{
					$tmp_artist_tokens['All'][] = 'disk';
					$tmp_artist_tokens['All'][] = 'disc';
					$tmp_artist_tokens['All'][] = 'volume';
					
					$album_count = count(array_intersect($tmp_album_tokens['All'], $album_tokens['All']));
				}
				
				// set the new count
				if($album_count + $artist_count + $track_count > $best_count)
				{
					$best_count = $album_count + $artist_count + $track_count;
					$best = $item;
					$best_tracks = $disks;
				}
			}
			
			// parse single result
			$match = preg_match('/\<ASIN\>([a-z0-9]*)\<\/ASIN\>/i', $best, $matches);
			if(!isset($matches[1]))
			{
				log_error('Error reading AmazonId: ' . htmlspecialchars($best));
			}
			else
			{
				$fileinfo['AmazonId'] = addslashes($matches[1]);
			}
			
			// parse tracks for later use
			$fileinfo['AmazonInfo'] = addslashes(serialize($best_tracks));
			
			$match = preg_match('/\<LargeImage\>\<URL\>(.*)\<\/URL\>/i', $best, $matches);
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
		if(!isset($request['order_by']) || $request['order_by'] == 'Filepath')
			$request['order_by'] = 'AmazonTitle';
		
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
						'WHERE' => 'AmazonTitle = "' . addslashes($artist) . ' - ' . addslashes($album) . '"'
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
						
						return $files;
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
			if(isset($request['file']))
				$files[$i]['Filepath'] = $request['file'];
			else
				$files[$i]['Filepath'] = '/' . $file['AmazonTitle'] . '/';
			$files[$i]['Filetype'] = 'FOLDER';
		}
		
		return $files;
	}

	static function remove($file, $module = NULL)
	{
		// remove the amazon entry for whatever is passed in
		//  but only if the artist/album doesn't exist in the database anymore
		//  so only remove when the last file is removed
		//    always remove directories
		//    
	}


	static function cleanup()
	{
	}
	
}
?>