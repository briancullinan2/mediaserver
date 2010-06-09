<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_ampache()
{
	return array(
		'name' => lang('ampache title', 'Ampache Compatibility'),
		'description' => lang('ampache description', 'Compatibility support for the Ampache XMLRPC protocol.'),
		'privilage' => 1,
		'path' => __FILE__,
		'depends on' => array('database'),
		'template' => true,
	);
}

/**
 * Implementation of status
 * @ingroup status
 */
function status_ampache()
{
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

	// just incase this matters
	$request['cat'] = validate_cat(array('cat' => 'audio'));
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
				$files = get_ids(array('cat' => 'audio'), $tmp_count, $files);
					
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
				$files = get_ids(array('cat' => 'audio'), $tmp_count, $files);
					
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
				$files = get_ids(array('cat' => 'audio'), $tmp_count, $files);
					
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
				$files = get_ids(array('cat' => 'audio'), $tmp_count, $files);
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
		case 'search_songs':
			$request = array(
				'limit' => $request['limit'],
				'start' => $request['start'],
				'search' => $request['search']
			);
			$files = get_files($request, $count, 'audio');
								   
			// the ids handler will do the replacement of the ids
			if(count($files) > 0)
				$files = get_ids(array('cat' => 'audio'), $tmp_count, $files);
				
			// replace file path is actual path
			foreach($files as $i => $file)
			{
				if(setting('admin_alias_enable') == true) $files[$i]['Filepath'] = preg_replace($GLOBALS['alias_regexp'], $GLOBALS['paths'], $files[$i]['Filepath']);
			}
					
			// set the variables in the template		
			register_output_vars('files', $files);
		break;
	}
	
}


function theme_ampache()
{
	// set the content type to xml
	header('Content-type: text/xml');
	//header('Content-Disposition: attachment; filename=information.xml');
	
	echo '<?xml version="1.0" encoding="utf-8" ?>';
	?>
	<root><?php
	
	// if there is an error print that out and exit
	if(count($GLOBALS['user_errors']) > 0)
	{
		foreach($GLOBALS['user_errors'] as $i => $error)
		{
			if(strpos($error->message, ':') !== false)
			{
				$err = split(':', $error->message);
				?><error code="<?php echo $err[0]; ?>"><![CDATA[<?php echo $err[1]; ?>]]></error><?php
			}
		}
		?></root><?php
		exit;
	}
	
	// do different stuff based on action
	switch($GLOBALS['templates']['vars']['action'])
	{
		case 'ping':
	?>
	<server><![CDATA[<?php echo VERSION; ?>]]></server>
	<version><![CDATA[350001]]></version>
	<compatible><![CDATA[350001]]></compatible>
	<?php
		break;
		case 'handshake':
	?>
	<auth><![CDATA[<?php echo $GLOBALS['templates']['vars']['auth']; ?>]]></auth>
	<api><![CDATA[350001]]></api>
	<update><![CDATA[<?php echo date('c'); ?>]]></update>
	<songs><![CDATA[<?php echo $GLOBALS['templates']['vars']['song_count']; ?>]]></songs>
	<albums><![CDATA[<?php echo $GLOBALS['templates']['vars']['album_count']; ?>]]></albums>
	<artists><![CDATA[<?php echo $GLOBALS['templates']['vars']['artist_count']; ?>]]></artists>
	<genres><![CDATA[<?php echo $GLOBALS['templates']['vars']['genre_count']; ?>]]></genres>
	<playlists><![CDATA[0]]></playlists>
	<?php
		break;
		case 'artists':
			foreach($GLOBALS['templates']['vars']['files'] as $i => $artist)
			{
	?>
	<artist id="<?php echo $artist['id'] ; ?>"> 
	<name><![CDATA[<?php echo $artist['Artist']; ?>]]></name>
	<albums><?php echo $artist['AlbumCount']; ?></albums>
	<songs><?php echo $artist['SongCount']; ?></songs>
	</artist>
	<?php
			}
		break;
		case 'album':
		case 'albums':
		case 'artist_albums':
			
			foreach($GLOBALS['templates']['vars']['files'] as $i => $album)
			{
	?>
	<album id="<?php echo $album['id']; ?>">
	<name><![CDATA[<?php echo $album['Album']; ?>]]></name>
	<?php
	if($album['ArtistCount'] != 1)
	{
		?><artist id="0"><![CDATA[Various]]></artist><?php
	}
	else
	{
		?><artist id="<?php echo $album['id'] ; ?>"><![CDATA[<?php echo $album['Artist'] ; ?>]]></artist><?php
	}
	?>
	<year><?php echo $album['Year']; ?></year>
	<tracks><?php echo $album['SongCount']; ?></tracks>
	<disk>0</disk>
	<art><![CDATA[]]></art>
	</album>
	<?php
			}
		break;
		case 'song':
		case 'songs':
		case 'artist_songs':
		case 'album_songs':
		case 'search_songs':
		
			foreach($GLOBALS['templates']['vars']['files'] as $i => $song)
			{
	?>
	<song id="<?php echo $song['id']; ?>">
	<title><![CDATA[<?php echo $song['Title']; ?>]]></title>
	<artist id="<?php echo $song['id']; ?>"><![CDATA[<?php echo $song['Artist']; ?>]]></artist>
	<album id="<?php echo $song['id']; ?>"><![CDATA[<?php echo $song['Album']; ?>]]></album>
	<genre id="<?php echo $song['id']; ?>"><![CDATA[<?php echo $song['Genre']; ?>]]></genre>
	<track><?php echo $song['Track']; ?></track>
	<time><?php echo $song['Length']; ?></time>
	<url><![CDATA[<?php echo url('encode=mp3&id=' . $song['id'] . '&cat=db_audio&module=encode', true, true); ?>]]></url>
	<size><?php echo file_exists($song['Filepath'])?filesize($song['Filepath']):0; ?></size>
	<art><![CDATA[]]></art>
	</song>
	<?php
			}
		break;
	}
	
	
	?>
	</root>
<?php
}

