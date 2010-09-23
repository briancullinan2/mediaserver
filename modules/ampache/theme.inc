<?php

function theme__ampache()
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
		$error = $GLOBALS['user_errors'][0];
		if(strpos($error, ':') !== false)
		{
			$err = explode(':', $error, 2);
			?><error code="<?php echo $err[0]; ?>"><![CDATA[<?php echo $err[1]; ?>]]></error><?php
		}
		?></root><?php
		$GLOBALS['user_errors'] = array();
		return;
	}
	
	// do different stuff based on action
	switch($GLOBALS['output']['action'])
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
	<auth><![CDATA[<?php print $GLOBALS['output']['html']['auth']; ?>]]></auth>
	<api><![CDATA[350001]]></api>
	<update><![CDATA[<?php print date('c'); ?>]]></update>
	<songs><![CDATA[<?php print $GLOBALS['output']['html']['song_count']; ?>]]></songs>
	<albums><![CDATA[<?php print $GLOBALS['output']['html']['album_count']; ?>]]></albums>
	<artists><![CDATA[<?php print $GLOBALS['output']['html']['artist_count']; ?>]]></artists>
	<genres><![CDATA[<?php print $GLOBALS['output']['html']['genre_count']; ?>]]></genres>
	<playlists><![CDATA[0]]></playlists>
	<?php
		break;
		case 'artists':
			foreach($GLOBALS['output']['html']['files'] as $i => $artist)
			{
	?>
	<artist id="<?php print $artist['id'] ; ?>"> 
	<name><![CDATA[<?php print $artist['Artist']; ?>]]></name>
	<albums><?php print $artist['AlbumCount']; ?></albums>
	<songs><?php print $artist['SongCount']; ?></songs>
	</artist>
	<?php
			}
		break;
		case 'album':
		case 'albums':
		case 'artist_albums':
			
			foreach($GLOBALS['output']['html']['files'] as $i => $album)
			{
	?>
	<album id="<?php print $album['id']; ?>">
	<name><![CDATA[<?php print $album['Album']; ?>]]></name>
	<?php
	if($album['ArtistCount'] != 1)
	{
		?><artist id="0"><![CDATA[Various]]></artist><?php
	}
	else
	{
		?><artist id="<?php print $album['id'] ; ?>"><![CDATA[<?php print $album['Artist'] ; ?>]]></artist><?php
	}
	?>
	<year><?php print $album['Year']; ?></year>
	<tracks><?php print $album['SongCount']; ?></tracks>
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
		
			foreach($GLOBALS['output']['html']['files'] as $i => $song)
			{
	?>
	<song id="<?php print $song['id']; ?>">
	<title><![CDATA[<?php print $song['Title']; ?>]]></title>
	<artist id="<?php print $song['id']; ?>"><![CDATA[<?php echo $song['Artist']; ?>]]></artist>
	<album id="<?php print $song['id']; ?>"><![CDATA[<?php echo $song['Album']; ?>]]></album>
	<genre id="<?php print $song['id']; ?>"><![CDATA[<?php echo $song['Genre']; ?>]]></genre>
	<track><?php print $song['Track']; ?></track>
	<time><?php print $song['Length']; ?></time>
	<url><![CDATA[<?php print url('encode/mp3/' . $song['id'] . '/audio', true, true); ?>]]></url>
	<size><?php print file_exists($song['Filepath'])?filesize($song['Filepath']):0; ?></size>
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

