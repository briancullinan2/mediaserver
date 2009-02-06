<?include "inc_config.php";?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="stylesheet" type="text/css" href="main.css" title="default" />
<?php if($page=="home") echo "<script src=\"script.js\" type=\"text/javascript\"></script>\n"
."<script type=\"text/javascript\">this.timerID = setInterval(doInterval, ". $refresh*1000 .");</script>\n";?>
<title>torrential.home</title>

</head>
<body>

<div id="top">
<div id="title">torrential.<?php echo $page?></div>
<div id="navigation">
<?php if($page!="login") { ?>
<ul>
<li><a href="login.php?logout=please">Logout</a></li>
<li><a <?php if($page=="help") echo "class=\"active\""; ?> href="help.php">Help</a></li>
<li><a <?php if($page=="config") echo "class=\"active\""; ?> href="config.php">Config</a></li>
<li><a <?php if($page=="home") echo "class=\"active\""; ?> href="index.php">Home</a></li>
</ul>
<?php } ?>
</div>

<div id="contentcontainer">
<div id="contentshadow">
<div id="content">
