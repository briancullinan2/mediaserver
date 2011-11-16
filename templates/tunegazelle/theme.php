<?php

function __tunegazelle_css_header()
{
	?><style type="text/css"><?php
}


function theme_tunegazelle_colors()
{
	header("Cache-Control: cache");  
	header("Pragma: public");
	header('Content-Type: text/css');
	
	list($main_hue, $main_sat, $main_lum) = RGBToHSL(setting('tunegazelle_main_color'));
	list($secondary_hue, $secondary_sat, $secondary_lum) = RGBToHSL(setting('tunegazelle_secondary_color'));
	list($menu_hue, $menu_sat, $menu_lum) = RGBToHSL(setting('tunegazelle_menu_color'));
	list($text_hue, $text_sat, $text_lum) = RGBToHSL(setting('tunegazelle_text_color'));
	
	?>
	html, body {
		color:<?php print setting('tunegazelle_text_color'); ?>;
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	.field_fieldset fieldset {
		border:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.ui-slider .ui-slider-handle,
	#content a,
	#content input[type='submit'],
	#content input[type='reset'],
	#content input[type='button'] {
		border:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		background:url("<?php print $menu_lum > 50 ? 'images/15-0-tiny.png' : 'images/15-0-tiny-white.png'; ?>") repeat-x scroll 50% 1000px <?php print setting('tunegazelle_main_color'); ?>;
		color:<?php print setting('tunegazelle_text_color'); ?>;
		box-shadow:0px 0px 3px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75) inset;
	}
	
	#content input,
	#content select {
		border:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		box-shadow:0px 0px 3px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75) inset;
		background:url("<?php print $menu_lum > 50 ? 'images/15-0-tiny.png' : 'images/15-0-tiny-white.png'; ?>") repeat-x scroll 50% 1000px <?php print setting('tunegazelle_secondary_color'); ?>;
		color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
	}
	
	select option:hover, select option:active {
		background-color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
	}
	
	.ui-slider .ui-slider-handle:not(:disabled):hover, .ui-slider .ui-slider-handle:not(:disabled):focus,
	#content a:hover, #content a:focus,
	#content input:not(:disabled):hover, #content input:not(:disabled):focus,
	#content select:not(:disabled):hover, #content select:not(:disabled):focus {
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
		box-shadow:0px 0px 3px rgba(<?php print $menu_lum < 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75);
		background-position:50% top;
		color:<?php print setting('tunegazelle_text_color'); ?>;
	}

	.ui-slider, .swatch {
		border: 1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.field_set .fieldname {
		border-color:<?php print setting('tunegazelle_border_color'); ?>;	
	}
	
	.field_row > * {
		border-color:<?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	/*
	.ui-slider .ui-slider-handle:hover,
	#content legend a:hover,
	#content input[type='submit']:hover,
	#content input[type='reset']:hover,
	#content input[type='button']:hover {
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
		background-position:50% <?php print $main_lum < 50 ? 'bottom' : 'top'; ?>;
		color:<?php print setting('tunegazelle_text_color'); ?>;
		text-shadow:none;
	}
	*/
	
	.field_row {
		<?php if($menu_lum > 50) { ?>
		background:url("images/0-20.png") repeat-x scroll 50% bottom transparent;
		<?php } else { ?>
		background:url("images/0-20-white.png") repeat-x scroll 50% bottom transparent;
		<?php } ?>
	}
	
	#heading {
		background:url("<?php print $main_lum < 50 ? 'images/0-20-white.png' : 'images/0-20.png'; ?>") repeat-x scroll 50% 50% <?php print setting('tunegazelle_main_color'); ?>;
		border-bottom:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		text-shadow:0 1px 1px rgba(<?php print $text_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, 0.60), 2px 0 1px rgba(<?php print $text_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, 0.60);
	}
	#row_tunegazelle_main_color .swatch {
		background:url("<?php print $text_lum > 50 ? 'images/0-20-large-white.png' : 'images/0-20-large.png'; ?>") repeat-x scroll 50% 50% <?php print setting('tunegazelle_main_color'); ?>;
	}
	
	#heading h1 {
		background:url("images/logo-tiny.png") no-repeat scroll 5px top transparent;
		padding-left:30px;
	}
	
	#heading a {
		color:<?php print setting('tunegazelle_text_color'); ?>;
	}

	#heading a:hover {
		color:<?php print setting('tunegazelle_text_color'); ?>;
		text-shadow:0px 1px 2px rgba(<?php print $text_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, 0.60), 3px 1px 2px rgba(<?php print $text_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, 0.60);
	}
	
	#left-menu {
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		background:url("<?php print $menu_lum > 50 ? 'images/10-0-large.png' : 'images/10-0-large-white.png'; ?>") repeat-x scroll 50% 40px <?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	#left-menu li:hover {
		background-color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
		border:1px solid <?php print setting('tunegazelle_text_secondary_color'); ?>;
	}

	#controls, 
	#row_setting_group_permissions > .field_set > .field_set > .field {
		color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
		background:url("<?php print $secondary_lum > 50 ? 'images/0-20.png' : 'images/0-20-white.png'; ?>") repeat-x scroll 50% 50% <?php print setting('tunegazelle_secondary_color'); ?>;
	}
	
	#search-box {
		border:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		box-shadow:0px 0px 3px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75) inset;
		background:url("<?php print $menu_lum > 50 ? 'images/15-0-tiny.png' : 'images/15-0-tiny-white.png'; ?>") repeat-x scroll 50% 1000px <?php print setting('tunegazelle_secondary_color'); ?>;
	}
	
	#search-box:hover {
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
		box-shadow:0px 0px 3px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75);
		background-position:50% top;
		color:<?php print setting('tunegazelle_text_color'); ?>;
	}
	
	#search-box:hover #search, #search:hover {
		color:<?php print setting('tunegazelle_text_color'); ?>;
	}
	
	#search-box:hover #search-icon, #search-box:hover #go-icon {
		background-position:<?php print $menu_lum > 50 ? '0px 0px' : '0px -18px'; ?>;
	}
	
	#search {
		color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
	}
	
	#search-icon {
	    background: url("images/magnify.png") no-repeat scroll <?php print $secondary_lum > 50 ? '0px 0px' : '0px -18px'; ?> transparent;
	}
	
	#view-control {
	    background: url("images/view.png") no-repeat scroll <?php print $secondary_lum > 50 ? '2px 2px' : '2px -16px'; ?> transparent;
	}
	
	#view-control:hover {
		background-position:<?php print $menu_lum > 50 ? '2px 2px' : '2px -16px'; ?>;
		box-shadow:0px 0px 2px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .75) inset;
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.current-tab {
		color:<?php print setting('tunegazelle_secondary_color'); ?>;
		border-top:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		border-left:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		background:url("<?php print $menu_lum > 50 ? 'images/20-0-small.png' : 'images/20-0-small-white.png'; ?>") repeat-x scroll 50% top <?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	.inactive-tab {
		border-top:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		box-shadow:2px 0px 3px rgba(<?php print $secondary_lum > 50 ? '255, 255, 255' : '0, 0, 0'; ?>, .50) inset;
		background:url("<?php print $secondary_lum < 50 ? 'images/20-0-small-white.png' : 'images/20-0-small.png'; ?>") repeat-x scroll 50% 1000px <?php print setting('tunegazelle_secondary_color'); ?>;
	}
	
	.inactive-tab:hover {
		color:<?php print setting('tunegazelle_menu_color'); ?>;
		background-position:50% top;
		box-shadow:none;
	}
	
	#handlers a {
		color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
	}
	
	#handlers a:hover {
		color:<?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	.field_row > .fielddesc,
	#details {
		background:url("images/0-20-large.png") repeat-x scroll 50% 50% <?php print setting('tunegazelle_main_color'); ?>;
		border-top:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	#login > div {
		border:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.folders {
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		border-left:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.folders ul {
		border-right:1px solid <?php print setting('tunegazelle_border_color'); ?>;
	}
	
	.folders li {
		<?php if($menu_lum > 50) { ?>
		background:url("images/0-10-small.png") repeat-x scroll 50% bottom <?php print setting('tunegazelle_menu_color'); ?>;
		<?php } else { ?>
		background:url("images/10-0-small-white.png") repeat-x scroll 50% top <?php print setting('tunegazelle_menu_color'); ?>;
		<?php } ?>
	}
	
	.folders li:hover {
		background-image:none;
		box-shadow:0px 0px 5px rgba(<?php print $menu_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, .5);
	}
	
	.folders .loading-icon {
		background-image:url("<?php print $menu_lum > 50 ? 'images/loading.gif' : 'images/loading-white.gif'; ?>");
	}

	.folders li a {
		color:<?php print setting('tunegazelle_text_color'); ?>;
	}
	
	.folders li:hover a, .folders li a:hover {
		color:<?php print setting('tunegazelle_secondary_color'); ?>;
	}
	
	#right-menu {
		border-left:1px solid <?php print setting('tunegazelle_border_color'); ?>;
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
	}
	
	.file a {
		color:<?php print setting('tunegazelle_text_color'); ?>;
		text-decoration:none;
	}
	
	.file.even {
		background-color:<?php print setting('tunegazelle_text_secondary_color'); ?>;
	}
	
	.file:hover {
		background-color:<?php print setting('tunegazelle_main_color'); ?>;
	}
	
	.users #content {
		background-color:rgba(<?php print $menu_lum > 50 ? '0, 0, 0' : '255, 255, 255'; ?>, .20);
	}
	
	#login > div {
		background-color:<?php print setting('tunegazelle_menu_color'); ?>;
	}

	
	<?
}

function __tunegazelle_css_footer()
{
	?></style><?php
	
}