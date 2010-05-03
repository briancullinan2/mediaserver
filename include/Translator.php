<?php

class http_client{
	function init($url){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);		
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.18) Gecko/2010021501 Ubuntu/8.04 (hardy) Firefox/3.0.18');
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		return $ch;
	}
	function post($url,$post_data){
		$curl=$this->init($url);
		curl_setopt($curl,CURLOPT_POST,1);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$post_data);
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;
	}
	function get($url){
		$curl=init($url);
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;	
	}
}

function unescapeUTF8EscapeSeq($str) {
	return preg_replace_callback("/\\\u([0-9a-f]{4})/i", create_function('$matches', 'return html_entity_decode(\'&#x\'.$matches[1].\';\', ENT_QUOTES, \'UTF-8\');'), $str);
}

function translate($text,$lang_code,$source_lang='en'){
	if(is_array($text)){
		$is_array=true;
		$text=implode("|",$text);
	}
	$post['q']=$text;
	$url="http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair=$source_lang%7C".$lang_code."&callback=foo&context=bar";
	$http=new http_client();
	$data=$http->post($url,$post);
	$data=explode('translatedText":"',$data);
	$data=explode('"},',$data[1]);
	$translated=unescapeUTF8EscapeSeq($data[0]);
	if($is_array){
		$translated=explode("|",$translated);
	}
	return $translated;
}
?>