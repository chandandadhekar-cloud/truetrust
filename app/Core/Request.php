<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');
        return $path;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function input(string $key, $default = null)
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        $json = self::json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        return $_GET[$key] ?? $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $v = self::input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::input($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key): bool
    {
        return in_array(self::input($key), ['1', 1, true, 'on', 'true', 'yes'], true);
    }

    public static function arr(string $key): array
    {
        $v = self::input($key, []);
        return is_array($v) ? $v : [];
    }

    public static function json(): array
    {
        static $json = null;
        if ($json === null) {
            $json = [];
            if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $decoded = json_decode((string) file_get_contents('php://input'), true);
                $json = is_array($decoded) ? $decoded : [];
            }
        }
        return $json;
    }

    public static function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        return is_array($f) && isset($f['error']) && $f['error'] !== UPLOAD_ERR_NO_FILE ? $f : null;
    }

    /** Normalises a multi-file input (name="photos[]") into a list of single-file arrays. */
    public static function files(string $key): array
    {
        $f = $_FILES[$key] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $f['type'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'error' => $f['error'][$i],
                'size' => $f['size'][$i],
            ];
        }
        return $out;
    }

    public static function wantsJson(): bool
    {
        return strncmp(self::path(), '/api/', 5) === 0
            || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }
}
