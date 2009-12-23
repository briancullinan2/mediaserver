<?php
set_time_limit(0);

// Variables Used:
//  files, error, error_code, auth, album_count, song_count, artist_count, genre_count, album_id, artist_id, genre_id
// Shared Variables:

// this script uses a lot of custom queries because we don't need all the information the module api would return
//  we also want it to be pretty fast

// include some common stuff
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'common.php';

// do some ampache compatibility stuff
$fp = fopen('/tmp/test.txt', 'a');
fwrite($fp, var_export($_SERVER, true));
fclose($fp);

$error = '';

if(isset($_REQUEST['auth']) && !isset($_REQUEST['action']))
	$_REQUEST['action'] = 'ping';

// check for the action
if(isset($_REQUEST['action']))
{
	switch($_REQUEST['action'])
	{
		case 'ping':
		// report the session has expired
		if($_REQUEST['auth'] != session_id())
		{
			$error = 'Session Expired';
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
		$GLOBALS['smarty']->assign('auth', session_id());
		
		$GLOBALS['smarty']->assign('song_count', $song_count);
		
		$GLOBALS['smarty']->assign('album_count', $album_count);
		
		$GLOBALS['smarty']->assign('artist_count', $artist_count);

		$GLOBALS['smarty']->assign('genre_count', $genre_count);
		
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
		$GLOBALS['smarty']->assign('files', $files);
		
		break;
		case 'artist_albums':
		// get a list of albums for a particular artist
		// first look up song by supplied ID
		$result = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'WHERE' => 'id = ' . intval($_REQUEST['filter'])
		), true);
		
		// get the list of albums
		$files = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'GROUP' => 'Album',
			'COLUMNS' => '*,MIN(id) as id,count(*) as SongCount',
			'WHERE' => 'Artist = "' . addslashes($result[0]['Artist']) . '"'
		), true);
		
		// set the variables in the template		
		$GLOBALS['smarty']->assign('files', $files);
		
		break;
		case 'album_songs':
		// get a list of songs for a particular album
		// first look up song by supplied ID
		$artist_album = $GLOBALS['database']->query(array(
			'SELECT' => 'audio',
			'WHERE' => 'id = ' . intval($_REQUEST['filter'])
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
			$files = db_ids::get(array('cat' => 'db_audio'), $tmp_count, $tmp_error, $files);
				
		// set the variables in the template		
		$GLOBALS['smarty']->assign('files', $files);
		
		$GLOBALS['smarty']->assign('genre_id', $genre_id);
		
		$GLOBALS['smarty']->assign('artist_id', $artist_id);
		
		break;
		default:
			$_REQUEST['action'] = 'ping';
	}
}



// display template
// this plugin will probably never use a smarty template
include $GLOBALS['templates']['TEMPLATE_AMPACHE'];




?>