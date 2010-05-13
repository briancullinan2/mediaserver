<?php

//
// Include the GeSHi library//
include_once 'include/geshi/geshi.php'; 

//// Define some source to highlight, a language to use
// and the path to the language files//
$source = implode("\n", file(__FILE__));

$language = 'php';
 //
// Create a GeSHi object//
 $geshi = new GeSHi($source, $language);
 //
// And echo the result!//
echo $geshi->parse_code();

