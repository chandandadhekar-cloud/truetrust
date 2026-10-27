<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Settings;
use App\Services\Emails;

final class SettingsController extends AdminController
{
    private const TOGGLES = [
        'registration_open', 'email_verification', 'photo_moderation', 'show_seed_profiles',
        'require_photo', 'pwa_prompt', 'maintenance_mode', 'announcement_active',
    ];
    private const TEXT = [
        'site_name', 'tagline', 'support_email', 'maintenance_message', 'announcement', 'flag_keywords',
        'mail_driver', 'mail_from_address', 'mail_from_name', 'smtp_host', 'smtp_user', 'smtp_secure',
    ];
    private const NUMBERS = [
        'min_age' => [18, 30], 'max_photos' => [1, 9], 'daily_like_limit' => [0, 10000],
        'daily_superlike_limit' => [0, 1000], 'message_max_length' => [100, 5000], 'smtp_port' => [1, 65535],
        'auto_hide_reports' => [0, 50],
    ];

    public function index(): string
    {
        return $this->render('settings/index', ['title' => 'Settings', 's' => Settings::all()]);
    }

    public function save(): void
    {
        $section = Request::str('section');
        $changed = [];
        foreach (self::TOGGLES as $k) {
            if (Request::input('_has_' . $k) !== null) {
                Settings::set($k, Request::bool($k) ? '1' : '0');
                $changed[] = $k;
            }
        }
        foreach (self::TEXT as $k) {
            if (array_key_exists($k, $_POST)) {
                Settings::set($k, trim((string) $_POST[$k]));
                $changed[] = $k;
            }
        }
        foreach (self::NUMBERS as $k => [$min, $max]) {
            if (array_key_exists($k, $_POST)) {
                Settings::set($k, (string) max($min, min($max, (int) $_POST[$k])));
                $changed[] = $k;
            }
        }
        // Only overwrite the stored SMTP password when a new one is typed.
        if (Request::str('smtp_pass') !== '') {
            Settings::set('smtp_pass', (string) $_POST['smtp_pass']);
            $changed[] = 'smtp_pass';
        }
        AdminAuth::log('settings.update', null, null, implode(', ', $changed));
        flash('success', 'Settings saved.');
        redirect('/admin/settings' . ($section ? '#' . $section : ''));
    }

    public function testEmail(): void
    {
        $to = Request::str('to') ?: (AdminAuth::admin()['email'] ?? '');
        $ok = Emails::send($to, 'Test email from ' . setting('site_name'), [
            'heading' => 'Email is working',
            'intro'   => 'This is a test message from your admin panel. If you can read this, verification and password reset emails will be delivered too.',
            'outro'   => 'Sent ' . date('j M Y, g:i A'),
        ]);
        flash($ok ? 'success' : 'error', $ok ? "Test email sent to {$to}. Check spam if you don't see it." : 'Sending failed: ' . Mailer::lastError());
        redirect('/admin/settings#email');
    }
}
