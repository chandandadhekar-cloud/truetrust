<?php
// Local development only: emulates the nginx try_files rule for `php -S`.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\.(md|sql|log|sample|ini)$/i', $path)) {
    http_response_code(403);
    exit;
}
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
require __DIR__ . '/index.php';
