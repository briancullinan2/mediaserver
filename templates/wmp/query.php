<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo htmlspecialchars(HTML_NAME) . ' : Media Player'; ?></title>
    <style type="text/css">
    .loading-mask{
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        z-index:20000;
        background-color:white;
    }
    .loading{
        position:absolute;
        left:45%;
        top:40%;
        padding:2px;
        z-index:20001;
        height:auto;
    }
    .loading a {
        color:#225588;
    }
    .loading .loading-indicator{
        background:white;
        color:#444;
        font:bold 13px tahoma,arial,helvetica;
        padding:10px;
        margin:0;
        height:auto;
    }
    .loading-msg {
        font: normal 10px arial,tahoma,sans-serif;
    }
    </style>
	<script type="text/javascript">
		site_path = '/<?php echo HTML_ROOT; ?>';
		template_path = '/<?php echo HTML_ROOT . HTML_TEMPLATE; ?>';
		plugins_path = '/<?php echo HTML_ROOT . HTML_PLUGINS; ?>';
		dir_sep = "\<?php echo DIRECTORY_SEPARATOR; ?>";
		
	</script>
</head>

<?php define('HTML_BASE', str_replace(DIRECTORY_SEPARATOR, '/', LOCAL_BASE)); ?>
<body onLoad="loaded=true">
	<div id="loading-mask" class="loading-mask" style=""></div>
	<div id="loading" class="loading">
		<div class="loading-indicator">Media Server<br /><span id="loading-msg" class="loading-msg">Loading styles and images...</span></div>
	</div>
    <!-- include everything after the loading indicator -->
    <link rel="stylesheet" type="text/css" href="/<?php echo HTML_ROOT . HTML_BASE; ?>extjs/resources/css/ext-all.css" />

    <!-- GC -->
 	<!-- LIBS -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Core API...';</script>
 	<script type="text/javascript" src="/<?php echo HTML_ROOT . HTML_BASE; ?>extjs/adapter/ext/ext-base.js"></script>

 	<!-- ENDLIBS -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading UI Components... (Core Components)';</script>
    <script type="text/javascript" src="/<?php echo HTML_ROOT . HTML_BASE; ?>extjs/ext-core-debug.js"></script>
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading UI Components... (Library Mods)';</script>
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading UI Components... (Media Components)';</script>

    <!-- PLAYER -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Initializing Player...';</script>
	
    <!-- ACCESSING -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Accessing Library...';</script>
	
</body>
</html>