<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\HttpException;
use App\Core\RateLimit;
use App\Core\Settings;
use App\Services\Push;

final class Message
{
    public static function send(array $match, int $senderId, string $body): array
    {
        $body = trim(preg_replace("/\r\n?/", "\n", $body));
        $body = preg_replace("/\n{3,}/", "\n\n", $body);
        $max = max(100, Settings::int('message_max_length'));
        if ($body === '') {
            throw new HttpException(422, 'Write a message first.');
        }
        if (mb_strlen($body) > $max) {
            throw new HttpException(422, "Messages can be up to {$max} characters.");
        }
        if ($match['unmatched_at']) {
            throw new HttpException(403, 'This conversation has ended.');
        }
        if (!RateLimit::hit("msg:{$senderId}", 30, 60)) {
            throw new HttpException(429, "You're sending messages very quickly. Take a breath and try again.");
        }

        $flag = self::flagReason($body);
        $id = DB::insert('messages', [
            'match_id'    => $match['id'],
            'sender_id'   => $senderId,
            'body'        => $body,
            'is_flagged'  => $flag !== '' ? 1 : 0,
            'flag_reason' => $flag,
            'created_at'  => now(),
        ]);
        DB::query('UPDATE matches SET last_message_at = NOW() WHERE id = ?', [$match['id']]);

        // Push the other person unless they are in the app right now.
        $recipient = MatchModel::otherId($match, $senderId);
        $active = DB::value('SELECT last_active_at > NOW() - INTERVAL 45 SECOND FROM users WHERE id = ?', [$recipient]);
        if (!$active) {
            $sender = (string) DB::value('SELECT first_name FROM users WHERE id = ?', [$senderId]);
            Push::notify($recipient, 'message', $sender, mb_strimwidth($body, 0, 90, '…'), '/chat/' . $match['id'], 90);
        }
        return DB::one('SELECT * FROM messages WHERE id = ?', [$id]);
    }

    /**
     * Automatic safety screening: contact details shared very early, money talk
     * and admin-configured keywords get flagged for moderator review.
     */
    public static function flagReason(string $body): string
    {
        $lower = mb_strtolower($body);
        foreach (preg_split('/\R/', (string) Settings::get('flag_keywords')) as $kw) {
            $kw = trim(mb_strtolower($kw));
            if ($kw !== '' && preg_match('/(^|\W)' . preg_quote($kw, '/') . '(\W|$)/u', $lower)) {
                return 'Keyword: ' . $kw;
            }
        }
        $digits = preg_replace('/[\s\-().]/', '', $body);
        if (preg_match('/(\+?91)?[6-9]\d{9}/', $digits)) {
            return 'Phone number';
        }
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $body)) {
            return 'Email address';
        }
        if (preg_match('/https?:\/\/|www\./i', $body)) {
            return 'Link';
        }
        return '';
    }

    /** Latest messages in chronological order, optionally only those after $afterId. */
    public static function forMatch(int $matchId, int $afterId = 0, int $limit = 200): array
    {
        $rows = DB::all(
            'SELECT id, sender_id, body, is_removed, is_flagged, flag_reason, read_at, created_at FROM messages
             WHERE match_id = ? AND id > ? ORDER BY id DESC LIMIT ' . (int) $limit,
            [$matchId, $afterId]
        );
        return array_reverse($rows);
    }

    public static function markRead(int $matchId, int $readerId): void
    {
        DB::query('UPDATE messages SET read_at = NOW() WHERE match_id = ? AND sender_id <> ? AND read_at IS NULL', [$matchId, $readerId]);
    }

    public static function unreadTotal(int $userId): int
    {
        return (int) DB::value(
            "SELECT COUNT(*) FROM messages msg
             JOIN matches m ON m.id = msg.match_id
             JOIN users o ON o.id = msg.sender_id
             WHERE (m.user_a = ? OR m.user_b = ?) AND m.unmatched_at IS NULL
               AND msg.sender_id <> ? AND msg.read_at IS NULL AND o.status = 'active'",
            [$userId, $userId, $userId]
        );
    }

    /** Shape sent to the chat UI. */
    public static function toJson(array $m, int $viewerId): array
    {
        return [
            'id'      => (int) $m['id'],
            'mine'    => (int) $m['sender_id'] === $viewerId,
            'body'    => $m['is_removed'] ? '' : $m['body'],
            'removed' => (bool) $m['is_removed'],
            'read'    => $m['read_at'] !== null,
            'time'    => date('g:i A', strtotime($m['created_at'])),
            'date'    => date('Y-m-d', strtotime($m['created_at'])),
        ];
    }
}
