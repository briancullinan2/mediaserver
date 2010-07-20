<?php

/**
 * Implementation of register
 * @ingroup register
 */
function register_admin_tools_debug()
{
	$tools = array(
		'name' => 'Debug Tools',
		'description' => 'Tools for displaying debug information for modules and the site as a whole.',
		'privilage' => 10,
		'path' => __FILE__,
		'template' => false,
		'subtools' => array(
			array(
				'name' => 'Remote Proceedure Call',
				'description' => 'Make function calls remotely for running scripts and managing database entries.',
				'privilage' => 10,
				'path' => __FILE__
			),
			array(
				'name' => 'Script Exporter',
				'description' => 'Compile and export PHP scripts with all the necessary template files included.',
				'privilage' => 10,
				'path' => __FILE__
			),
		)
	);
	
	return $tools;
}

/**
 * Implementation of validate
 * @ingroup validate
 */
function validate_compile_module($request)
{
	if(isset($request['compile_module']['download']) && 
		isset($request['compile_module']['module']) &&
		isset($GLOBALS['modules'][$request['compile_module']['module']])
	)
		return $request['compile_module']['module'];
}

/**
 * Implementation of output
 * @ingroup output
 */
function output_admin_tools_debug($request)
{
	$request['subtool'] = validate($request, 'subtool');
	$infos = array();
	
	if(isset($request['compile_module']))
	{
		$result = debug_compile_module($request['compile_module']);
		
		if($result)
			return;
	}
	
	if(isset($request['subtool']) && $request['subtool'] == 1)
	{
		$modules = array();
		foreach($GLOBALS['modules'] as $module => $config)
		{
			$modules[$module] = $config['name'];
		}
		
		$infos['script_export'] = array(
			'name' => 'Export a Tool',
			'status' => '',
			'description' => array(
				'list' => array(
					'Select a script to export and download.',
				),
			),
			'type' => 'set',
			'options' => array(
				'compile_module[module]' =>  array(
					'type' => 'select',
					'options' => $modules,
					'value' => 'admin_tools_television',
				),
				'compile_module[download]' =>  array(
					'type' => 'submit',
					'value' => 'Download',
				),
			),
		);
	}
	
	if(isset($request['subtool'])) register_output_vars('subtool', $request['subtool']);
	register_output_vars('infos', $infos);
	
	theme('tools_subtools');
}

/**
 * Helper function for compiling a module in to a single runnable script
 */
function debug_compile_module($module)
{
	$functions = array(
		$module => 'all',
		'core' => array('validate', 'set_output_vars'),
	);
	
	if(isset($GLOBALS['modules'][$module]))
	{
		$code = '';
		foreach($functions as $module => $extract)
		{
			$fh = fopen($GLOBALS['modules'][$module]['path'], 'r');
			
			if($fh == false)
				return false;
			
			$tmp_code = fread($fh, filesize($GLOBALS['modules'][$module]['path']));
			
			if($extract == 'all')
				$code .= $tmp_code;
			else
			{
preg_match_all('~ function\svalidate\([\s\S]*?{ ( (?>[^{}]+) | (?R) )* } ~x', $tmp_code, $blocks);
print_r($blocks);
exit;

				foreach($extract as $i => $function)
				{
					if(preg_match('/function ' . $function . '\(.*?({ ( (?>[^{}]+) | (?1) )* })/', $tmp_code, $matches) == 0)
						raise_error('Function ' . $function . ' could not be extracted!', E_DEBUG);
					else
						$code .= $matches[0];					
				}
			}
		
			fclose($fh);
		}
		
		$show = '
// if this script is being called directly then run the output function
if(realpath($_SERVER[\'SCRIPT_FILENAME\']) == __FILE__)
{
	output_' . $module . '($_REQUEST);
}';
			
		highlight_string($code . $show);
		
		return true;
	}
	
	return false;
}

