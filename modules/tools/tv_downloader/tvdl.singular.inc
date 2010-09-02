<?php


/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_info_singular_step_television($request)
{
	if(isset($request['info_singular_step_television']) &&
		in_array($request['info_singular_step_television'], array('login', 'shows', 'download'))
	)
		return $request['info_singular_step_television'];
}

/**
 * Helper function for outputting a single piece of the infos
 * @param request the request to process
 */
function output_tv_download_singular($request)
{
	$request['info_singular_step_television'] = validate($request, 'info_singular_step_television');
	
	switch($request['info_singular_step_television'])
	{
		case 'login':
			$infos = get_tv_download_login($request);
			break;
		case 'shows':
			$infos = get_tv_download_shows($request);
			break;
		case 'download':
			$infos = get_tv_download_downloads($request);
			break;
		default:
			$infos = array();
	}
	
	theme('form_tableset', $infos);
}

function get_tv_download_login($request)
{
	// log in to my episodes first
	$result = television_myepisodes_login();
	
	$infos = array();
	
	if($result['status'] == false)
	{
		$infos['television_login'] = array(
			'name' => 'MyEpisodes Login',
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'Failed to login to my episodes while fetching the login page.',
				),
			),
			'text' => 'Login Failed!'
		);
	}
	else
	{
		// login succeeded
		$infos['television_login'] = array(
			'name' => 'MyEpisodes Login',
			'status' => '',
			'description' => array(
				'list' => array(
					'Login to myepisodes.com successful!',
				),
			),
			'text' => 'Login Succeeded!'
		);
		
		// log in to services here
		if(setting('nzbservices_enable') == true)
			$infos = array_merge(nzbservices_singular_result(), $infos);
		if(setting('torservices_enable') == true)
			$infos = array_merge(torservices_singular_result(), $infos);
		
		// download episode list
		$infos['myepisodes_shows'] = array(
			'name' => 'MyEpisodes TV Shows',
			'status' => '',
			'description' => array(
				'list' => array(
					'Loading TV shows.',
				),
			),
			'text' => array(
				'loading' => 'Loading...'
			),
			'type' => 'theme',
			'value' => 'singular',
			'href' => url('admin/tools/television/downloader/shows', true),
		);
	}
	
	return $infos;
}

function get_tv_download_shows($request)
{
	// get list of shows
	$shows = television_myepisodes_fetch_shows();
	
	$infos = array();
	
	$infos['myepisodes_shows'] = array(
		'name' => 'MyEpisodes TV Shows',
		'status' => '',
		'description' => array(
			'list' => array(
				'These are all the TV Shows you are subscribed to.',
			),
		),
		'value' => array(
			'TV Shows:',
			'list' => $shows['all_shows'],
		),
	);
	
	// list of shows to download
	$infos['myepisodes_episodes'] = array(
		'name' => 'New Episodes',
		'status' => '',
		'description' => array(
			'list' => array(
				'These are all the new episodes that need to be downloaded.',
			),
		),
		'value' => array(
			'Episodes for download:',
			'list' => $shows['new_episodes']['combined']
		),
	);
	
	if(count(setting('all_nzbservices')) > 0 || count(setting('torservices')) > 0)
	{
		// add an entry for each show
		foreach($shows['new_episodes']['shows'] as $i => $show)
		{
			$infos['myepisodes_shows_' . $i] = array(
				'name' => 'Searching for ' . $show,
				'status' => '',
				'description' => array(
					'list' => array(
						'Searching NZB Services for show ' . $shows['new_episodes']['combined'][$i] . '.',
					),
				),
				'text' => array(
					'loading' => 'Searching for NZB...'
				),
				'type' => 'theme',
				'value' => 'singular',
				'href' => url('admin/tools/television/downloader/download' . 
					'?episode=' . urlencode($shows['new_episodes']['episodes'][$i]) . 
					'&showname=' . urlencode($show) . 
					'&season=' . urlencode($shows['new_episodes']['seasons'][$i]) . 
					'&show_status=' . urlencode($shows['new_episodes']['status'][$i]) . 
					'&show_index=' . $i
				, true),
			);
		}
	}
	else
	{
		$infos['services'] = array(
			'name' => 'No Services',
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'There are no services configured, shows cannot be downloaded!',
				),
			),
			'text' => 'No Services Configured!',
		);
	}
	
	return $infos;
}

function get_tv_download_downloads($request)
{
	$request['showname'] = validate($request, 'showname');
	$request['season'] = validate($request, 'season');
	$request['episode'] = validate($request, 'episode');
	$request['show_status'] = validate($request, 'show_status');
	$request['show_index'] = validate($request, 'show_index');
	
	// fetch nzb files
	$status = television_fetch_nzb($request['showname'], $request['season'], $request['episode']);
	
	$show_title = htmlspecialchars($request['showname']) . ' ' . 
		htmlspecialchars($request['season']) . 'x' . 
		htmlspecialchars($request['episode']);
		
	$infos = array();
	
	// nzb file was found but something went terribly wrong
	if($status === -1)
	{
		$infos['myepisodes_shows_' . $request['show_index']] = array(
			'name' => 'Failed to save ' . $request['showname'],
			'status' => 'fail',
			'description' => array(
				'list' => array(
					'Failed to save the file, but the episode ' . $show_title . ' was found successfully.',
				),
			),
			'text' => 'Failed to save.',
		);
	}
	// nzb was not found
	elseif($status === false)
	{
		$services = setting('all_nzbservices');
		$links = '';
		foreach($services as $i => $config)
		{
			$search = setting('nzb_television_search_' . $i);
			
			$links .= '<a href="' . sprintf($search, urlencode($request['showname']), urlencode($request['season']), urlencode($request['episode'])) . '">Search for ' . $request['showname'] . ' on ' . $config['name'] . '</a><br />';
		}
		$infos['myepisodes_shows_' . $request['show_index']] = array(
			'name' => 'Failed to find ' . $request['showname'],
			'status' => 'warn',
			'description' => array(
				'list' => array(
					'Failed to find the episode for ' . $show_title . '.',
				),
			),
			'text' => 'Failed to find:<br />' . $links,
		);
	}
	// nzb was found and saved to disk
	else
	{
		$infos['myepisodes_shows_' . $request['show_index']] = array(
			'name' => 'Downloaded ' . $request['showname'],
			'status' => '',
			'description' => array(
				'list' => array(
					'The episode for ' . $show_title . ' was successfully downloaded to ' . $status . '.',
				),
			),
			'text' => 'Downloaded ' . htmlspecialchars($request['showname']) . '.',
		);
		
		// save in myepisodes
		television_myepisodes_save_status($request['show_status']);
	}
	
	return $infos;
}
