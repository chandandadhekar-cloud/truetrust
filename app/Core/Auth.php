<?php
namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Member authentication: session login plus long-lived "remember me" tokens
 * so the installed PWA stays signed in.
 */
final class Auth
{
    private const COOKIE = 'tt_remember';
    private const REMEMBER_DAYS = 90;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function boot(): void
    {
        if (!Session::get('uid') && !empty($_COOKIE[self::COOKIE])) {
            self::loginFromCookie((string) $_COOKIE[self::COOKIE]);
        }
        $user = self::user();
        if ($user) {
            $last = (int) Session::get('touched', 0);
            if (time() - $last > 60) {
                DB::query('UPDATE users SET last_active_at = NOW() WHERE id = ?', [$user['id']]);
                Session::put('touched', time());
            }
        }
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int) Session::get('uid', 0);
            self::$user = $id ? DB::one("SELECT * FROM users WHERE id = ? AND status <> 'deleted'", [$id]) : null;
            if ($id && !self::$user) {
                Session::forget('uid');
            }
        }
        return self::$user;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function refresh(): void
    {
        self::$loaded = false;
        self::$user = null;
    }

    public static function login(int $userId, bool $remember = true): void
    {
        Session::regenerate();
        Session::put('uid', $userId);
        Session::put('touched', 0);
        self::refresh();
        DB::query('UPDATE users SET last_login_at = NOW(), last_active_at = NOW() WHERE id = ?', [$userId]);
        if ($remember) {
            self::issueRememberToken($userId);
        }
    }

    public static function logout(): void
    {
        if (!empty($_COOKIE[self::COOKIE])) {
            [$selector] = explode(':', (string) $_COOKIE[self::COOKIE]) + [''];
            DB::query('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        }
        self::setCookie('', time() - 3600);
        Session::forget('uid');
        Session::regenerate();
        self::refresh();
    }

    /** Signs a user out on every device (password change, ban, deletion). */
    public static function revokeAll(int $userId): void
    {
        DB::query('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        DB::insert('remember_tokens', [
            'user_id'        => $userId,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => date('Y-m-d H:i:s', time() + 86400 * self::REMEMBER_DAYS),
            'created_at'     => now(),
        ]);
        self::setCookie($selector . ':' . $validator, time() + 86400 * self::REMEMBER_DAYS);
    }

    private static function loginFromCookie(string $cookie): void
    {
        $parts = explode(':', $cookie);
        if (count($parts) !== 2) {
            return;
        }
        [$selector, $validator] = $parts;
        $row = DB::one('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()', [$selector]);
        if (!$row || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
            self::setCookie('', time() - 3600);
            return;
        }
        $status = DB::value('SELECT status FROM users WHERE id = ?', [$row['user_id']]);
        if ($status === null || $status === 'deleted') {
            DB::query('DELETE FROM remember_tokens WHERE id = ?', [$row['id']]);
            self::setCookie('', time() - 3600);
            return;
        }
        Session::regenerate();
        Session::put('uid', (int) $row['user_id']);
        self::refresh();

        // Rotate tokens older than a day. The old one stays valid for two minutes,
        // because an app launch fires several requests with the same cookie at once.
        if (strtotime($row['created_at']) < time() - 86400 && strtotime($row['expires_at']) > time() + 300) {
            DB::query('UPDATE remember_tokens SET expires_at = NOW() + INTERVAL 2 MINUTE WHERE id = ?', [$row['id']]);
            self::issueRememberToken((int) $row['user_id']);
        }
        if (random_int(1, 50) === 1) {
            DB::query('DELETE FROM remember_tokens WHERE expires_at < NOW()');
            DB::query('DELETE FROM email_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY');
        }
    }

    private static function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
