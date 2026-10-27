<?php
defined('APP_ROOT') || exit;

return static function (PDO $pdo): void {
    $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        endpoint VARCHAR(700) NOT NULL,
        endpoint_hash CHAR(64) NOT NULL,
        user_agent VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        last_used_at DATETIME NULL,
        UNIQUE KEY uq_push_endpoint (endpoint_hash),
        KEY idx_push_user (user_id),
        CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    // What a push wakes the service worker up to show.
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        type VARCHAR(20) NOT NULL,
        title VARCHAR(120) NOT NULL,
        body VARCHAR(255) NOT NULL DEFAULT '',
        url VARCHAR(255) NOT NULL DEFAULT '/',
        delivered_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY idx_notif_user (user_id, delivered_at),
        KEY idx_notif_created (created_at),
        CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS verification_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        pose VARCHAR(80) NOT NULL,
        selfie MEDIUMBLOB NOT NULL,
        mime VARCHAR(30) NOT NULL DEFAULT 'image/jpeg',
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        note VARCHAR(255) NOT NULL DEFAULT '',
        reviewed_by INT UNSIGNED NULL,
        reviewed_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY idx_verif_status (status, id),
        KEY idx_verif_user (user_id, id),
        CONSTRAINT fk_verif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS error_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        message VARCHAR(1000) NOT NULL,
        location VARCHAR(255) NOT NULL DEFAULT '',
        url VARCHAR(255) NOT NULL DEFAULT '',
        user_id INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        KEY idx_errors_created (created_at)
    ) $opts");

    $cols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('notify_matches', $cols, true)) {
        $pdo->exec("ALTER TABLE users
            ADD COLUMN notify_matches TINYINT(1) NOT NULL DEFAULT 1 AFTER is_hidden,
            ADD COLUMN notify_messages TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_matches,
            ADD COLUMN notify_likes TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_messages");
    }
};
