<?php
namespace App\Services;

defined('APP_ROOT') || exit;

use App\Core\Mailer;
use App\Models\EmailToken;

final class Emails
{
    public static function verification(array $user): bool
    {
        $token = EmailToken::issue((int) $user['id'], 'verify', 60 * 48);
        return self::send($user['email'], 'Confirm your email', [
            'heading' => 'Confirm your email',
            'intro'   => 'Tap the button below to confirm this is your email address. The link works for 48 hours.',
            'button'  => 'Confirm email',
            'link'    => url('/verify/' . $token),
            'outro'   => "If you didn't create an account, you can ignore this email.",
        ]);
    }

    public static function passwordReset(array $user): bool
    {
        $token = EmailToken::issue((int) $user['id'], 'reset', 60);
        return self::send($user['email'], 'Reset your password', [
            'heading' => 'Reset your password',
            'intro'   => 'Someone asked to reset the password for your account. If that was you, choose a new password below. The link works for one hour.',
            'button'  => 'Choose a new password',
            'link'    => url('/reset-password/' . $token),
            'outro'   => "If you didn't ask for this, your account is safe and you can ignore this email.",
        ]);
    }

    public static function send(string $to, string $subject, array $data): bool
    {
        $html = partial('emails/layout', $data);
        return Mailer::send($to, $subject, $html);
    }
}
