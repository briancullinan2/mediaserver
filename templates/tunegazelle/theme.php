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
		text-shadow:0 -1px 1px rgba(255, 255, 255, 0.40), -2px 0 1px rgba(255, 255, 255, 0.40)
	}
	
	#left-menu {
		border-right:1px solid #AA9988;
	}
	
	#left-menu li:hover, #left-menu li a:hover {
		background-color:#DDDDCC;
		border:1px solid #DDDDCC;
	}

	<?
}

function __tunegazelle_css_footer()
{
	?></style><?php
	
}