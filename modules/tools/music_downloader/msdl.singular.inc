<?php

function get_music_download_manual($request)
{
	$infos = array();

	if(!setting('discogs_enable') || !setting('search_enable'))
	{
		$infos['advanced_select'] = array(
			'name' => 'Advanced Select',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'Cannot use advanced select because the Discogs module is not available for comparison.',
				),
			),
			'text' => 'Cannot use advanced select'
		);
	}
	else
	{
		// parse through results and compare with what we already have
		$results = db_assoc('SELECT * FROM download_services WHERE Search=? AND Type="audio"', array(session('download_services')));
		$results = unserialize($results[0]['Results']);
		
		// group results
		$search_groups = tokenize_groups($results['titles']);
		
		// do a search in local database to find artist
		$search = filter_search(array('search_Artist' => $request['service_search'], 'handler' => 'audio'));
		$artists = db_list('SELECT Artist,' . $search['columns'] . ' FROM audio WHERE ' . $search['where'] . ' ORDER BY ' . $search['order'], array(), 'Artist');
		
		// get the closest artist result
		$closest = get_closest($request['service_search'], $artists);
		if(!$closest)
		{
			$infos['advanced_select'] = array(
				'name' => 'Advanced Select',
				'status' => 'warn',
				'description' => array(
					'list' => array(
						'Cannot pair search text with any artist already in the database.',
					),
				),
				'text' => 'Cannot use advanced select'
			);
		}
		else
		{
			list($artist) = $closest;
			// this will return the discogs releases paired with the albums on disk
			$artists = db_assoc('SELECT * FROM audio WHERE Artist=? GROUP BY Album', array($artist));
			
			// get discogs information
			merge_id_info($artists, 'audio', $cache_updates);
			foreach($artists as $i => $artist)
			{
				if($artist['discogs_id'] == 0 && handles($artist['Filepath'], 'discogs'))
				{
					$GLOBALS['settings']['deep_select'] = 2;
					$result = add($artist['Filepath'], 'discogs');
					if(is_array($result))
					{
						$artists[$i] = array('discogs_id' => $result['id']) + array_merge($result, $artist);
						$ids = array_intersect_key($artists[$i], array_flip(get_id_fields()));
						db_query('UPDATE ids ' . sql_update($ids) . ' WHERE id=' . $artist['id'], array_values($ids));
					}
				}
				else
				{
					$discogs = db_assoc('SELECT * FROM discogs WHERE id=?', array($artist['discogs_id']));
					
					$artists[$i] = array_merge($discogs[0], $artist);
				}
			}
			
			// get list of missing albums from discogs artist
			try {
				$artist_xml = new SimpleXMLElement($artists[0]['ArtistXml']);
			
				$discogs_albums = get_xml_list($artist_xml->xpath('//release[@type="Main"]/title'));
				
				$discogs_albums = tokenize_groups($discogs_albums);
				
			} catch (Exception $e) {
				exception_wrapper($e);
			}
			
			// pair up all the releases with the search results
			foreach($artists as $i => $album)
			{
				// get album name
				list($album) = get_closest($artist . ' ' . $album['Album'], array_keys($search_groups));

				// select all the other albums for download
				unset($search_groups[$album]);
				
				// get album name
				list($album) = get_closest($artist . ' ' . $album['Album'], array_keys($discogs_albums));

				// select all the other albums for download
				unset($discogs_albums[$album]);
			}
			
			foreach($discogs_albums as $title => $related)
			{
				// select missing albums
				list($album) = get_closest($title, array_keys($search_groups));
				
				print_r(array_intersect_key($results['titles'], array_flip($search_groups[$album])));
				
			exit;
			}
			
			// remove indices that have nothing in common with the search string
			foreach($groups as $title => $related)
			{
				//if(levenshtein($request['service_search'], $title) >= strlen($title))
				//	unset($groups[$title]);
			}
		}
	}
		
	return $infos;
}

