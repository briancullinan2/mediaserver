<?php

function __tunegazelle_css_header()
{
	?><style type="text/css"><?php
}


function tunegazelle_colors()
{
	?>
	html, body {
		color:#443322;
	}
	
	#heading {
		background:url("images/10-20.png") repeat-x scroll 50% 50% #FFEEBB;
		border-bottom:1px solid #AA9988;
	}
	
	#heading h1 {
		background:url("images/logo-tiny.png") no-repeat scroll 5px top transparent;
		padding-left:30px;
	}
	
	#heading h1 {
		text-shadow:0 1px 1px rgba(255, 255, 255, 0.40), 2px 0 1px rgba(255, 255, 255, 0.40)
	}
	
	#left-menu {
		border-right:1px solid #AA9988;
		background:url("images/10-0-large.png") repeat-x scroll 50% 40px #FFFFFF;
	}
	
	#left-menu li:hover {
		background-color:#DDCCBB;
		border:1px solid #DDCCBB;
	}

	#controls {
		color:#DDCCBB;
		background:url("images/0-20-white.png") repeat-x scroll 50% 50% #332211;
		box-shadow:2px 3px 3px rgba(0, 0, 0, .5)
	}
	
	#search-box {
		border:1px solid #AA9988;
		box-shadow:0px 0px 3px rgba(0, 0, 0, .50) inset;
		background:url("images/15-0-tiny.png") repeat-x scroll 50% 60px transparent;
	}
	
	#search-box:hover {
		background-color:#FFFFFF;
		box-shadow:0px 0px 3px rgba(0, 0, 0, .50);
		background-position:50% top;
		color:#443322;
	}
	
	#search-box:hover #search, #search:hover {
		color:#443322;
	}
	
	#search-box:hover #search-icon, #search-box:hover #go-icon {
		background-position:0px 0px;
	}
	
	#search {
		color:#DDCCBB;
	}
	
	#search-icon {
	    background: url("images/magnify.png") no-repeat scroll 0 -18px transparent;
	}
	
	#view-control {
	    background: url("images/view.png") no-repeat scroll 0 -18px transparent;
	}
	
	#view-control:hover {
		background-position:0px 0px;
		box-shadow:0px 0px 2px rgba(0, 0, 0, .75) inset;
		background-color:#FFFFFF;
		border-right:1px solid #AA9988;
	}
	
	.current-tab {
		color:#443322;
		border-top:1px solid #AA9988;
		border-left:1px solid #AA9988;
		border-right:1px solid #AA9988;
		background:url("images/20-0-small.png") repeat-x scroll 50% top #FFFFFF;
	}
	
	.inactive-tab {
		border-top:1px solid #AA9988;
		border-right:1px solid #AA9988;
		box-shadow:2px 0px 3px rgba(0, 0, 0, .50) inset;
		background:url("images/20-0-small-white.png") repeat-x scroll 50% 60px #443322;
	}
	
	.inactive-tab:hover {
		color:#FFFFFF;
		background-position:50% top;
		box-shadow:none;
	}
	
	.inactive-handler:hover {
		color:#FFFFFF;
	}
	
	#details {
		background:url("images/10-20-large.png") repeat-x scroll 50% 50% #FFEEBB;
		border-top:1px solid #AA9988;
	}
	
	.folders {
		box-shadow:2px 3px 3px rgba(0, 0, 0, .5);
		background-color:#FFFFFF;
		border-right:1px solid #AA9988;
		border-left:1px solid #AA9988;
	}
	
	.folders ul {
		border-right:1px solid #AA9988;
	}
	
	.folders li {
		background:url("images/0-10-small.png") repeat-x scroll 50% bottom #FFFFFF;
	}
	
	.folders li:hover {
		background-image:none;
		box-shadow:0px 0px 5px rgba(0, 0, 0, .5);
	}
	
	.folders .loading-icon {
		background-image:url("images/loading-white.gif");
	}

	.folders li a {
		color:#332211;
	}
	
	.folders li:hover a, .folders li a:hover {
		color:#443322;
	}
	
	#right-menu {
		border-left:1px solid #AA9988;
		background-color:#FFFFFF;
	}
	
	<?
}

function __tunegazelle_css_footer()
{
	?></style><?php
	
}