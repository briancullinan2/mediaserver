<?php

// set the content type to xml
header('Content-type: text/xml');
//header('Content-Disposition: attachment; filename=information.xml');

echo '<?xml version="1.0" encoding="utf-8" ?>';
?>
<root><?php

// do different stuff based on action
switch($_REQUEST['action'])
{
	case 'ping':
		if($_REQUEST['auth'] != session_id())
		{
			?><error code="401"><![CDATA[Session Expired]]></error><?php
		}
	break;
	case 'handshake':
		?>
		<auth><![CDATA[<?php echo session_id(); ?>]]></auth>
		<api><![CDATA[<?php echo intval(VERSION); ?>]]></api>
		<songs><![CDATA[<?php echo $song_count; ?>]]></songs>
		<albums><![CDATA[<?php echo $album_count; ?>]]></albums>
		<artists><![CDATA[<?php echo $artist_count; ?>]]></artists>
		<genres><![CDATA[<?php echo $genre_count; ?>]]></genres>
		<playlists><![CDATA[0]]></playlists>
		<?php
	break;
	case 'artists':
		foreach($files as $i => $artist)
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
	case 'artist_albums':
		// get lowest id
		$artist_id = 0;
		foreach($files as $i => $album)
		{
			if($artist_id == 0 || $album['id'] < $artist_id)
			{
				$artist_id = $album['id'];
			}
		}
		
		foreach($files as $i => $album)
		{
		?>
		<album id="<?php echo $album['id']; ?>">
		<name><![CDATA[<?php echo $album['Album']; ?>]]></name>
		<artist id="<?php echo artist_id; ?>"><![CDATA[<?php echo $album['Artist']; ?>]]></artist>
		<year><?php echo $album['Year']; ?></year>
		<tracks><?php echo $album['SongCount']; ?></tracks>
		<disk>0</disk>
		<art><![CDATA[]]></art>
		</album>
		<?php
		}
	break;
	case 'album_songs':
		// get album id
		$album_id = 0;
		foreach($files as $i => $song)
		{
			if($album_id == 0 || $song['id'] < $album_id)
			{
				$album_id = $song['id'];
			}
		}
	
		foreach($files as $i => $song)
		{
		?>
		<song id="<?php echo $song['id']; ?>">
		<title><![CDATA[<?php echo $song['Title']; ?>]]></title>
		<artist id="<?php echo $artist_id; ?>"><![CDATA[<?php echo $song['Artist']; ?>]]></artist>
		<album id="<?php echo $album_id; ?>"><![CDATA[<?php echo $song['Album']; ?>]]></album>
		<genre id="<?php echo $genre_id; ?>"><![CDATA[<?php echo $song['Genre']; ?>]]></genre>
		<track><?php echo $song['Track']; ?></track>
		<time><?php echo $song['Length']; ?></time>
		<url><![CDATA[<?php echo HTML_DOMAIN . HTML_PLUGINS . 'encode.php/db_audio/' . $song['id'] . '/MP3/' . basename($song['Filepath']) . '.mp3'; ?>]]></url>
		<size><?php echo filesize($song['Filepath']); ?></size>
		<art><![CDATA[]]></art>
		</song>
		<?php
		}
	break;
}




?>
</root>