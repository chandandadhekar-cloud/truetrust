<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;

/** A mutual like between two members. (Named MatchModel because `match` is reserved in PHP 8.) */
final class MatchModel
{
    public static function create(int $u1, int $u2): array
    {
        [$a, $b] = $u1 < $u2 ? [$u1, $u2] : [$u2, $u1];
        DB::query(
            'INSERT INTO matches (user_a, user_b, created_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE id = id',
            [$a, $b]
        );
        return DB::one('SELECT * FROM matches WHERE user_a = ? AND user_b = ?', [$a, $b]);
    }

    public static function between(int $u1, int $u2): ?array
    {
        [$a, $b] = $u1 < $u2 ? [$u1, $u2] : [$u2, $u1];
        return DB::one('SELECT * FROM matches WHERE user_a = ? AND user_b = ?', [$a, $b]);
    }

    /** A match the given member belongs to, or null. */
    public static function forMember(int $matchId, int $userId): ?array
    {
        return DB::one('SELECT * FROM matches WHERE id = ? AND (user_a = ? OR user_b = ?)', [$matchId, $userId, $userId]);
    }

    public static function otherId(array $match, int $userId): int
    {
        return (int) ($match['user_a'] == $userId ? $match['user_b'] : $match['user_a']);
    }

    public static function markSeen(array $match, int $userId): void
    {
        $col = $match['user_a'] == $userId ? 'seen_a' : 'seen_b';
        DB::query("UPDATE matches SET {$col} = 1 WHERE id = ?", [$match['id']]);
    }

    public static function unmatch(array $match, int $byUserId): void
    {
        DB::query('UPDATE matches SET unmatched_at = NOW(), unmatched_by = ? WHERE id = ? AND unmatched_at IS NULL', [$byUserId, $match['id']]);
    }

    /**
     * Active matches for a member with the other person, last message and unread count.
     */
    public static function listFor(int $userId): array
    {
        $matches = DB::all(
            "SELECT m.*, IF(m.user_a = ?, m.user_b, m.user_a) AS other_id, IF(m.user_a = ?, m.seen_a, m.seen_b) AS seen
             FROM matches m
             JOIN users o ON o.id = IF(m.user_a = ?, m.user_b, m.user_a)
             WHERE (m.user_a = ? OR m.user_b = ?) AND m.unmatched_at IS NULL AND o.status = 'active'
               AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id = m.user_a AND b.blocked_id = m.user_b) OR (b.user_id = m.user_b AND b.blocked_id = m.user_a))
             ORDER BY COALESCE(m.last_message_at, m.created_at) DESC",
            [$userId, $userId, $userId, $userId, $userId]
        );
        if (!$matches) {
            return [];
        }
        $ids = array_map('intval', array_column($matches, 'id'));
        $in = DB::placeholders($ids);

        $last = [];
        foreach (DB::all("SELECT m1.match_id, m1.sender_id, m1.body, m1.is_removed, m1.created_at, m1.read_at FROM messages m1
                          JOIN (SELECT match_id, MAX(id) AS id FROM messages WHERE match_id IN ($in) GROUP BY match_id) x ON x.id = m1.id", $ids) as $r) {
            $last[(int) $r['match_id']] = $r;
        }
        $unread = [];
        foreach (DB::all("SELECT match_id, COUNT(*) AS n FROM messages WHERE match_id IN ($in) AND sender_id <> ? AND read_at IS NULL GROUP BY match_id", array_merge($ids, [$userId])) as $r) {
            $unread[(int) $r['match_id']] = (int) $r['n'];
        }
        $otherIds = array_map('intval', array_column($matches, 'other_id'));
        $others = [];
        foreach (DB::all('SELECT id, first_name, dob, is_verified, last_active_at FROM users WHERE id IN (' . DB::placeholders($otherIds) . ')', $otherIds) as $o) {
            $others[(int) $o['id']] = $o;
        }
        $photos = Photo::idsForMany($otherIds);

        $out = [];
        foreach ($matches as $m) {
            $oid = (int) $m['other_id'];
            $o = $others[$oid] ?? null;
            if (!$o) {
                continue;
            }
            $out[] = [
                'id'       => (int) $m['id'],
                'seen'     => (bool) $m['seen'],
                'created'  => $m['created_at'],
                'last'     => $last[(int) $m['id']] ?? null,
                'unread'   => $unread[(int) $m['id']] ?? 0,
                'other'    => [
                    'id'       => $oid,
                    'name'     => $o['first_name'],
                    'age'      => age($o['dob']),
                    'verified' => (bool) $o['is_verified'],
                    'online'   => is_online($o['last_active_at']),
                    'photo'    => photo_url($photos[$oid][0] ?? null, 'thumb'),
                ],
            ];
        }
        return $out;
    }

    public static function unseenCount(int $userId): int
    {
        return (int) DB::value(
            "SELECT COUNT(*) FROM matches m JOIN users o ON o.id = IF(m.user_a = ?, m.user_b, m.user_a)
             WHERE ((m.user_a = ? AND m.seen_a = 0) OR (m.user_b = ? AND m.seen_b = 0))
               AND m.unmatched_at IS NULL AND m.last_message_at IS NULL AND o.status = 'active'",
            [$userId, $userId, $userId]
        );
    }
}
