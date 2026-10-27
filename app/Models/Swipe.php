<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\HttpException;
use App\Core\Settings;

final class Swipe
{
    public const ACTIONS = ['like', 'pass', 'superlike'];

    /** Records a swipe. Returns the match row when it completes a mutual like. */
    public static function record(array $me, int $targetId, string $action): array
    {
        $myId = (int) $me['id'];
        if (!in_array($action, self::ACTIONS, true) || $targetId === $myId) {
            throw new HttpException(422, 'Invalid action.');
        }
        $target = DB::one("SELECT id, first_name, is_seed FROM users WHERE id = ? AND status = 'active'", [$targetId]);
        if (!$target || Block::between($myId, $targetId)) {
            throw new HttpException(404, 'This profile is no longer available.');
        }

        if ($action !== 'pass') {
            self::enforceLimits($myId, $action);
        }

        DB::query(
            'INSERT INTO swipes (user_id, target_id, action, created_at) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE action = VALUES(action), created_at = NOW()',
            [$myId, $targetId, $action]
        );

        $match = null;
        if ($action !== 'pass') {
            $theyLiked = DB::value(
                "SELECT 1 FROM swipes WHERE user_id = ? AND target_id = ? AND action IN ('like','superlike')",
                [$targetId, $myId]
            );
            if ($theyLiked) {
                $match = MatchModel::create($myId, $targetId);
            }
        }

        return ['match' => $match, 'remaining' => self::remaining($myId)];
    }

    private static function enforceLimits(int $userId, string $action): void
    {
        $r = self::remaining($userId);
        if ($r['likes'] !== null && $r['likes'] <= 0) {
            throw new HttpException(429, "You've used today's likes. They refresh at midnight.");
        }
        if ($action === 'superlike' && $r['superlikes'] !== null && $r['superlikes'] <= 0) {
            throw new HttpException(429, "You're out of Super Likes for today. They refresh at midnight.");
        }
    }

    /** Remaining likes/superlikes for today; null means unlimited. */
    public static function remaining(int $userId): array
    {
        $likeLimit = Settings::int('daily_like_limit');
        $superLimit = Settings::int('daily_superlike_limit');
        $counts = DB::one(
            "SELECT SUM(action IN ('like','superlike')) AS likes, SUM(action = 'superlike') AS supers
             FROM swipes WHERE user_id = ? AND created_at >= CURDATE()",
            [$userId]
        );
        return [
            'likes'      => $likeLimit > 0 ? max(0, $likeLimit - (int) $counts['likes']) : null,
            'superlikes' => $superLimit > 0 ? max(0, $superLimit - (int) $counts['supers']) : null,
        ];
    }

    /** Undoes the most recent pass so a member can take another look. */
    public static function rewind(int $userId): ?int
    {
        $last = DB::one('SELECT id, target_id, action FROM swipes WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1', [$userId]);
        if (!$last || $last['action'] !== 'pass') {
            return null;
        }
        DB::query('DELETE FROM swipes WHERE id = ?', [$last['id']]);
        return (int) $last['target_id'];
    }

    /** Active members who liked $userId and haven't been answered yet. */
    public static function likesReceived(int $userId, int $limit = 60): array
    {
        $sql = "SELECT u.*, s.action AS their_action, s.created_at AS liked_at
                FROM swipes s
                JOIN users u ON u.id = s.user_id
                WHERE s.target_id = ? AND s.action IN ('like','superlike')
                  AND u.status = 'active' AND u.is_hidden = 0 AND u.onboarded_at IS NOT NULL
                  AND NOT EXISTS (SELECT 1 FROM swipes mine WHERE mine.user_id = ? AND mine.target_id = s.user_id)
                  AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id = ? AND b.blocked_id = u.id) OR (b.user_id = u.id AND b.blocked_id = ?))
                ORDER BY (s.action = 'superlike') DESC, s.created_at DESC
                LIMIT " . (int) $limit;
        return DB::all($sql, [$userId, $userId, $userId, $userId]);
    }

    public static function newLikesCount(array $me): int
    {
        $since = $me['likes_seen_at'] ?: '1970-01-01';
        return (int) DB::value(
            "SELECT COUNT(*) FROM swipes s JOIN users u ON u.id = s.user_id
             WHERE s.target_id = ? AND s.action IN ('like','superlike') AND s.created_at > ?
               AND u.status = 'active' AND u.is_hidden = 0
               AND NOT EXISTS (SELECT 1 FROM swipes mine WHERE mine.user_id = ? AND mine.target_id = s.user_id)",
            [$me['id'], $since, $me['id']]
        );
    }
}
