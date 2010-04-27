<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_upload()
{
	return array(
		'name' => 'Upload',
		'description' => 'Handle the uploading of files.',
		'privilage' => 1,
		'path' => __FILE__
	);	
}

?>