<?php


function bootstrap($mode = 'all')
{
	switch($mode)
	{
		case 'all':
			$includes = array(
				'error.inc',
				'common.inc',
				'settings.inc',
				'module.inc',
				'file.inc',
				'menu.inc',
				'triggers.inc',
				'request.inc',
				'template.inc',
				'session.inc',
				'compatibility.inc',
				'database.inc',
				'users.inc',
				'theme.inc',
				'curl.inc',
			);
			
			// include all required system functionality
			foreach($includes as $i => $include)
			{
				if(!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $include))
					die('Includes are corrupted, please reinstall!');
					
				require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . $include;
			}
			preload_error();
			preload_triggers();
			
			// run setup functions and add to modules
			foreach($includes as $i => $include)
			{
				$config = load_module(dirname(__FILE__) . DIRECTORY_SEPARATOR . $include);
			}

			load_modules(setting_local_root() . 'modules');
			
			bootstrap('mime');
			
			// run some known setup functions
			setup_module();
		
			invoke_all_callback('setup', 'disable_module');
			
			request_validate();
			
			break;
		
		case 'mime':
			setup_mime();
			break;
	}
	
}

/** 
 * parse mime types from a mime.types file, 
 * This functionality sucks less then the PEAR mime type library
 * @ingroup setup
 */
function setup_mime()
{
	// this will load the mime-types from a linux dist mime.types file stored in includes
	// this will organize the types for easy lookup
	if(file_exists(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types'))
	{
		$handle = fopen(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types', 'r');
		$mime_text = fread($handle, filesize(setting_local_root() . 'include' . DIRECTORY_SEPARATOR . 'mime.types'));
		fclose($handle);
		
		$mimes = split("\n", $mime_text);
		
		$ext_to_mime = array();
		foreach($mimes as $index => $mime)
		{
			$mime = preg_replace('/#.*?$/', '', $mime);
			if($mime != '')
			{
				// mime to ext
				$file_types = preg_split('/[\s,]+/', $mime);
				$mime_type = $file_types[0];
				// general type
				$tmp_type = split('/', $mime_type);	
				$type = $tmp_type[0];
				// unset mime part to get all its filetypes
				unset($file_types[0]);
				
				// ext to mime
				foreach($file_types as $index => $ext)
				{
					$ext_to_mime[$ext] = $mime_type;
				}
			}
		}
		
		
		// set global variables
		$GLOBALS['ext_to_mime'] = $ext_to_mime;
	}
}

