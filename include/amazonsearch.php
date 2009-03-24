<?php
require('AmazonProduct.class.php');
require('AmazonSearchEngine.class.php');
require('Snoopy.class.inc');

$dev_key = 'xxxxxxxxxxxx';    // Your Amazon web services developer key
$associate_id = 'none'; // Your (optional) Amazon associates ID

// Grab the term from the query string
$term = isset($_GET['term']) ? $_GET['term'] : 'php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>PHP Amazon Search (Demo)</title>
<link rel="stylesheet" type="text/css" media="screen" href="http://scripts.incutio.com/amazon/styles.css" />
</head>
<body>
<div class="header">
<h1>PHP Amazon Search (Demo)</h1>
</div>
<form action="amazonsearch.php">
<div class="search">
<label for="searchbox">Search:</label> <input id="searchbox" type="text" name="term" value="<?php echo $term; ?>" /> <input type="submit" value="Search!" />
<a href="http://scripts.incutio.com/amazon/">How does this work?</a>
</div>
</form>
<div class="main">
<?php
/* Create a new AmazonSearchEngine instance, with your developer key and associates ID */
$se = new AmazonSearchEngine($dev_key, $associate_id);

if (isset($_GET['do']) && $_GET['do'] == 'related') {
    $se->searchRelated($term);
} else {
    $se->searchTerm($term);
}
/* Call the search method with your search term */

$counter = 0;
/* Cycle through the result set */
foreach ($se->results as $result) {
    $counter++;
    $authors = implode(', ', $result->Authors);
    $saving = $result->getSaving();
    if ($saving) {
        $save = '<div class="Saving">Save $'.$saving."!</div>\n";
    } else {
        $save = '';
    }
    echo <<<EOD
<div class="AmazonResult">
<img class="AmazonImage" src="{$result->ImageUrlMedium}" alt="{$result->ProductName}" />
<h3 class="ProductName">$counter. <a href="{$result->url}">{$result->ProductName}</a></h3>
<div class="authors">By $authors</div>
<div class="ISBN">ISBN: {$result->Asin} - <a href="amazonsearch.php?do=related&term={$result->Asin}">List Related Items</a></div>
<div class="AmazonPrice">Amazon Price: {$result->OurPrice}</div>
<div class="ListPrice">List Price: {$result->ListPrice}</div>
$save
<!-- <div class="UsedPrice">Used Price: {$result->UsedPrice}</div> -->
</div>
EOD;
}
?></div>
</body>
</html>