<?php

// allow templates to save settings
// get all template settings from database
// parse out selected template setting
// return settings in manner that the template understands
// templates can submit settings using the config options for the template
//   define the setting name and type in a key array
//   types will be specified in the documentation (aligns with form types), text, int, radio, checkbox)

function register_settings()
{
	return array(
		'name' => 'Settings',
		'description' => 'This allows users to save theme settings.',
		'privilage' => 1,
		'path' => __FILE__
	);
}

function output_settings($request)
{
	
}

?>