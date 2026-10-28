<?php
defined('APP_ROOT') || exit;

return static function (PDO $pdo): void {
    $cols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('is_tester', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_tester TINYINT(1) NOT NULL DEFAULT 0 AFTER is_seed');
    }
};
