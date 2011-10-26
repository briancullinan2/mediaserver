<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.inc';

bootstrap();

invoke_menu($_REQUEST);

invoke_all('end');

