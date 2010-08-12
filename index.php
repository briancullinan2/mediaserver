<?php

error_reporting(E_ALL | E_STRICT);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.inc';

bootstrap();

invoke_menu($_REQUEST);

session('errors', array(
	'user' => $GLOBALS['user_errors'],
	'warn' => $GLOBALS['warn_errors'],
	'note' => $GLOBALS['note_errors'],
	'debug' => $GLOBALS['debug_errors'],
));

