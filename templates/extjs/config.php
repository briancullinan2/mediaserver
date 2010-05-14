<?php

function register_extjs()
{
	return array(
		'name' => 'Ext JS',
		'description' => 'Ext JS javascript windows style template.',
		'privilage' => 1,
		'path' => __FILE__,
	);
}

function output_extjs()
{
	?><script language="javascript" type="application/javascript"><?php
}

function theme_extjs_index()
{
	?>
	
	
	<?php
}

function theme_extjs_footer()
{
	?></script><?php
}