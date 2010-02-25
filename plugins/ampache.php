<?php

function register_ampache()
{
	return array(
		'name' => 'ampache',
		'description' => 'Compatibility support for the Ampache XMLRPC protocol.',
		'privilage' => 1,
		'path' => __FILE__,
		'notemplate' => true
	);
}

function validate_auth($request)
{
	return $request['auth'];
}

function validate_action($request)
{
	if(isset($request['auth']) && !isset($request['action']))
		return 'ping';
	if(in_array($request['action'], array('handshake', 'artists', 'artist_albums', 'album_songs')))
		return $request['action'];
	else
		return 'ping';
}

function validate_filter($request)
{
	$request['id'] = $request['filter'];
	return validate_id($request);
}

function output_ampache($request)
{
	set_time_limit(0);
	
	// do some ampache compatibility stuff
	$fp = fopen('/tmp/test.txt', 'a');
	fwrite($fp, var_export($_SERVER, true));
	fclose($fp);
	
	$request['action'] = validate_action($request);
	$request['auth'] = validate_auth($request);
	
	// check for the action
	switch($request['action'])
	{
		case 'ping':
		// report the session has expired
		if($request['auth'] != session_id())
		{
			PEAR::raiseError('Session Expired', E_USER);
			$error_code = '401';
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
			)) . ') as counter',
			'COLUMNS' => 'count(*)'
		), true);
		$album_count = $result[0]['count(*)'];
		
		// artist count
		$result = $GLOBALS['database']->query(array(
			'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
				'SELECT' => 'audio',
				'GROUP' => 'Artist'
			)) . ') as counter',
			'COLUMNS' => 'count(*)'
		), true);
		$artist_count = $result[0]['count(*)'];
		
		// genre count
		$result = $GLOBALS['database']->query(array(
			'SELECT' => '(' . $GLOBALS['database']->statement_builder(array(
				'SELECT' => 'audio',
				'GROUP' => 'Genre'
			)) . ') as counter',
			'COLUMNS' => 'count(*)'
		), true);
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
			'GROUP' => 'Artist,Album',
			'COLUMNS' => 'MIN(id) as id,Artist,count(*) as SongCount'
		), true);
		
		// go through and merge the artist album and counts
		$files = array();
		foreach($result as $i => $artist)
		{
			if(!isset($artists[$artist['Artist']]))
			{
				$files[$artist['Artist']] = array(
					'Artist' => $artist['Artist'],
					'SongCount' => $artist['SongCount'],
					'AlbumCount' => 1,
					'id' => $artist['id']
				);
			}
			else
			{
				$files[$artist['Artist']]['SongCount'] += $artist['SongCount'];
				$files[$artist['Artist']]['AlbumCount'] += 1;
			}
		}
		
		// set the variables in the template		
		$register_output_vars('files', $files);
		
		break;
		case 'artist_albums':
		$request['filter'] = validate_filter($request);
		
		// get a list of albums for a particular artist
		// first look up song by supplied ID
		$result = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'WHERE' => 'id = ' . intval($request['filter'])
		), true);
		
		// get the list of albums
		$files = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'GROUP' => 'Album',
			'COLUMNS' => '*,MIN(id) as id,count(*) as SongCount',
			'WHERE' => 'Artist = "' . addslashes($result[0]['Artist']) . '"'
		), true);
		
		// set the variables in the template		
		register_output_vars('files', $files);
		
		break;
		case 'album_songs':
		// get a list of songs for a particular album
		// first look up song by supplied ID
		$artist_album = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'WHERE' => 'id = ' . intval($request['filter'])
		), true);
		
		// get the id for genre
		$result = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'COLUMNS' => 'MIN(id) as id',
			'WHERE' => 'Genre = ' . addslashes($artist_album[0]['Genre'])
		), true);
		$genre_id = $result[0]['id'];
		
		// get the min id for artist
		$result = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'COLUMNS' => 'MIN(id) as id',
			'WHERE' => 'Artist = ' . addslashes($artist_album[0]['Artist'])
		), true);
		$artist_id = $result[0]['id'];
		
		// get the list of songs
		$files = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'WHERE' => 'Album = "' . addslashes($artist_album[0]['Album']) . '" AND Artist = "' . addslashes($artist_album[0]['Artist']) . '"'
		), true);
		
		// the ids module will do the replacement of the ids
		if(count($files) > 0)
			$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $files);
				
		// set the variables in the template		
		register_output_vars('files', $files);
		
		register_output_vars('genre_id', $genre_id);
		
		register_output_vars('artist_id', $artist_id);
		
		break;
	}
	
	// display template
	// this plugin will probably never use a smarty template
	include $GLOBALS['templates']['TEMPLATE_AMPACHE'];

}
