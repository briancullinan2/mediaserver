<?include "inc_security.php";?>
<?include "inc_config.php";?>
<?php $page="home"; include("tpl_header.php"); ?>
<div id="contentright"><h2>New Torrent</h2><p>
Only .torrent files are allowed.
</p>
      <form method="post" action="dyn_process.php" enctype="multipart/form-data">
      <input type=file name=file size="5"><br>
	  <p><input name="upload" type="submit" value="Upload"/></p>
      </form>
</div>
<div id="contentleft">
<div id="message" style="">
<? if(isset($_GET['endresult'])) echo $_GET['endresult'] . "&nbsp;<a href=\"index.php\" onclick=\"javascript:var x=document.getElementById('message').innerHTML='';return false;\"><img src=\"images/delete.png\"/></a>";?>
</div>

<h1>Running Torrents</h1>
<div id="runningtable">
<? include "dyn_progress.php"; ?>
</div>  		

<br />

<h1>Stopped Torrents</h1>
<div id="stoppedtable">
<? include "dyn_stopped.php"; ?>
</div>  

</div>



<?php include("tpl_footer.php"); ?>
