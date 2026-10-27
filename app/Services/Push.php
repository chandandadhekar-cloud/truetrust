<?php
namespace App\Services;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Settings;

/**
 * Web Push without payloads. Each push is an empty, VAPID-signed request that
 * wakes the service worker, which then fetches /api/push/pending over the
 * member's own session. Message text never passes through Google or Apple.
 *
 * Pushes are sent after the response has been flushed, so they never slow
 * down the request that triggered them.
 */
final class Push
{
    private const PREF = ['match' => 'notify_matches', 'message' => 'notify_messages', 'like' => 'notify_likes'];

    private static array $queue = [];
    private static bool $hooked = false;

    /** Records a notification for a member and schedules a push to their devices. */
    public static function notify(int $userId, string $type, string $title, string $body, string $url, int $throttleSeconds = 0): void
    {
        try {
            $u = DB::one('SELECT is_seed, status, notify_matches, notify_messages, notify_likes FROM users WHERE id = ?', [$userId]);
            if (!$u || $u['is_seed'] || $u['status'] !== 'active') {
                return;
            }
            if (isset(self::PREF[$type]) && !$u[self::PREF[$type]]) {
                return;
            }
            if ($throttleSeconds > 0 && DB::value(
                'SELECT 1 FROM notifications WHERE user_id = ? AND type = ? AND url = ? AND created_at > NOW() - INTERVAL ? SECOND LIMIT 1',
                [$userId, $type, $url, $throttleSeconds]
            )) {
                return;
            }
            DB::insert('notifications', [
                'user_id'    => $userId,
                'type'       => $type,
                'title'      => mb_substr($title, 0, 120),
                'body'       => mb_substr($body, 0, 255),
                'url'        => $url,
                'created_at' => now(),
            ]);
            if (random_int(1, 100) === 1) {
                DB::query('DELETE FROM notifications WHERE created_at < NOW() - INTERVAL 14 DAY');
            }
            self::$queue[$userId] = true;
            if (!self::$hooked) {
                self::$hooked = true;
                register_shutdown_function([self::class, 'flush']);
            }
        } catch (\Throwable $e) {
            error_log('Push notify: ' . $e->getMessage());
        }
    }

    public static function flush(): void
    {
        if (!self::$queue) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $ids = array_keys(self::$queue);
        self::$queue = [];
        try {
            $subs = DB::all('SELECT * FROM push_subscriptions WHERE user_id IN (' . DB::placeholders($ids) . ')', $ids);
            foreach ($subs as $sub) {
                self::sendTo($sub);
            }
        } catch (\Throwable $e) {
            error_log('Push flush: ' . $e->getMessage());
        }
    }

    /** Sends one wake-up push. Gone subscriptions (404/410) are removed. */
    public static function sendTo(array $sub): int
    {
        $status = self::post($sub['endpoint']);
        if ($status === 404 || $status === 410) {
            DB::query('DELETE FROM push_subscriptions WHERE id = ?', [$sub['id']]);
        } elseif ($status >= 200 && $status < 300) {
            DB::query('UPDATE push_subscriptions SET last_used_at = NOW() WHERE id = ?', [$sub['id']]);
        } else {
            error_log("Push endpoint returned {$status}");
        }
        return $status;
    }

    public static function subscribe(int $userId, string $endpoint, string $userAgent): void
    {
        $hash = hash('sha256', $endpoint);
        DB::query(
            'INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), user_agent = VALUES(user_agent)',
            [$userId, $endpoint, $hash, mb_substr($userAgent, 0, 255)]
        );
    }

    public static function unsubscribe(int $userId, string $endpoint): void
    {
        DB::query('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint_hash = ?', [$userId, hash('sha256', $endpoint)]);
    }

    /**
     * Notifications for the service worker to display: anything undelivered plus
     * the last few minutes, so each of a member's devices gets them. The worker
     * tags notifications by URL, so repeats on one device replace each other.
     */
    public static function pending(int $userId): array
    {
        $rows = DB::all(
            'SELECT id, type, title, body, url FROM notifications
             WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 DAY
               AND (delivered_at IS NULL OR created_at > NOW() - INTERVAL 5 MINUTE)
             ORDER BY id DESC LIMIT 10',
            [$userId]
        );
        if ($rows) {
            $ids = array_column($rows, 'id');
            DB::query('UPDATE notifications SET delivered_at = NOW() WHERE delivered_at IS NULL AND id IN (' . DB::placeholders($ids) . ')', $ids);
        }
        return $rows;
    }

    /** VAPID key pair, generated once and kept in settings. */
    public static function keys(): ?array
    {
        $public = Settings::get('vapid_public', '');
        $private = Settings::get('vapid_private', '');
        if ($public && $private) {
            return ['public' => $public, 'private' => $private];
        }
        if (!function_exists('openssl_pkey_new')) {
            return null;
        }
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if (!$key || !openssl_pkey_export($key, $pem)) {
            return null;
        }
        $d = openssl_pkey_get_details($key);
        $raw = "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $public = self::b64($raw);
        Settings::set('vapid_public', $public);
        Settings::set('vapid_private', $pem);
        return ['public' => $public, 'private' => $pem];
    }

    public static function publicKey(): string
    {
        return self::keys()['public'] ?? '';
    }

    private static function post(string $endpoint): int
    {
        $keys = self::keys();
        $parts = parse_url($endpoint);
        if (!$keys || empty($parts['scheme']) || empty($parts['host']) || $parts['scheme'] !== 'https') {
            return 0;
        }
        $aud = $parts['scheme'] . '://' . $parts['host'];
        $jwt = self::jwt($aud, $keys['private']);
        if ($jwt === null) {
            return 0;
        }
        $headers = [
            'TTL: 86400',
            'Urgency: high',
            'Content-Length: 0',
            'Authorization: vapid t=' . $jwt . ', k=' . $keys['public'],
        ];
        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => '',
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code;
        }
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => '', 'timeout' => 8, 'ignore_errors' => true]]);
        @file_get_contents($endpoint, false, $ctx);
        $line = $http_response_header[0] ?? '';
        return preg_match('/\s(\d{3})\s?/', $line, $m) ? (int) $m[1] : 0;
    }

    private static function jwt(string $aud, string $pem): ?string
    {
        $subject = 'mailto:' . Settings::get('support_email', 'support@truetrust.online');
        $input = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']))
            . '.' . self::b64(json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => $subject]));
        $key = openssl_pkey_get_private($pem);
        if (!$key || !openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }
        return $input . '.' . self::b64(self::derToRaw($der));
    }

    /** ES256 needs the raw 64-byte r||s signature, OpenSSL returns DER. */
    private static function derToRaw(string $der): string
    {
        $p = 2;
        if (ord($der[1]) > 0x80) {
            $p += ord($der[1]) - 0x80;
        }
        $rLen = ord($der[$p + 1]);
        $r = substr($der, $p + 2, $rLen);
        $p += 2 + $rLen;
        $sLen = ord($der[$p + 1]);
        $s = substr($der, $p + 2, $sLen);
        $fix = static fn ($v) => str_pad(substr(ltrim($v, "\0"), -32), 32, "\0", STR_PAD_LEFT);
        return $fix($r) . $fix($s);
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
