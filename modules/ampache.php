<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_ampache()
{
	return array(
		'name' => 'Ampache Compatibility',
		'description' => 'Compatibility support for the Ampache XMLRPC protocol.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, the auth key is any string if specified
 */
function validate_auth($request)
{
	if(isset($request['auth']))
		return $request['auth'];
}

/**
 * Implementation of validate
 * @ingroup validate
 * @return NULL by default, creates an error if action is invalid
 */
function validate_action($request)
{
	if(isset($request['action']) && in_array($request['action'], array(
		'handshake',
		'artists',
		'artist_albums',
		'album_songs',
		'albums',
		'album',
		'artist_songs',
		'songs',
		'song',
		'search_songs',
		'ping'
	)))
		return $request['action'];
	else
		PEAR::raiseError('405:Invalid Request', E_USER);
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_ampache($request)
{
	set_time_limit(0);
	
	// do some ampache compatibility stuff
	$fp = fopen('/tmp/test.txt', 'a');
	fwrite($fp, var_export($_SERVER, true));
	fclose($fp);
	
	$request['action'] = validate_action($request);
	$request['auth'] = validate_auth($request);
	$request['start'] = validate_start($request);
	$request['limit'] = validate_limit($request);
	
	register_output_vars('action', $request['action']);
	
	// check for the action
	switch($request['action'])
	{
		case 'ping':
			// report the session has expired
			if($request['auth'] != session_id())
			{
				PEAR::raiseError('401:Session Expired', E_USER);
			}
			
		break;
		case 'handshake':
			// send out the ssid information
			// send out some counts instead of running select
			// song count
			$result = $GLOBALS['database']->query(array('SELECT' => 'audio', 'COLUMNS' => 'count(*)'), true);
			$song_count = $result[0]['count(*)'];
			
			// album count
			$result = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Album'
				), true) . ') as counter',
				'COLUMNS' => 'count(*)'
			), false);
			$album_count = $result[0]['count(*)'];
			
			// artist count
			$result = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Artist'
				), true) . ') as counter',
				'COLUMNS' => 'count(*)'
			), false);
			$artist_count = $result[0]['count(*)'];
			
			// genre count
			$result = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Genre'
				), true) . ') as counter',
				'COLUMNS' => 'count(*)'
			), false);
			$genre_count = $result[0]['count(*)'];
			
			// set the variables in the template
			register_output_vars('auth', session_id());
			
			register_output_vars('song_count', $song_count);
			
			register_output_vars('album_count', $album_count);
			
			register_output_vars('artist_count', $artist_count);
	
			register_output_vars('genre_count', $genre_count);
			
		break;
		case 'artists':
			// get a simple list of all the artists
			$result = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'GROUP' => 'Artist',
				'COLUMNS' => 'MIN(id) as id,Artist,count(*) as SongCount',
				'LIMIT' => $request['start'] . ',' . $request['limit'],
				'ORDER' => 'Artist'
			), true);
			
			// album counts
			$album_count = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Artist,Album',
					'COLUMNS' => 'Artist'
				), true) . ') as counter',
				'COLUMNS' => 'count(*) as AlbumCount',
				'GROUP' => 'Artist',
				'ORDER' => 'Artist',
				'LIMIT' => $request['start'] . ',' . $request['limit']
			), false);
			
			// go through and merge the artist album and counts
			$files = array();
			foreach($result as $i => $artist)
			{
				$files[$artist['Artist']] = array(
					'Artist' => $artist['Artist'],
					'SongCount' => $artist['SongCount'],
					'AlbumCount' => $album_count[$i]['AlbumCount'],
					'id' => $artist['id']
				);
			}
			
			// set the variables in the template		
			register_output_vars('files', $files);
			
		break;
		case 'artist_albums':
			$request['id'] = validate_id($request);
			
			// get a list of albums for a particular artist
			// first look up song by supplied ID
			$artist = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'WHERE' => 'id = ' . intval($request['id'])
			), true);
			
			// get the list of albums
			$result = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'GROUP' => 'Album',
				'COLUMNS' => '*,MIN(id) as id,count(*) as SongCount',
				'WHERE' => 'Artist = "' . addslashes($artist[0]['Artist']) . '"',
				'ORDER' => 'Album'
			), true);
			
			// artist counts
			$artist_count = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Album,Artist',
					'COLUMNS' => 'Album',
					'WHERE' => 'Artist = "' . addslashes($artist[0]['Artist']) . '"'
				), true) . ') as counter',
				'COLUMNS' => 'count(*) as ArtistCount',
				'ORDER' => 'Album',
				'GROUP' => 'Album'
			), false);
			
			// go through and merge the artist album and counts
			$files = array();
			foreach($result as $i => $album)
			{
				$files[$album['Album']] = array(
					'Artist' => $album['Artist'],
					'Album' => $album['Album'],
					'Year' => $album['Year'],
					'ArtistCount' => $artist_count[$i]['ArtistCount'],
					'SongCount' => $album['SongCount'],
					'id' => $album['id']
				);
			}
			
			// set the variables in the template		
			register_output_vars('files', $files);
			
		break;
		case 'albums':
			// get a simple list of all the artists
			$result = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'GROUP' => 'Album',
				'ORDER' => 'Album',
				'COLUMNS' => 'MIN(id) as id,Artist,Year,Album,count(*) as SongCount',
				'LIMIT' => $request['start'] . ',' . $request['limit']
			), true);
			
			// artist counts
			$artist_count = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Album,Artist',
					'COLUMNS' => 'Album'
				), true) . ') as counter',
				'ORDER' => 'Album',
				'COLUMNS' => 'count(*) as ArtistCount',
				'GROUP' => 'Album',
				'LIMIT' => $request['start'] . ',' . $request['limit']
			), false);
			
			// go through and merge the artist album and counts
			$files = array();
			foreach($result as $i => $album)
			{
				$files[$album['Album']] = array(
					'Artist' => $album['Artist'],
					'Album' => $album['Album'],
					'Year' => $album['Year'],
					'ArtistCount' => $artist_count[$i]['ArtistCount'],
					'SongCount' => $album['SongCount'],
					'id' => $album['id']
				);
			}
			
			// set the variables in the template		
			register_output_vars('files', $files);
			
		break;
		case 'album':
			$request['id'] = validate_id($request);
	
			// get a list of songs for a particular album
			// first look up song by supplied ID
			$albums = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'COLUMNS' => 'MIN(id) as id,Artist,Year,Album,count(*) as SongCount',
				'GROUP' => 'Album',
				'WHERE' => 'Album = (' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'COLUMNS' => 'Album',
					'WHERE' => 'id = ' . intval($request['id'])
				), false) . ')'
			), true);
			$album = $albums[0];
			
			// artist counts
			$artist_count = $GLOBALS['database']->query(array(
				'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
					'SELECT' => 'audio',
					'GROUP' => 'Album,Artist',
					'COLUMNS' => 'Album',
					'WHERE' => 'Album = "' . addslashes($album['Album']) . '"'
				), true) . ') as counter',
				'COLUMNS' => 'count(*) as ArtistCount',
				'GROUP' => 'Album'
			), false);
			
			// create the list of albums with only 1 entry
			$files = array();
			$files[$album['Album']] = array(
				'Artist' => $album['Artist'],
				'Album' => $album['Album'],
				'Year' => $album['Year'],
				'ArtistCount' => $artist_count[0]['ArtistCount'],
				'SongCount' => $album['SongCount'],
				'id' => $album['id']
			);
			
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
		case 'song':
			$request['id'] = validate_id($request);
	
			// get a list of songs for a particular album
			// first look up song by supplied ID
			$songs = $GLOBALS['database']->query(array(
				'SELECT' => 'ids',
				'COLUMNS' => 'audio_id',
				'WHERE' => 'id = ' . intval($request['id'])
			), true);
			
			$files = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'WHERE' => 'id = ' . intval($songs[0]['audio_id'])
			), true);			
			
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
		case 'songs':
			
			// get the list of songs
			$files = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'LIMIT' => $request['start'] . ',' . $request['limit'],
				'WHERE' => 'Title != ""',
				'ORDER' => 'Title'
			), true);
			
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
		case 'artist_songs':
			$request['id'] = validate_id($request);
	
			// get a list of songs for a particular album
			// first look up song by supplied ID
			$artist_album = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'WHERE' => 'id = ' . intval($request['id'])
			), true);
			
			// get the list of songs
			$files = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'ORDER' => 'Title',
				'WHERE' => 'Artist = "' . addslashes($artist_album[0]['Artist']) . '"'
			), true);
			
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;			
		case 'album_songs':
			$request['id'] = validate_id($request);
	
			// get a list of songs for a particular album
			// first look up song by supplied ID
			$artist_album = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'WHERE' => 'id = ' . intval($request['id'])
			), true);
			
			// get the list of songs
			$files = $GLOBALS['database']->query(array(
				'SELECT' => 'audio',
				'ORDER' => 'Track',
				'WHERE' => 'Album = "' . addslashes($artist_album[0]['Album']) . '" AND Artist = "' . addslashes($artist_album[0]['Artist']) . '"'
			), true);
			
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
		case 'search_songs':
			$request = array(
				'limit' => $request['limit'],
				'start' => $request['start'],
				'search' => $request['search']
			);
			$files = db_audio::get($request, $count);
								   
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
				
			// replace file path is actual path
			foreach($files as $i => $file)
			{
				if(setting('use_alias') == true) $files[$i]['Filepath'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $files[$i]['Filepath']);
			}
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
	}
	
}
