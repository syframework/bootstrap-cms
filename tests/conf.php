<?php
include __DIR__ . '/ServiceContainer.php';
include __DIR__ . '/DbContainer.php';

define('WEB_ROOT', '/webroot');
define('CONTROLLER_TRIGGER', 'controller');
define('ACTION_TRIGGER', 'action');
define('ACTION_PARAM', 'action_param');
define('LANG', 'fr');
define('LANGS', ['fr' => 'Fran&ccedil;ais', 'en' => 'English', 'es' => 'Espa&ntilde;ol', 'it' => 'Italiano']);

// Database informations
define('DATABASE_CONFIG', [
	'host'     => '127.0.0.1',
	'port'     => '3333',
	'dbname'   => 'sytest',
	'username' => 'root',
	'password' => 'password',
]);