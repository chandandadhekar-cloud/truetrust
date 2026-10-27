<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;

/** Single-use tokens for email verification and password resets. Only hashes are stored. */
final class EmailToken
{
    public static function issue(int $userId, string $type, int $ttlMinutes): string
    {
        DB::query('UPDATE email_tokens SET used_at = NOW() WHERE user_id = ? AND type = ? AND used_at IS NULL', [$userId, $type]);
        $token = bin2hex(random_bytes(32));
        DB::insert('email_tokens', [
            'user_id'    => $userId,
            'type'       => $type,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
            'created_at' => now(),
        ]);
        return $token;
    }

    /** Returns the user id and marks the token used, or null when invalid/expired. */
    public static function consume(string $token, string $type, bool $markUsed = true): ?int
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = DB::one(
            'SELECT * FROM email_tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token), $type]
        );
        if (!$row) {
            return null;
        }
        if ($markUsed) {
            DB::query('UPDATE email_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
        }
        return (int) $row['user_id'];
    }
}
