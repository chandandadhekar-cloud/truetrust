<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Models\User;
use App\Services\Emails;

final class SettingsController extends Controller
{
    public function index(): string
    {
        return view('settings/index', ['title' => 'Settings', 'nav' => true, 'me' => $this->me()]);
    }

    public function preferences(): void
    {
        $me = $this->me();
        $hidden = Request::bool('paused') ? 1 : 0;
        User::update((int) $me['id'], ['is_hidden' => $hidden]);
        flash('success', $hidden ? 'Your profile is paused. You can still chat with your matches.' : 'Your profile is visible again.');
        redirect('/settings');
    }

    public function password(): void
    {
        $me = $this->me();
        $current = (string) Request::input('current_password', '');
        $new = (string) Request::input('new_password', '');
        if (!password_verify($current, $me['password_hash'])) {
            with_errors(['current_password' => 'Your current password is incorrect.']);
            redirect('/settings#password');
        }
        if (mb_strlen($new) < 8) {
            with_errors(['new_password' => 'Use at least 8 characters.']);
            redirect('/settings#password');
        }
        User::update((int) $me['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        Auth::revokeAll((int) $me['id']);
        Auth::login((int) $me['id']);
        flash('success', 'Password changed. Other devices have been signed out.');
        redirect('/settings');
    }

    public function email(): void
    {
        $me = $this->me();
        $email = mb_strtolower(Request::str('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            with_errors(['email' => 'Enter a valid email address.']);
            redirect('/settings#email');
        }
        if (!password_verify((string) Request::input('password', ''), $me['password_hash'])) {
            with_errors(['email' => 'Your password is incorrect.']);
            redirect('/settings#email');
        }
        $existing = User::findByEmail($email);
        if ($existing && (int) $existing['id'] !== (int) $me['id']) {
            with_errors(['email' => 'That email is already used by another account.']);
            redirect('/settings#email');
        }
        User::update((int) $me['id'], ['email' => $email, 'email_verified_at' => null]);
        if (Settings::bool('email_verification')) {
            Emails::verification(User::find((int) $me['id']));
        }
        flash('success', 'Email updated.');
        redirect('/settings');
    }

    public function delete(): void
    {
        $me = $this->me();
        if (!password_verify((string) Request::input('password', ''), $me['password_hash'])) {
            with_errors(['delete_password' => 'Your password is incorrect.']);
            redirect('/settings#delete');
        }
        User::softDelete((int) $me['id']);
        Auth::logout();
        flash('success', 'Your account has been deleted. Take care.');
        redirect('/');
    }
}
