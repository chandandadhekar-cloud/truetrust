<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class RateLimit
{
    /** Records a hit and returns false once $max hits occurred within $seconds. */
    public static function hit(string $bucket, int $max, int $seconds): bool
    {
        $bucket = substr($bucket, 0, 120);
        $count = (int) DB::value(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at > (NOW() - INTERVAL ? SECOND)',
            [$bucket, $seconds]
        );
        if ($count >= $max) {
            return false;
        }
        DB::insert('rate_limits', ['bucket' => $bucket, 'created_at' => now()]);
        if (random_int(1, 200) === 1) {
            DB::query('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
        }
        return true;
    }

    public static function clear(string $bucket): void
    {
        DB::query('DELETE FROM rate_limits WHERE bucket = ?', [$bucket]);
    }
}
