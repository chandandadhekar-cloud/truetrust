<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }
        return $token;
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? (Request::json()['_csrf'] ?? ''));
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            throw new HttpException(419, 'Your session expired. Please refresh the page and try again.');
        }
    }
}
