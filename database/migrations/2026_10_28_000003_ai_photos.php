<?php
defined('APP_ROOT') || exit;

return static function (PDO $pdo): void {
    $cols = array_column($pdo->query('SHOW COLUMNS FROM photos')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('is_ai', $cols, true)) {
        $pdo->exec('ALTER TABLE photos ADD COLUMN is_ai TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
    }

    // One row per image to generate. Images are made one at a time by a
    // browser-driven runner so no single request runs longer than one API call.
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_photo_jobs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        shot TINYINT UNSIGNED NOT NULL DEFAULT 0,
        person TEXT NOT NULL,
        prompt TEXT NOT NULL,
        quality VARCHAR(10) NOT NULL DEFAULT 'medium',
        status ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
        error VARCHAR(500) NOT NULL DEFAULT '',
        photo_id INT UNSIGNED NULL,
        cost_usd DECIMAL(8,4) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        KEY idx_ai_status (status, id),
        KEY idx_ai_user (user_id, shot),
        CONSTRAINT fk_ai_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
