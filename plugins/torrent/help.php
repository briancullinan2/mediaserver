<? include "inc_security.php"; ?>
<? include("inc_config.php"); ?>
<?php $page="help"; include("tpl_header.php"); ?>
<h1>Torrential Help</h1>
<p>
<ul>
<li>For this version of torrential, you need to have enhanced-ctorrent installed. On the Unslung Linux you can install it with: <code>ipkg install enhanced-ctorrent</code></li>
<li>enhanced-ctorrent is using port 2706 per default and goes down if it's already taken. That means, if you want to run four torrents concurrently, you have to forward ports 2703-2706 on your router to the machine you are running ctorrent on. If you change the port in the configuration, the behaviour is similar and ctorrent will start at your port and go down until it finds a free port.</li>
<li>If you are running torrential and ctorrent on the NSLU2 or other embedded system, don't forget it's limited resources and don't start too many torrent sessions as the results are unpredictable.</li>
<li>Please remember that the interface updates every x seconds, if you execute an action, it may take few seconds until the new status of the torrent is updated</li>
<li><b>s/l/p</b> means: Number of <b>s</b>eeders/<b>l</b>eechers you are connected to, and there are in total XX <b>p</b>eers reported by the tracker</li>
<li>if you have problems uploading bigger .torrent files with appWeb, have a look at <code>/opt/var/appWeb/appWeb.conf</code> and especially the property <code>LimitRequestBody</code>.</li>
</p>
<?php include("tpl_footer.php"); ?>
