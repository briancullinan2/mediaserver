<?php


function output_music_download_login($request)
{
	$login_index = validate($request, 'login_index');
	
	$infos = get_download_services_login($login_index, 'admin/tools/music/downloader/login/');

	if($login_index == count(setting('all_download_services'))-1)
	{
		$infos = array_merge($infos, get_download_services_manual(validate($request, 'service_index'), session('download_services'), 'audio', 'admin/tools/music/downloader/search/'));
		
	}
	
	theme('form_fieldrows', $infos);
}


function output_music_download_manual($request)
{
	$service_index = validate($request, 'service_index');
	
	$infos = get_download_services_manual($service_index, session('download_services'), 'audio', 'admin/tools/music/downloader/search/');

	if($service_index == count(setting('all_download_services', 'audio'))-1)
	{
		// parse through results and compare with what we already have
		
	}
	
	theme('form_fieldrows', $infos);
}

