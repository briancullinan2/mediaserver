<?php

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if snoopy library is installed in the include directory
 */
function dependency_curl_installed($settings)
{
	return function_exists('curl_init');
}

/**
 * Fetch remote pages using curl
 * @param url the url of the page to fetch
 * @param post if set perform a post request
 * @param cookies send cookies along with the request, also stores the cookies returned
 * @return an associative array consisting of content, and headers
 */
function fetch($url, $post = array(), $headers = array(), $cookies = array())
{
	if(function_exists('curl_init'))
	{
		$ch = curl_init($url);
		
		// setup basics
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// setup timeout
		if(isset($headers['timeout']))
		{
			curl_setopt($ch, CURLOPT_TIMEOUT, $headers['timeout']);
			unset($headers['timeout']);
		}
		else
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_BUFFERSIZE, setting('buffer_size'));
		
		// setup user agent
		if(isset($headers['agent']))
		{
			curl_setopt($ch, CURLOPT_USERAGENT, $headers['agent']);
			unset($headers['agent']);
		}
		else
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1');
			
		// setup referer
		if(isset($headers['referer']))
		{
			curl_setopt($ch, CURLOPT_REFERER, $headers['referer']);
			unset($headers['referer']);
		}
		
		// curl ssl
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		// setup headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		// setup post
		if(count($post) > 0)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);    
		}
		
		$cookie = '';
		if(is_array($cookies))
		{
			foreach ($cookies as $key => $value)
			{
				$cookie .= $key . '=' . $value . '; ';
			}
		}
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		// use a cookie file just because of follow_location
		$cookie_file = tempnam('dummy', 'cookie_');
		if($cookie_file !== false)
		{
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		}
	
		// execute
		raise_error('Fetching \'' . $url . '\'.', E_DEBUG);
		$content = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 	
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);	
		$headers_raw = explode("\n", substr($content, 0, $header_size));
		$content = substr($content, $header_size);
		curl_close($ch);
		
		// process cookies
		list($headers, $new_cookies) = parse_headers($headers_raw);
		$cookies = array_merge($cookies, $new_cookies);
		
		// delete cookie jar because they are saved and return in an array instead
		if($cookie_file !== false) unlink($cookie_file);

		if(dependency('simplexml_installed') && isset($headers['content-type']) && substr($headers['content-type'], 0, 8) == 'text/xml')
		{
			try {
				$xml = new SimpleXMLElement($content);
			} catch (Exception $e) {
				exception_wrapper($e);
			}
		}
		
		if(isset($xml))
			return array('headers' => $headers, 'content' => $content, 'cookies' => $cookies,
				'status' => $status, 'xml' => $xml
			);
		else
			return array('headers' => $headers, 'content' => $content, 'cookies' => $cookies, 'status' => $status);
	}
	else
	{
		raise_error('cUrl not installed!', E_DEBUG);
		
		return array('headers' => array(), 'content' => '', 'cookies' => array(), 'status' => 0);
	}
}

function parse_headers($headers_raw)
{
	$headers = array();
	$cookies = array();
	
	foreach($headers_raw as $i => $header)
	{
		// parse header
		if(strpos($header, ':') !== false)
		{
			$headers[strtolower(substr($header, 0, strpos($header, ':')))] = trim(substr($header, strpos($header, ':') + 1));
		}
		
		// parse cookie
		if(!strncmp($header, "Set-Cookie:", 11))
		{
			$cookiestr = trim(substr($header, 11, -1));
			$cookie = explode(';', $cookiestr);
			$cookie = explode('=', $cookie[0]);
			$cookiename = trim(array_shift($cookie)); 
			$cookies[$cookiename] = trim(implode('=', $cookie));
		}
	}
	
	return array(
		$headers,
		$cookies,
		'headers' => $headers,
		'cookies' => $cookies,
	);
}

/**
 * Helper function takes a url and a fragment and gets the full valid url
 */
function get_full_url($url, $fragment)
{
	if($address = generic_validate_hostname(array('address' => $fragment), 'address'))
		// already is valid
		return $fragment;
	else
	{
		// check if url is valid
		$address = generic_validate_hostname(array('address' => $url), 'address');
		
		// get path to prepend to fragment
		if($path = generic_validate_urlpath(array('path' => $url), 'path') && 
			substr($fragment, 0, 1) != '/'
		)
		{
			$path = dirname($path);
			if(substr($path, 0, 1) == '/') $path = substr($path, 1);
			// make sure there is a slash on the end
			if(substr($address, -1) != '/') $address .= '/';
			
			return $address . $path . (($path != '')?'/':'') . $fragment;
		}
		else
		{
			return $address . $fragment;
		}
		
	}
}

function get_login_form($content, $userfield = 'username')
{
	// get forms
	if(preg_match_all('/<form[^>]*?action="([^"]*?)"[^>]*?>([\s\S]*?)<\/form>/i', $content, $forms) > 0)
	{
		// match input fields
		foreach($forms[0] as $i => $form)
		{
			// extract form elements
			$result = preg_match_all('/<input[^>]*>/i', $form, $matches);
			if($result > 0)
			{
				$form = array();
				foreach($matches[0] as $j => $field)
				{
					$count = preg_match('/name=(["\']?)([^\1]*?)\1/i', $field, $name);
					$count = preg_match('/value=(["\']?)([^\1]*?)\1/i', $field, $value);
					$count = preg_match('/type=(["\']?)([^\1]*?)\1/i', $field, $type);

					if(isset($type[2]) && $type[2] == 'password')
						$type[2] = 'text';
					if(!isset($value[2]))
						$value[2] = '';
					
					if(isset($name[2]))
					{
						$form[$name[2]] = array(
							'type' => $type[2],
							'value' => $value[2],
							'name' => ($type[2]!='hidden')?$name[2]:'',
						);
					}
				}
				if(isset($form[$userfield]))
					return array(
						escape_urlquery(htmlspecialchars_decode($forms[1][$i])),
						$form,
						$forms[0][$i]
					);
			}
		}
	}
}

function get_filename($headers)
{
	if(isset($headers['content-disposition']) && 
		preg_match('/filename="([^"]*)"/i', $headers['content-disposition'], $matches) > 0
	)
		$filename = $matches[1];
}

function get_xml_list($xpath)
{
	$result = array();
	while(list( , $node) = each($xpath)) {
		$result[] = (string)$node;
	}
	return $result;
}

function get_xml_result($xpath)
{
	if(count($xpath) > 0)
		return (string)array_shift($xpath);
	else
		return;
}

function escape_urlquery($request)
{
	if(strpos($request, '?') !== false)
	{
		$host = substr($request, 0, strpos($request, '?') + 1);
		
		$new_query = '';
		
		// split up the query string by amersands
		$arr = explode('&', substr($request, strpos($request, '?') + 1));
		if(count($arr) == 1 && $arr[0] == '')
			$arr = array();
		
		// loop through all the query string and generate our new request array
		foreach($arr as $i => $value)
		{
			// split each part of the query string into name value pairs
			$x = explode('=', $value, 2);
			
			// set each part of the query string in our new request array
			$new_query .= (($new_query != '')?'&':'') . $x[0] . '=' . urlencode(isset($x[1])?$x[1]:'');
		}
		
		return $host . $new_query;
	}
	else
		return $request;
}