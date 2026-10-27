<?php
defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function url(string $path = '/'): string
{
    return rtrim(Config::get('app.url'), '/') . '/' . ltrim($path, '/');
}

/** Static asset URL with a cache-busting version from the file's mtime. */
function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

function setting(string $key, $default = null)
{
    return Settings::get($key, $default);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function redirect(string $to, int $status = 302): void
{
    header('Location: ' . $to, true, $status);
    exit;
}

function back(string $fallback = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    redirect($ref && $host === ($_SERVER['HTTP_HOST'] ?? '') ? $ref : $fallback);
}

function json($data, int $status = 200): string
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function view(string $view, array $data = [], ?string $layout = 'layouts/app'): string
{
    return View::render($view, $data, $layout);
}

function partial(string $view, array $data = []): string
{
    return View::partial($view, $data);
}

function abort(int $status, string $message = ''): void
{
    throw new HttpException($status, $message);
}

function flash(string $type, string $message): void
{
    Session::flash('toast', ['type' => $type, 'message' => $message]);
}

/** Keeps submitted form values across a redirect. */
function remember_input(array $except = ['password', 'password_confirmation', '_csrf']): void
{
    Session::flash('old', array_diff_key($_POST, array_flip($except)));
}

function old(string $key, $default = '')
{
    static $old = null;
    if ($old === null) {
        $old = Session::pull('old', []);
    }
    return $old[$key] ?? $default;
}

function errors(): array
{
    static $errors = null;
    if ($errors === null) {
        $errors = Session::pull('errors', []);
    }
    return $errors;
}

function error_for(string $field): string
{
    $msg = errors()[$field] ?? '';
    return $msg ? '<p class="field-error">' . e($msg) . '</p>' : '';
}

function with_errors(array $errors): void
{
    Session::flash('errors', $errors);
    remember_input();
}

function me(): ?array
{
    return Auth::user();
}

function age(?string $dob): int
{
    if (!$dob) {
        return 0;
    }
    return (int) (new DateTime($dob))->diff(new DateTime('today'))->y;
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h';
    }
    if ($diff < 86400 * 7) {
        return floor($diff / 86400) . 'd';
    }
    return date('j M', strtotime($datetime));
}

function activity_label(?string $lastActive): string
{
    if (!$lastActive) {
        return '';
    }
    $diff = time() - strtotime($lastActive);
    if ($diff < 600) {
        return 'Active now';
    }
    if ($diff < 86400) {
        return 'Active today';
    }
    if ($diff < 86400 * 7) {
        return 'Active this week';
    }
    return '';
}

function is_online(?string $lastActive): bool
{
    return $lastActive !== null && time() - strtotime($lastActive) < 600;
}

function photo_url(?int $photoId, string $size = 'full'): string
{
    return $photoId ? "/photo/{$photoId}/" . ($size === 'thumb' ? 'thumb' : 'full') : '';
}

function initials(string $name): string
{
    return mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
}

function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

function selected($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(bool $on): string
{
    return $on ? ' checked' : '';
}

function active_nav(string $prefix): string
{
    $path = App\Core\Request::path();
    return ($path === $prefix || strpos($path, rtrim($prefix, '/') . '/') === 0) ? ' is-active' : '';
}

/** Inline SVG icon. Stroke icons inherit currentColor. */
function icon(string $name, int $size = 22, string $class = ''): string
{
    static $paths = [
        'heart'      => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'x'          => '<path d="M18 6 6 18M6 6l12 12"/>',
        'star'       => '<path d="m12 2.5 2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z"/>',
        'undo'       => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        'chat'       => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l1.9-5.2a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 12.5 3.5 8.4 8.4 0 0 1 21 11.5z"/>',
        'user'       => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'sliders'    => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
        'pin'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'briefcase'  => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'cap'        => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
        'check'      => '<path d="M20 6 9 17l-5-5"/>',
        'shield'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'more'       => '<circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/><circle cx="5" cy="12" r="1.2"/>',
        'back'       => '<path d="M15 18l-6-6 6-6"/>',
        'next'       => '<path d="m9 18 6-6-6-6"/>',
        'down'       => '<path d="m6 9 6 6 6-6"/>',
        'up'         => '<path d="m18 15-6-6-6 6"/>',
        'send'       => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
        'camera'     => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'trash'      => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'flag'       => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>',
        'block'      => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
        'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'eye'        => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'    => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.1-5.9M9.9 4.2A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2M1 1l22 22"/>',
        'edit'       => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'bell'       => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'lock'       => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'mail'       => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'home'       => '<path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'chart'      => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
        'alert'      => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/>',
        'download'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'image'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'zap'        => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
        'crosshair'  => '<circle cx="12" cy="12" r="9"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/>',
        'share'      => '<path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/>',
        'add-square' => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/>',
        'ruler'      => '<path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/>',
        'glass'      => '<path d="M8 22h8M12 15v7M6 3h12l-1 6a5 5 0 0 1-10 0z"/>',
        'smoke'      => '<path d="M2 16h15v4H2zM19 16v4M22 16v4M18 12c0-2-2-2-2-4s2-2 2-4"/>',
        'target'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'sparkle'    => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
        'globe'      => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'leaf'       => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.1-6"/>',
        'calendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'refresh'    => '<path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15"/>',
        'menu'       => '<path d="M3 12h18M3 6h18M3 18h18"/>',
        'wifi-off'   => '<path d="M1 1l22 22M16.7 11.1A11 11 0 0 1 19 12.6M5 12.6a11 11 0 0 1 5.2-2.5M10.7 5A16 16 0 0 1 22.6 9M1.4 9a16 16 0 0 1 4.4-2.7M8.5 16.1a5 5 0 0 1 7 0M12 20h.01"/>',
    ];
    if ($name === 'verified') {
        return '<svg class="icon icon-verified ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-label="Verified"><path fill="currentColor" d="M12 1.5l2.6 1.9 3.2-.1 1 3 2.6 1.9-1 3.1 1 3.1-2.6 1.9-1 3-3.2-.1L12 22.5l-2.6-1.9-3.2.1-1-3-2.6-1.9 1-3.1-1-3.1 2.6-1.9 1-3 3.2.1z"/><path d="M8 12.2l2.6 2.6L16.2 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
    $body = $paths[$name] ?? $paths['info'];
    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/** Profile option lists from app/Data/options.php. */
function opt(string $group): array
{
    static $options = null;
    if ($options === null) {
        $options = require APP_ROOT . '/app/Data/options.php';
    }
    return $options[$group] ?? [];
}

function opt_label(string $group, ?string $key): string
{
    return $key === null || $key === '' ? '' : (opt($group)[$key] ?? $key);
}

function cities(): array
{
    static $cities = null;
    if ($cities === null) {
        $cities = require APP_ROOT . '/app/Data/cities.php';
    }
    return $cities;
}

function find_city(string $name): ?array
{
    foreach (cities() as $c) {
        if (strcasecmp($c[0], $name) === 0) {
            return $c;
        }
    }
    return null;
}

/** Brand wordmark: "TrueTrust" renders as True + italic rose "Trust". */
function wordmark(): string
{
    $name = (string) setting('site_name');
    if (preg_match('/^(\p{Lu}\p{Ll}+)(\p{Lu}.*)$/u', $name, $m)) {
        return '<span class="brand-word">' . e($m[1]) . '<i>' . e($m[2]) . '</i></span>';
    }
    return '<span class="brand-word">' . e($name) . '</span>';
}
