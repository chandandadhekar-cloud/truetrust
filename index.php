<?php
define('APP_ROOT', __DIR__);
define('APP_START', microtime(true));

require APP_ROOT . '/app/bootstrap.php';

App\Core\App::run();
