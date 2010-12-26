<?php

function get_music_download_manual($request)
{
	$infos = array();

	$download_services = setting('all_download_services', 'audio');

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
		$service_results = db_assoc('SELECT * FROM download_services WHERE Search=? AND Type="audio"', array(session('download_services')));
		if(!isset($service_results[0]))
			return array();
		$results = unserialize($service_results[0]['Results']);
		
		// group results
		$search_groups = tokenize_groups($results['titles']);
//foreach($search_groups as $tokens => $titles)
//{
//	print $tokens . ":\n";
//	print_r(array_intersect_key($results['titles'], array_flip($titles)));
//}
//print_r(array_keys($search_groups));
//exit;
		
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
			list($search_artist) = $closest;
			// this will return the discogs releases paired with the albums on disk
			$artist_albums = db_assoc('SELECT * FROM audio WHERE Artist=? GROUP BY Album', array($search_artist));
			
			// get discogs information
			merge_id_info($artist_albums, 'audio', $cache_updates);
			foreach($artist_albums as $i => $artist)
			{
				if($artist['discogs_id'] == 0)
				{
					$GLOBALS['settings']['deep_select'] = 2;
					$result = add($artist['Filepath'], 'discogs');
					if(is_array($result))
					{
						$artist_albums[$i] = array('discogs_id' => $result['id']) + array_merge($result, $artist);
						$ids = array_intersect_key($artist_albums[$i], array_flip(get_id_fields()));
						db_query('UPDATE ids ' . sql_update($ids) . ' WHERE id=' . $artist['id'], array_values($ids));
					}
				}
				else
				{
					$discogs = db_assoc('SELECT * FROM discogs WHERE id=?', array($artist['discogs_id']));
					
					$artist_albums[$i] = array_merge($discogs[0], $artist);
				}
			}
			
			// get list of missing albums from discogs artist
			try {
				$discogs_titles = array();
				foreach($artist_albums as $i => $artist_album)
				{
					if(isset($artist_album['ArtistXml']))
					{
						$artist_xml = new SimpleXMLElement($artist_album['ArtistXml']);
			
						$discogs_titles = get_xml_list($artist_xml->xpath('//release[@type="Main"]/title'));
						break;
					}
				}
				
				$discogs_albums = tokenize_groups($discogs_titles);
				unset($discogs_albums['']);
//foreach($discogs_albums as $tokens => $titles)
//{
//	print $tokens . ":\n";
//	print_r(array_intersect_key($discogs_titles, array_flip($titles)));
//}
//print_r(array_keys($discogs_albums));
//exit;
				
			} catch (Exception $e) {
				exception_wrapper($e);
			}
			
			// pair up all the releases with the search results
			foreach($artist_albums as $i => $album)
			{
				// get album name
				$result = get_closest($search_artist . ' ' . $album['Album'], array_keys($search_groups));
				
				if($result)
				{
//print $search_artist . ' ' . $album['Album'] . ' - ' . $result['value'] . "\n";
					// select all the other albums for download
					unset($search_groups[$result['value']]);
				}
				
				// get album name
				$result = get_closest($album['Album'], array_keys($discogs_albums));

				if($result)
				{
//print $album['Album'] . ' - ' . $result['value'] . "\n";
					// select all the other albums for download
					unset($discogs_albums[$result['value']]);
				}
			}
			
			// remove indices that have nothing in common with the search string
			foreach($search_groups as $title => $related)
			{
				if(levenshtein($request['service_search'], $title) > max(strlen($title), strlen($request['service_search'])) * .80)
					unset($search_groups[$title]);
			}
			
			$selected = array();
			$to_be_searched = array();
			foreach($discogs_albums as $title => $related)
			{
				// select missing albums with 
				list($album) = get_closest($title, array_keys($search_groups));
				if(!isset($album))
				{
					$to_be_searched[] = $title;
					continue;
				}
				
				$album_formats = array_intersect_key($results['titles'], array_flip($search_groups[$album]));

				// select the preffered format
				$music_formats = explode(',', setting('music_formats'));
				foreach($music_formats as $i => $format)
				{
					$result = get_closest($format, $album_formats);
					if($result)
					{
						$selected[] = $result['key'];
						break;
					}
				}
			}
			$selected = array_unique($selected);
//print_r(array_intersect_key($results['titles'], array_flip($selected)));
//exit;
			
			if(validate($request, 'download_automatically') || true)
			{
				$failed = array();
				$succeeded = array();
				
				// if the auto download option is set, proceed with downloading of all torrents
				foreach($selected as $i => $key)
				{
					// download and save
					$result = fetch($results['urls'][$key], array(), array(), session('download_services_' . $service_results[0]['Service']));
					
					if(strlen($result['content']) == 0)
					{
						$failed[] = $key;
						continue;
					}
					
					// get the filename from headers or from search results
					$filename = get_filename($result['headers']);
					if(!$filename)
						$filename = $results['titles'][$key] . '.torrent';
					$path = $download_services[$service_results[0]['Service']]['savepath'] . generic_validate_filename(array('filename' => $filename), 'filename');
					
					// write the streams
					if(($fh = fopen($path, 'w')))
					{
						fwrite($fh, $result['content']);
						fclose($fh);
						
						$succeeded[] = $key;
					}
				}
				
				if(count($failed) > 0)
				{
					$links = array();
					foreach($failed as $i => $key)
					{
						$links[] = array(
							'link' => array('text' => $results['titles'][$key], 'url' => $results['urls'][$key])
						);
					}
					$infos['failed_downloads'] = array(
						'name' => 'Failed to Download',
						'status' => 'warn',
						'description' => array(
							'list' => array(
								'Failed to download some of the files.',
							),
						),
						'text' => array('Failed to download:', $links),
					);
				}
				
				$links = array();
				foreach($succeeded as $i => $key)
				{
					$links[] = array(
						'link' => array('text' => $results['titles'][$key], 'url' => $results['urls'][$key])
					);
				}
				$infos['succeeded_downloads'] = array(
					'name' => 'Successfully Downloaded',
					'status' => '',
					'description' => array(
						'list' => array(
							'These files have been downloaded and saved to.',
						),
					),
					'text' => array('Successfully downloaded:', $links),
				);
			}
			else
			{
				$links = array();
				foreach($selected as $i => $key)
				{
					$links[] = array(
						'link' => array('text' => $results['titles'][$key], 'url' => $results['urls'][$key])
					);
				}
				$infos['selected_downloads'] = array(
					'name' => 'Selected Downloads',
					'status' => '',
					'description' => array(
						'list' => array(
							'These files have been selected for download.',
						),
					),
					'text' => array('Selected downloads:', $links),
				);
			}
		}
	}
		
	return $infos;
}

