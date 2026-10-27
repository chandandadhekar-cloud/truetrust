<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class AdminAuth
{
    private static ?array $admin = null;
    private static bool $loaded = false;

    public static function admin(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int) Session::get('admin_id', 0);
            self::$admin = $id ? DB::one('SELECT * FROM admins WHERE id = ? AND is_active = 1', [$id]) : null;
        }
        return self::$admin;
    }

    public static function id(): int
    {
        return (int) (self::admin()['id'] ?? 0);
    }

    public static function isOwner(): bool
    {
        return (self::admin()['role'] ?? '') === 'owner';
    }

    public static function login(int $adminId): void
    {
        Session::regenerate();
        Session::put('admin_id', $adminId);
        self::$loaded = false;
        DB::query('UPDATE admins SET last_login_at = NOW() WHERE id = ?', [$adminId]);
    }

    public static function logout(): void
    {
        Session::forget('admin_id');
        Session::regenerate();
        self::$loaded = false;
        self::$admin = null;
    }

    /** Writes an entry to the admin audit trail. */
    public static function log(string $action, ?string $targetType = null, ?int $targetId = null, string $details = ''): void
    {
        DB::insert('admin_logs', [
            'admin_id'    => self::id() ?: null,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => mb_substr($details, 0, 1000),
            'ip'          => Request::ip(),
            'created_at'  => now(),
        ]);
    }
}
