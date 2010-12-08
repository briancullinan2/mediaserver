<?php

error_reporting(E_ALL | E_STRICT);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.inc';

bootstrap();

raise_error('Bootstrap Complete! Processing Request.', E_DEBUG);

invoke_menu($_REQUEST);

$request_info = array(
	'Errors' => gzdeflate(serialize(array(
		'user' => $GLOBALS['user_errors'],
		'warn' => $GLOBALS['warn_errors'],
		'note' => $GLOBALS['note_errors'],
		'debug' => $GLOBALS['debug_errors'],
	))),
);

if(setting('database_enable'))
	db_query('UPDATE error ' . sql_update($request_info) . ' WHERE Time=?', array(
		$request_info['Errors'],
		date('Y-m-d h:i:s', $GLOBALS['tm_start'])
	));
