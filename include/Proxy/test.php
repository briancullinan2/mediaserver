<? 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/');
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
//curl_setopt($ch, CURLOPT_PROXY, 'fakeproxy.com:1080');
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'user:password');
$data = curl_exec($ch);
curl_close($ch);
print $data;
?>