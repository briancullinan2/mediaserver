<?php

$result = fetch('https://www.waffles.fm/w/index.php?title=Special%3AUserLogin&returnto=Main_Page');

$cookies = $result['cookies'];

$result = fetch('https://www.waffles.fm/w/index.php?title=Special%3AUserLogin&action=submitlogin&type=login&returnto=Main_Page', array(
    'ipLogout' => 'yes',
    'wpName' => 'wisemaster',
    'wpPassword' => 'daddy123',
    'wpLoginattempt' => 'Log in',
), array('referer' => 'https://www.waffles.fm/w/index.php?title=Special%3AUserLogin&returnto=Main_Page'), $cookies);
print_r($result);

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
		foreach ($cookies as $key => $value)
		{
			$cookie .= $key . '=' . $value . '; ';
		}
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		
		// execute
		$content = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 	
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);	
		$headers_raw = split("\n", substr($content, 0, $header_size));
		$content = substr($content, $header_size);
		curl_close($ch);
		
		// process cookies
		$headers = array();
		foreach($headers_raw as $i => $header)
		{
			// parse header
			if(strpos($header, ':') !== false)
			{
				$headers[substr($header, 0, strpos($header, ':'))] = trim(substr($header, strpos($header, ':') + 1));
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
		
		return array('headers' => $headers, 'content' => $content, 'cookies' => $cookies, 'status' => $status);
	}
	else
	{
		PEAR::raiseError('cUrl not installed!', E_DEBUG);
		
		return array('headers' => array(), 'content' => '', 'cookies' => array(), 'status' => 0);
	}
}
