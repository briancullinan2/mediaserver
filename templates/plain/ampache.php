<?php

function theme_plain_ampache()
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
	<url><![CDATA[<?php echo generate_href('encode=mp3&id=' . $song['id'] . '&cat=db_audio&plugin=encode', true, true); ?>]]></url>
	<size><?php echo filesize($song['Filepath']); ?></size>
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

