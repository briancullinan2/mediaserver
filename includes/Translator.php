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

function utf8_html_decode($matches){
   return html_entity_decode('&#x'.$matches[1].';', ENT_QUOTES, 'UTF-8');
}

function unescapeUTF8EscapeSeq($str) {
   $str=preg_replace_callback("/\\\u([0-9a-f]{4})/i",utf8_html_decode , $str);
	$str=html_entity_decode($str, ENT_QUOTES,"UTF-8");
   $str=trim($str);
   $str=str_replace('<s/>', " %s ", $str);
   $str=str_replace('</s>', " %s ", $str);
   $str=str_replace('<br>',"\\n", $str);
   return $str;
}

function translate($text,$lang_code,$source_lang='en'){
   if(is_array($text)){
      $is_array=true;
      //If the array is larger than 100 items then google may reject it.
      if(sizeof($text)>100){
         $len=sizeof($text);
         for($x=0;$x<$len;$x+=100){
            $slice=array_slice($text,$x,$x+100);
            $tran=translate($slice,$lang_code,$source_lang);
            foreach($tran as $t){
               $ret[]=$t;
            }
         }
         return $ret;
      }
      /*$dex=0;
      foreach($text as $dex=>$t){
         $e=execute("select translated_text from translate_cache where origonal_text=? and origonal_lang=? and translated_lang=?",array($t,$source_lang,$lang_code));
         $r=$e->FetchRow();
         if($r){
            $cache[$dex]=$r[0];
            $text[$dex]='';
         }else{
            execute("insert into translate_cache (origonal_text,origonal_lang,translated_lang) values (?,?.?)",array($t,$source_lang,$lang_code));
         }
      }*/
      $post=implode("&q=",$text);
      $post="q=".$post;
   }else{
      $post="q=".$text;
   }
   $post=str_replace("\\n","<br>",$post);
   $post=str_replace("%s","<s/>",$post);
   //$post=str_replace("\n","||", $post);

   $url="http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair=$source_lang%7C$lang_code&callback=foo&context=bar";
   $http=new http_client();
   $data=$http->post($url,$post);
   $data=explode('translatedText":"',$data);

   if($is_array){
      array_shift($data);
      foreach($data as $d){
         $t=explode('"},',$d);
         $translated[]=unescapeUTF8EscapeSeq($t[0]);
      }
      if(sizeof($translated)!=sizeof($text)){
         die(sizeof($translated)."!=".sizeof($text));
      }
      //Check for translation errors,  do not leave blank on error.
      for($x=sizeof($translated)-1;$x>=0;$x--){
         $translated[$x]=trim($translated[$x]);
         if(!$translated[$x]){
            if($text[$x]){
              $translated[$x]=$text[$x];
            }else{
              $translated[$x]=$cache[$x];
            }
         }
      }
   }else{
      $data=explode('"},',$data[1]);
      $translated=unescapeUTF8EscapeSeq($data[0]);
      if(!$translated){
         $translated=$text;
      }
   }
   return $translated;
}

function translate_cache($text,$lang_code,$source_lang='en'){
   $e=execute("select translated_text from translations where origonal_text=? and origonal_lang=? and translated_lang=?",array($text,$source_lang,$lang_code));
   $r=$e->FetchRow();
   if($r){
     $ret=$r[0];
   }else{
      $ret=translate($text,$lang_code,$source_lang);
      execute("insert into translations (origonal_text,translated_text,origonal_lang,translated_lang) values (?,?,?,?)",array($text,$ret,$source_lang,$lang_code));
   }
   return $ret;
}


