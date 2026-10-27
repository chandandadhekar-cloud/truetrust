<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class Config
{
    private static array $items = [];

    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /** Dot-notation lookup, e.g. Config::get('db.host'). */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
