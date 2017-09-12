<?php
/*
 * register_globals and magic_quotes_gpc are being removed in PHP6.
 * I support this because these features are dangerious. 
 * the programmer is repsonsible for their own user input validation.
 * register_globals is a very bad idea that spawed its own breed of attacks.
 */

//Errors spill information to hackers. 
//error_reporting(0);

//Disable register_globals becase it is a security hazard
if(ini_get('register_globals')){
	foreach(get_defined_vars() as $var=>$val){
		//only keep superglobals we need on this whitelist,  _SESSION will take care of its self:
		if(!in_array($var,array('_GET','_POST','_COOKIE','_SERVER', '_REQUEST'))){
			unset($GLOBALS[$var]);
		}
	}
}
//Disable magic_quotes - recursive 
if(ini_get('magic_quotes_gpc')){
   function stripslashes_arrays(&$array){//pass by refernce
      if ( is_array($array) ) {
         $keys = array_keys($array);
         foreach ( $keys as $key ) {
            if ( is_array($array[$key]) ) {
               stripslashes_arrays($array[$key]);
            }
            else {
               $array[$key] = stripslashes($array[$key]);
            }
         }
      }
   }
   //we only use get and post,  _SESSION needs cookie,  which shouldn't exist as of yet. 
   stripslashes_arrays($_GET);
   stripslashes_arrays($_POST);
   stripslashes_arrays($_COOKIE);
   stripslashes_arrays($_REQUEST);
}
//Make all Posts immune to xsrf.
//$_GET is only used for navigation. 
if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=="POST"){
	//The referer can be forged on old, unpatched browsers.
	//The token cannot be forged unless it is guessed. 
	$referer_check = parse_url($_SERVER['HTTP_REFERER']);
	$server_host = parse_url($_SERVER['HTTP_HOST']);
	if(!isset($server_host['host']))
		$server_host['host'] = $server_host['path'];
	if($server_host['host'] != $referer_check['host']){
		die('Session Riding(XSRF) Detected!');	
	}
}else{
	//Paranoia
	unset($_POST);
}

