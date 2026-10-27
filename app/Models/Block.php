<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;

final class Block
{
    public static function between(int $a, int $b): bool
    {
        return (bool) DB::value(
            'SELECT 1 FROM blocks WHERE (user_id = ? AND blocked_id = ?) OR (user_id = ? AND blocked_id = ?) LIMIT 1',
            [$a, $b, $b, $a]
        );
    }

    /** Blocking hides both people from each other and ends any conversation. */
    public static function add(int $userId, int $blockedId): void
    {
        DB::query('INSERT IGNORE INTO blocks (user_id, blocked_id, created_at) VALUES (?, ?, NOW())', [$userId, $blockedId]);
        $match = MatchModel::between($userId, $blockedId);
        if ($match) {
            MatchModel::unmatch($match, $userId);
        }
    }
}
