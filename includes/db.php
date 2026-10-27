<?php
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit;
}

/**
 * Load app config: config.php if present, otherwise environment variables.
 */
function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $file = APP_ROOT . '/config.php';
    if (is_file($file)) {
        $config = require $file;
        return $config;
    }

    $config = [
        'db' => [
            'host'    => getenv('DB_HOST') ?: 'localhost',
            'port'    => (int) (getenv('DB_PORT') ?: 3306),
            'name'    => getenv('DB_NAME') ?: '',
            'user'    => getenv('DB_USER') ?: '',
            'pass'    => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
        ],
    ];
    return $config;
}

/**
 * Shared PDO connection. Throws PDOException on failure.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = app_config()['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);

    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
    return $pdo;
}

/**
 * Check the database connection without exposing credentials.
 * Returns ['ok' => bool, 'message' => string, 'version' => ?string].
 */
function db_status(): array
{
    if (!extension_loaded('pdo_mysql')) {
        return ['ok' => false, 'message' => 'PHP pdo_mysql extension is not installed', 'version' => null];
    }
    if (app_config()['db']['name'] === '') {
        return ['ok' => false, 'message' => 'Database is not configured', 'version' => null];
    }

    try {
        $version = db()->query('SELECT VERSION()')->fetchColumn();
        return ['ok' => true, 'message' => 'Database connected', 'version' => (string) $version];
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Database connection failed', 'version' => null];
    }
}
