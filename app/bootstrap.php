<?php
defined('APP_ROOT') || exit;

$config = require APP_ROOT . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set($config['app']['timezone']);
mb_internal_encoding('UTF-8');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/app/helpers.php';

App\Core\Config::load($config);
