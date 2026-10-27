<?php
defined('APP_ROOT') || exit;

return static function (PDO $pdo): void {
    $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        `key` VARCHAR(64) PRIMARY KEY,
        `value` TEXT NOT NULL
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(60) NOT NULL DEFAULT '',
        last_name VARCHAR(60) NOT NULL DEFAULT '',
        gender ENUM('man','woman','nonbinary') NULL,
        interested_in ENUM('men','women','everyone') NULL,
        dob DATE NULL,
        city VARCHAR(80) NOT NULL DEFAULT '',
        state VARCHAR(80) NOT NULL DEFAULT '',
        lat DECIMAL(9,6) NULL,
        lng DECIMAL(9,6) NULL,
        bio TEXT NULL,
        job_title VARCHAR(100) NOT NULL DEFAULT '',
        company VARCHAR(100) NOT NULL DEFAULT '',
        education VARCHAR(120) NOT NULL DEFAULT '',
        height_cm SMALLINT UNSIGNED NULL,
        religion VARCHAR(40) NOT NULL DEFAULT '',
        mother_tongue VARCHAR(40) NOT NULL DEFAULT '',
        languages VARCHAR(255) NOT NULL DEFAULT '',
        drinking VARCHAR(20) NOT NULL DEFAULT '',
        smoking VARCHAR(20) NOT NULL DEFAULT '',
        diet VARCHAR(20) NOT NULL DEFAULT '',
        looking_for VARCHAR(30) NOT NULL DEFAULT '',
        interests VARCHAR(500) NOT NULL DEFAULT '',
        prompts TEXT NULL,
        pref_age_min TINYINT UNSIGNED NOT NULL DEFAULT 18,
        pref_age_max TINYINT UNSIGNED NOT NULL DEFAULT 40,
        pref_distance_km SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('active','suspended','banned','deleted') NOT NULL DEFAULT 'active',
        ban_reason VARCHAR(255) NOT NULL DEFAULT '',
        is_seed TINYINT(1) NOT NULL DEFAULT 0,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        is_spotlight TINYINT(1) NOT NULL DEFAULT 0,
        is_hidden TINYINT(1) NOT NULL DEFAULT 0,
        email_verified_at DATETIME NULL,
        onboarded_at DATETIME NULL,
        likes_seen_at DATETIME NULL,
        last_active_at DATETIME NULL,
        last_login_at DATETIME NULL,
        signup_ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uq_users_email (email),
        KEY idx_users_discover (status, onboarded_at, gender, is_hidden),
        KEY idx_users_active (last_active_at),
        KEY idx_users_seed (is_seed),
        KEY idx_users_created (created_at)
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS photos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        position TINYINT UNSIGNED NOT NULL DEFAULT 0,
        mime VARCHAR(30) NOT NULL,
        width SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        height SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved',
        created_at DATETIME NOT NULL,
        KEY idx_photos_user (user_id, status, position),
        KEY idx_photos_status (status),
        CONSTRAINT fk_photos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    // Image bytes live apart from photo metadata so listing queries never touch blobs.
    $pdo->exec("CREATE TABLE IF NOT EXISTS photo_blobs (
        photo_id INT UNSIGNED PRIMARY KEY,
        data MEDIUMBLOB NOT NULL,
        thumb MEDIUMBLOB NOT NULL,
        CONSTRAINT fk_blobs_photo FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS swipes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        target_id INT UNSIGNED NOT NULL,
        action ENUM('like','pass','superlike') NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_swipe (user_id, target_id),
        KEY idx_swipes_target (target_id, action),
        KEY idx_swipes_user_day (user_id, action, created_at),
        CONSTRAINT fk_swipes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_swipes_target FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS matches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_a INT UNSIGNED NOT NULL,
        user_b INT UNSIGNED NOT NULL,
        seen_a TINYINT(1) NOT NULL DEFAULT 0,
        seen_b TINYINT(1) NOT NULL DEFAULT 0,
        unmatched_at DATETIME NULL,
        unmatched_by INT UNSIGNED NULL,
        last_message_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_match (user_a, user_b),
        KEY idx_matches_b (user_b),
        KEY idx_matches_last (last_message_at),
        CONSTRAINT fk_matches_a FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_matches_b FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        match_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        is_flagged TINYINT(1) NOT NULL DEFAULT 0,
        flag_reason VARCHAR(120) NOT NULL DEFAULT '',
        is_removed TINYINT(1) NOT NULL DEFAULT 0,
        read_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY idx_messages_match (match_id, id),
        KEY idx_messages_flagged (is_flagged, id),
        KEY idx_messages_created (created_at),
        CONSTRAINT fk_messages_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS blocks (
        user_id INT UNSIGNED NOT NULL,
        blocked_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (user_id, blocked_id),
        KEY idx_blocks_blocked (blocked_id),
        CONSTRAINT fk_blocks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_blocks_blocked FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT UNSIGNED NOT NULL,
        reported_id INT UNSIGNED NOT NULL,
        reason VARCHAR(40) NOT NULL,
        details TEXT NULL,
        status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        resolved_by INT UNSIGNED NULL,
        resolved_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY idx_reports_status (status, id),
        KEY idx_reports_reported (reported_id),
        CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_reports_reported FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        selector CHAR(24) NOT NULL,
        validator_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_selector (selector),
        KEY idx_remember_user (user_id),
        CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS email_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        type ENUM('verify','reset') NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_token (token_hash),
        KEY idx_tokens_user (user_id, type),
        CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bucket VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL,
        KEY idx_rate (bucket, created_at)
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('owner','moderator') NOT NULL DEFAULT 'moderator',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        must_change_password TINYINT(1) NOT NULL DEFAULT 0,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_admin_email (email)
    ) $opts");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id INT UNSIGNED NULL,
        action VARCHAR(60) NOT NULL,
        target_type VARCHAR(30) NULL,
        target_id INT UNSIGNED NULL,
        details VARCHAR(1000) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        KEY idx_logs_created (created_at),
        KEY idx_logs_target (target_type, target_id)
    ) $opts");

    // First owner account. The password is temporary and must be changed at first sign-in.
    $exists = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($exists === 0) {
        $st = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, must_change_password, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
        $st->execute(['Owner', 'admin@truetrust.online', '$2y$10$AaW0I1br8IceHtbXtV2pm.HrBMzc8VDawL0TZS8Qhi1Og52hF88JO', 'owner']);
    }
};
