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
		'path' => __FILE__,
		'settings' => array('tmp_dir'),
		'depends on' => array('writable_tmp_dir')
	);	
}

/**
 * Implementation of dependency
 * @ingroup dependency
 * @return true or false if the specified tmp_dir is writable
 */
function dependency_writable_tmp_dir($settings)
{
	$settings['tmp_dir'] = setting_tmp_dir($settings);
	return is_writable($settings['tmp_dir']);
}

/**
 * Implementation of setting
 * @ingroup setting
 * @return the temp directory reported by the OS by default
 */
function setting_tmp_dir($settings)
{
	if(isset($settings['tmp_dir']) && is_dir($settings['tmp_dir']))
		return $settings['tmp_dir'];
	else
	{
		$tmpfile = tempnam("dummy","");
		unlink($tmpfile);
		return dirname($tmpfile) . DIRECTORY_SEPARATOR;
	}
}

/**
 * Implementation of dependencies
 */
function status_upload($settings)
{
	$settings['tmp_dir'] = setting_tmp_dir($settings);

	$status = array();

	if(dependency('writable_tmp_dir'))
	{
		$status['tmp_dir'] = array(
			'name' => lang('tmp dir title', 'Temporary Files'),
			'status' => '',
			'value' => 'Temporary directory accessible',
			'description' => array(
				'list' => array(
					lang('tmp dir description', 'This directory will be used for uploaded files and storing temporary files like converted files and images.'),
				),
			),
		);
	}
	else
	{
		$status['tmp_dir'] = array(
			'name' => lang('tmp dir title', 'Temporary Files'),
			'status' => 'fail',
			'value' => 'Configure the temporary directory!',
			'description' => array(
				'list' => array(
					lang('tmp dir fail description 1', 'The system has detected that this directory does not exist or is not writable.'),
					lang('tmp dir fail description 2', 'Please correct this error by entering a directory path that exists and is writable by the web server.'),
				),
			),
		);
	}
	
	return $status;
}

/**
 * Implementation of configure
 */
function configure_upload($settings)
{
	$settings['tmp_dir'] = setting_tmp_dir($settings);

	$options = array();

	if(setting_writable_tmp_dir($settings))
	{
		$options['tmp_dir'] = array(
			'name' => lang('tmp dir title', 'Temporary Files'),
			'status' => '',
			'description' => array(
				'list' => array(
					lang('tmp dir description', 'This directory will be used for uploaded files and storing temporary files like converted files and images.'),
				),
			),
			'type' => 'text',
			'value' => $settings['tmp_dir'],
		);
	}
	else
	{
		$options['tmp_dir'] = array(
			'name' => lang('tmp dir title', 'Temporary Files'),
			'status' => 'fail',
			'description' => array(
				'list' => array(
					lang('tmp dir fail description 1', 'The system has detected that this directory does not exist or is not writable.'),
					lang('tmp dir fail description 2', 'Please correct this error by entering a directory path that exists and is writable by the web server.'),
				),
			),
			'type' => 'text',
			'value' => $settings['tmp_dir'],
		);
	}
	
	return $options;
}
