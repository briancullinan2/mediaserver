<?php /* Smarty version 2.6.19, created on 2008-06-14 13:58:18
         compiled from /var/www/mediaserver/templates/extjs/index.html */ ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title><?php echo @SITE_NAME; ?>
</title>
	<?php echo '
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
	'; ?>

	<script type="text/javascript">
		template_path = '/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
';
	</script>
</head>
<body scroll="no" onLoad="loaded=true">
	<div id="loading-mask" class="loading-mask" style=""></div>
	<div id="loading" class="loading">
		<div class="loading-indicator">Media Server<br /><span id="loading-msg" class="loading-msg">Loading styles and images...</span></div>
	</div>
    <!-- include everything after the loading indicator -->
    <link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
extjs/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
css/Desktop.css" />
    <link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
css/Portal.css" />
    <link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
css/Global.css" />
	<link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
css/examples.css" />
	<link rel="stylesheet" type="text/css" href="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
css/lib.css" />

    <!-- GC -->
 	<!-- LIBS -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Core API...';</script>
 	<script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
extjs/adapter/ext/ext-base.js"></script>

 	<!-- ENDLIBS -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading UI Components...';</script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
extjs/ext-all-debug.js"></script>
 	<script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Library.js"></script>
 	<script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/FolderView.js"></script>
 	<script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Address.js"></script>

    <!-- DESKTOP -->
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Initializing Desktop...';</script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/StartMenu.js"></script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/TaskBar.js"></script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Desktop.js"></script>
	
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Applications...';</script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/App.js"></script>
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Applications... (Module)';</script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Module.js"></script>
	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Applications... (Portal)';</script>
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Portal.js"></script>

	<script type="text/javascript">document.getElementById('loading-msg').innerHTML = 'Loading Personal Settings...';</script>
	<div id="x-desktop">
		<dl id="x-shortcuts">
			<dt id="portal-win-shortcut">
				<a href="#"><span> </span>
				<div>Portal</div></a>
			</dt>
		</dl>
	</div>
	
	<div id="ux-taskbar">
		<div id="ux-taskbar-start"></div>
		<div id="ux-taskbuttons-panel"></div>
		<div class="x-clear"></div>
	</div>
	
    <script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/Config.js"></script>
    <!--<script type="text/javascript" src="/<?php echo @SITE_HTMLROOT; ?>
<?php echo @SITE_TEMPLATE; ?>
js/examples.js"></script>-->
	
	<?php echo '
	<script type="text/javascript">
	setTimeout(function(){
		Ext.get(\'loading\').remove();
		Ext.get(\'loading-mask\').fadeOut({remove:true});
		if(window.console && window.console.firebug){
			setTimeout(function(){
				
				var error = Ext.Msg.show({
					title: \'Warning\',
					msg: \'Firebug is known to cause performance issues with Ext JS.\',
					buttons: Ext.MessageBox.OK,
					icon: \'ext-mb-warning\',
					shadow: false
				}).getDialog();
				
			}, 300);
		}
	}, 300);
	
	Ext.BLANK_IMAGE_URL = template_path  + \'images/s.gif\';
	
	</script>
	'; ?>

	
</body>
</html>