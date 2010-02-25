<?php

function register_request()
{
	return array(
		'name' => 'request',
		'description' => 'Redirect unknown file and folder requests to recognized protocols and other plugins.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

function output_request($request)
{
	
	// check for ampache compitibility
	if(strpos($_SERVER['REQUEST_URI'], '/server/xml.server.php?') !== false)
	{
		// yes they are trying to access ampache
		// send request to ampache plugin
		output_ampache($request);
		
		// exit because the request was handled
		exit;
	}

	// if it isn't handled and exited by now then there is no hope
	header("HTTP/1.0 404 Not Found");
	die('Page not found');
}

?>