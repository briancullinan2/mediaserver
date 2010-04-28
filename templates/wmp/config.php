<?php


function register_wmp()
{
	return array(
		'name' => 'WMP',
		'description' => 'A Windows Media Player style interface built on qooXdoo.',
		'privilage' => 1,
		'path' => __FILE__,
		'files' => array()
	);
}


function output_wmp()
{
	theme('index');
}

function theme_frame()
{
	// ?><script>
	var win = new qx.ui.window.Window("<?php print setting('html_name');?>");
	
	<?php
}