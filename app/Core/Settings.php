<?php
namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Admin-editable runtime settings, stored in the `settings` table.
 */
final class Settings
{
    public const DEFAULTS = [
        'site_name'              => 'TrueTrust',
        'tagline'                => 'Dating, minus the paywall.',
        'support_email'          => 'support@truetrust.online',
        'registration_open'      => '1',
        'email_verification'     => '0',
        'photo_moderation'       => '0',
        'show_seed_profiles'     => '1',
        'require_photo'          => '1',
        'min_age'                => '18',
        'max_photos'             => '6',
        'daily_like_limit'       => '0',
        'daily_superlike_limit'  => '5',
        'message_max_length'     => '1000',
        'auto_hide_reports'      => '3',
        'pwa_prompt'             => '1',
        'maintenance_mode'       => '0',
        'maintenance_message'    => 'We are making TrueTrust better. Back shortly.',
        'announcement_active'    => '0',
        'announcement'           => '',
        'flag_keywords'          => "paytm\ngpay\nphonepe\nupi\nsend money\nbank account\nifsc\nwestern union\nbitcoin\ncrypto\ninvestment\nforex\ngift card\nwhatsapp\ntelegram\nsnapchat\nonlyfans",
        'mail_driver'            => 'mail',
        'mail_from_address'      => 'no-reply@truetrust.online',
        'mail_from_name'         => 'TrueTrust',
        'smtp_host'              => '',
        'smtp_port'              => '587',
        'smtp_user'              => '',
        'smtp_pass'              => '',
        'smtp_secure'            => 'tls',
        'openai_api_key'         => '',
        'openai_image_model'     => 'gpt-image-1',
        'ai_default_quality'     => 'medium',
        'ai_budget_usd'          => '3',
        'ai_spent_usd'           => '0',
        'ai_profiles_mode'       => 'live',
    ];

    private static ?array $values = null;

    public static function all(): array
    {
        if (self::$values === null) {
            $rows = [];
            try {
                foreach (DB::all('SELECT `key`, `value` FROM settings') as $r) {
                    $rows[$r['key']] = (string) $r['value'];
                }
            } catch (\PDOException $e) {
                $rows = [];
            }
            self::$values = array_merge(self::DEFAULTS, $rows);
        }
        return self::$values;
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function bool(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function int(string $key): int
    {
        return (int) self::get($key, 0);
    }

    public static function set(string $key, string $value): void
    {
        DB::query(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        if (self::$values !== null) {
            self::$values[$key] = $value;
        }
    }
}
