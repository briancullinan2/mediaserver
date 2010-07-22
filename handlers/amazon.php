<?php

/** 
 * Implementation of register_handler
 * @ingroup register_handler
 */
function register_amazon()
{
	return array(
		'name' => 'Amazon',
		'description' => 'Links amazing music to files on disk.',
		'database' => array(
			'AmazonId' 		=> 'TEXT',
			'Filepath' 		=> 'TEXT',
			'AmazonType' 	=> 'TEXT',
			'AmazonInfo' 	=> 'TEXT',
			'Matches' 		=> 'TEXT',
			'Thumbnail' 	=> 'BLOB',
		),
		'depends on' => array('getid3_installed', 'curl_installed', 'audio', 'movies', 'database'),
		'settings' => array('amazon_dev_key', 'amazon_server'),
	);
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_amazon_dev_key($settings)
{
	if(isset($settings['amazon_dev_key']))
		return $settings['amazon_dev_key'];
	else
		return '1D9T2665M4N4A7ACEZR2';
}

/**
 * Implementation of setting
 * @ingroup setting
 */
function setting_amazon_server($settings)
{
	if(isset($settings['amazon_server']))
		return $settings['amazon_server'];
	else
		return 'ecs.amazonaws.com';
}

/**
 * Implementation of configure
 * @ingroup configure
 */
function configure_amazon($settings, $request)
{
	$settings['amazon_dev_key'] = setting_amazon_dev_key($settings);
	$settings['amazon_server'] = setting_amazon_server($settings);
	
	$options = array();
	
	$options['amazon_dev_key'] = array(
		'name' => lang('amazon dev key title', 'Amazon Development API Access Key'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('amazon dev key description', 'This key is used to perform queries on Amazon\'s search API servers.'),
			),
		),
		'type' => 'text',
		'value' => $settings['amazon_dev_key'],
	);
	
	$options['amazon_server'] = array(
		'name' => lang('amazon server title', 'Amazon Development Server'),
		'status' => '',
		'description' => array(
			'list' => array(
				lang('amazon server description', 'This is the address for the server to work with, this could be different in other countries or provide a sandbox address.'),
			),
		),
		'type' => 'text',
		'value' => $settings['amazon_server'],
	);
	
	return $options;
}

/**
 * Implementation of handler_setup
 * @ingroup handler_setup
 */
function setup_amazon()
{
	// include the id handler
	include_once setting('local_root') . 'include' . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'getid3.php';
	
	// set up id3 reader incase any files need it
	$GLOBALS['getID3'] = new getID3();
}

/**
 * Implementation of handles
 * @ingroup handles
 */
function handles_amazon($file)
{
	$file = str_replace('\\', '/', $file);
	if(setting('admin_alias_enable') == true) $file = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $file);
	
	if(handles($file, 'audio') || handles($file, 'movies'))
	{
		return true;
	}
	elseif(is_dir($file))
	{
		// check if it is a directory that can be found
		if($file[strlen($file)-1] == '/') $file = substr($file, 0, strlen($file)-1);
					
		$dirs = split('/', $file);
		$tokens = tokenize($file);
		
		$album = @$dirs[count($dirs)-1];
		$artist = @$dirs[count($dirs)-2];
		
		if(isset($album) && isset($artist) && $album != '' && $artist != '' && $artist != 'Music' && in_array('music', $tokens['Unique']))
		{
			return true;
		}
	}
	
	return false;
}

/**
 * Helper function
 */
function get_amazon_info($file)
{
	if(handles($file, 'audio'))
	{
		// get information from database
		$audio = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'WHERE' => 'Filepath = "' . addslashes($file) . '"',
				'LIMIT' => 1
			)
		, false);
		if(count($audio) > 0)
		{
			$artist = $audio[0]['Artist'];
			$album = $audio[0]['Album'];
			
			return get_amazon_music_info($artist, $album);
		}
	}
	elseif(handles($file, 'movies'))
	{
	}
}

/**
 * Implementation of handle
 * @ingroup handle
 */
function add_amazon($file, $force = false)
{
	$file = str_replace('\\', '/', $file);
	
	if(handles($file, 'amazon'))
	{
		if(handles($file, 'audio') || is_dir($file))
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
						'SELECT' => 'audio',
						'WHERE' => 'Filepath = "' . addslashes($file) . '"',
						'LIMIT' => 1
					)
				, false);
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
					'SELECT' => 'amazon',
					'WHERE' => 'Filepath = "' . addslashes($artist . "\n" . $album) . '"',
					'LIMIT' => 1
				)
			, false);
			
			if( count($amazon) == 0 )
			{
				return amazon_add_music($artist, $album);
			}
			elseif($force)
			{
				// don't modify because amazon information doesn't change
				return $amazon[0]['id'];
			}
		}
		elseif(handles($file, 'movies'))
		{
			// get information from database
			// try and get information from file
		}
	}
	return false;
}


function get_amazon_music_info($artist, $album)
{
	
	$fileinfo = array();
	$fileinfo['Filepath'] = addslashes($artist . "\n" . $album);
	$fileinfo['AmazonId'] = '';
	$fileinfo['AmazonType'] = 'Music';
	$fileinfo['AmazonInfo'] = '';
	$fileinfo['Matches'] = '';
	$fileinfo['Thumbnail'] = '';
	
	$artist_tokens = tokenize($artist);
	$album_tokens = tokenize($album);
	
	// create url
	// do soundtracks seperately because they will already have a very limit selection
	if(in_array('soundtrack', $album_tokens['All']))
	{
		$tmp_some_tokens = array_unique(array_merge($artist_tokens['Some'], $album_tokens['Some']));
		$url = 'http://' . setting('amazon_server') . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . setting('amazon_dev_key');
		$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $tmp_some_tokens)) . '&ResponseGroup=Images,Similarities,Small,Tracks';
		$result = fetch($url);
	}
	else
	{
		$url = 'http://' . setting('amazon_server') . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . setting('amazon_dev_key');
		$url .= '&SearchIndex=Music&Artist=' . urlencode(join(' ', $artist_tokens['Some'])) . '&Title=' . urlencode(join(' ', $album_tokens['Few'])) . '&ResponseGroup=Images,Similarities,Small,Tracks';
		
		$result = fetch($url);
		
		if(preg_match('/\<Errors\>/i', $result['content']) !== 0)
		{
			$url = 'http://' . setting('amazon_server') . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . setting('amazon_dev_key');
			$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $artist_tokens['Some'])) . '&Title=' . urlencode(join(' ', $album_tokens['Few'])) . '&ResponseGroup=Images,Similarities,Small,Tracks';
			$result = fetch($url);
		}
		
		if(preg_match('/\<Errors\>/i', $result['content']) !== 0)
		{
			$tmp_some_tokens = array_unique(array_merge($artist_tokens['Some'], $album_tokens['Some']));
			$url = 'http://' . setting('amazon_server') . '/onca/xml?Service=AWSECommerceService&Version=2005-03-23&Operation=ItemSearch&ContentType=text%2Fxml&SubscriptionId=' . setting('amazon_dev_key');
			$url .= '&SearchIndex=Music&Keywords=' . urlencode(join(' ', $tmp_some_tokens)) . '&ResponseGroup=Images,Similarities,Small,Tracks';
			$result = fetch($url);
		}
	}
	
	// check if it was found
	if(preg_match('/\<Errors\>/i', $result['content']) !== 0)
	{
		return $fileinfo;
	}
	else
	{
		$match = preg_match('/\<Item\>(.*)\<\/Item\>/i', $result['content'], $matches);
		if(isset($matches[1]))
		{
			$items = preg_split('/\<\/Item\>\<Item\>/i', $matches[1]);
			
			// pick out best match
			$best = '';
			$best_tracks = '';
			$best_count = 0;
			$all_matches = array();
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
				$tracks = get_files(array('search_Artist' => '+' . join(' +', $artist_tokens['Few']), 'search_Album' => '+' . join(' +', $album_tokens['Few'])), $tmp_count, 'audio');
				
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
				
				// record all results
				$match = preg_match('/\<ASIN\>([a-z0-9]*)\<\/ASIN\>/i', $item, $matches);
				if(isset($matches[1]))
					$all_matches[] = addslashes($matches[1]);
			}
			
			// parse single result
			$match = preg_match('/\<ASIN\>([a-z0-9]*)\<\/ASIN\>/i', $best, $matches);
			if(!isset($matches[1]))
			{
				raise_error('Error reading AmazonId: ' . htmlspecialchars($best), E_DEBUG);
			}
			else
			{
				$fileinfo['AmazonId'] = addslashes($matches[1]);
			}
			$fileinfo['Matches'] = join(';', $all_matches);
			
			// parse tracks for later use
			$fileinfo['AmazonInfo'] = addslashes(serialize($best_tracks));
			
			$match = preg_match('/\<LargeImage\>\<URL\>(.*)\<\/URL\>/i', $best, $matches);
			if(isset($matches[1]))
			{
				$result = fetch($matches[1]);
				$fileinfo['Thumbnail'] = addslashes($result['content']);
			}
		}
	}
	
	return $fileinfo;
}

function amazon_add_music($artist, $album)
{
	
	// pull information from $info
	$fileinfo = get_amazon_music_info($artist, $album);

	raise_error('Adding Amazon Music: ' . $artist . ' - ' . $album, E_DEBUG);
	
	// add to database
	$id = $GLOBALS['database']->query(array('INSERT' => 'amazon', 'VALUES' => $fileinfo), false);

	return $id;
}

function amazon_add_movie($title)
{
	// pull information from $info
	$fileinfo = db_amazon_getMovieInfo($title);

	raise_error('Adding Amazon Movie: ' . $title, E_DEBUG);
	
	// add to database
	$id = $GLOBALS['database']->query(array('INSERT' => 'amazon', 'VALUES' => $fileinfo), false);
	
	return $id;
}

/**
 * Implementation of handler_get
 * @ingroup handler_get
 */
function get_amazon($request, &$count)
{
	// modify the request
	if(isset($request['file']))
	{
		if(handles($request['file'], 'audio'))
		{
			$audio = get_files(array('file' => $request['file'], 'audio_id' => (isset($request['audio_id'])?$request['audio_id']:0)), $tmp_count, 'audio');
			if(count($audio) > 0)
			{
				$files = $GLOBALS['database']->query(array(
						'SELECT' => 'amazon',
						'WHERE' => 'Filepath = "' . addslashes($audio[0]['Artist'] . "\n" . $audio[0]['Album']) . '"',
						'LIMIT' => 1
					)
				, true);
			}
			else
			{
				$files = array();
			}
		}
		elseif(handles($request['file'], 'movies'))
		{
			$movie = get_files(array('file' => $request['file'], 'video_id' => (isset($request['video_id'])?$request['video_id']:0)), $tmp_count, 'audio');
			if(count($movie) > 0)
			{
				$files = $GLOBALS['database']->query(array(
						'SELECT' => 'amazon',
						'WHERE' => 'Filepath = "' . addslashes($movie[0]['Title']) . '"',
						'LIMIT' => 1
					)
				, true);
			}
			else
			{
				$files = array();
			}
		}
		elseif(is_dir($request['file']))
		{
			if($request['file'][strlen($request['file'])-1] == '/') $request['file'] = substr($request['file'], 0, strlen($request['file'])-1);
			$dirs = split('/', $request['file']);
			
			$album = $dirs[count($dirs)-1];
			$artist = @$dirs[count($dirs)-2];
			$files = $GLOBALS['database']->query(array(
					'SELECT' => 'amazon',
					'WHERE' => 'Filepath = "' . addslashes($artist . "\n" . $album) . '"',
					'LIMIT' => 1
				)
			, true);
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
			
			if(strpos($request['dir'], '/') !== false)
			{
				$dirs = split('/', $request['dir']);
				$title = $dirs[0] . "\n" . $dirs[1];
			}
			else
			{
				$title = $request['dir'];
			}
			unset($request['dir']);
			$amazon = $GLOBALS['database']->query(array(
					'SELECT' => 'amazon',
					'WHERE' => 'Filepath = "' . addslashes($title) . '"',
					'LIMIT' => 1
				)
			, true);
			
			if(count($amazon) > 0)
			{
				if($amazon[0]['AmazonType'] = 'Music')
				{
					$title = split("\n", $title);
					$request['search_Artist'] = '=' . $title[0] . '=';
					$request['search_Album'] = '=' . $title[1] . '=';
					$files = get_db_audio($request, $tmp_count);
					
					if($amazon[0]['AmazonId'] != '')
						$amazon[0]['AmazonLink'] = 'http://www.amazon.com/dp/' . $amazon[0]['AmazonId'] . '/?SubscriptionId=' . AMAZON_DEV_KEY;
					$amazon[0]['Handler'] = 'db_audio';
					
					foreach($files as $i => $file)
					{
						$amazon[0]['Filepath'] = $file['Filepath'];
						$files[$i] = $amazon[0];
					}
					
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
			if(!isset($request['search_AmazonId']))
				$request['search_AmazonId'] = '/[a-z0-9]+/';
			// get files
			$files = get_files($request, $count, 'amazon');
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
			$files[$i]['Filepath'] = '/' . join('/', split("\n", $file['Filepath'])) . '/';
		$files[$i]['Filetype'] = 'FOLDER';
	}
	
	return $files;
}

/**
 * Implementation of handler_remove
 * @ingroup handler_remove
 */
function remove_amazon($file, $handler = NULL)
{
	// remove the amazon entry for whatever is passed in
	//  but only if the artist/album doesn't exist in the database anymore
	//  so only remove when the last file is removed
	//    always remove directories
	//    
}

