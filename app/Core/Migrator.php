<?php
namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Runs pending files from database/migrations automatically on the first
 * request after a deploy. Each file returns a callable(PDO): void.
 */
final class Migrator
{
    public static function ensure(): void
    {
        $files = glob(APP_ROOT . '/database/migrations/*.php') ?: [];
        sort($files);
        $names = array_map(static fn ($f) => basename($f, '.php'), $files);

        try {
            $done = DB::column('SELECT name FROM migrations');
            if (!array_diff($names, $done)) {
                return;
            }
        } catch (\PDOException $e) {
            // Table missing: fresh install.
        }

        $pdo = DB::pdo();
        if ((int) DB::value("SELECT GET_LOCK('tt_migrate', 30)") !== 1) {
            throw new \RuntimeException('Could not obtain migration lock.');
        }
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL UNIQUE,
                ran_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $done = DB::column('SELECT name FROM migrations');
            foreach ($files as $i => $file) {
                if (in_array($names[$i], $done, true)) {
                    continue;
                }
                $migration = require $file;
                $migration($pdo);
                DB::insert('migrations', ['name' => $names[$i], 'ran_at' => date('Y-m-d H:i:s')]);
            }
        } finally {
            DB::value("SELECT RELEASE_LOCK('tt_migrate')");
        }
    }
}
