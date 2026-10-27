<?php
/**
 * Application configuration.
 * A git-ignored config.local.php (same shape) overrides these values for local development.
 */
$config = [
    'app' => [
        'name'     => 'TrueTrust',
        'url'      => 'https://truetrust.online',
        'debug'    => false,
        'timezone' => 'Asia/Kolkata',
        'key'      => '31c86bf607e19869478a92137716d1428f3e7b33447de3d2de5e745e53248189',
    ],
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'sql_truetrust_online',
        'user'    => 'sql_truetrust_online',
        'pass'    => '9a2f9c9d4b6d8',
        'charset' => 'utf8mb4',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
