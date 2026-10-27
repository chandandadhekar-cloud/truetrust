<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\DB;
use App\Models\Photo;
use App\Core\Request;
use App\Core\Settings;
use App\Models\User;
use App\Services\Emails;

final class SettingsController extends Controller
{
    public function index(): string
    {
        $me = $this->me();
        $blocked = (int) DB::value('SELECT COUNT(*) FROM blocks WHERE user_id = ?', [$me['id']]);
        return view('settings/index', ['title' => 'Settings', 'nav' => true, 'me' => $me, 'blockedCount' => $blocked]);
    }

    public function blocked(): string
    {
        $me = $this->me();
        $people = DB::all(
            'SELECT u.id, u.first_name, u.dob, u.city, b.created_at FROM blocks b JOIN users u ON u.id = b.blocked_id
             WHERE b.user_id = ? ORDER BY b.created_at DESC',
            [$me['id']]
        );
        return view('settings/blocked', [
            'title'  => 'Blocked',
            'nav'    => true,
            'people' => $people,
            'photos' => Photo::idsForMany(array_column($people, 'id')),
        ]);
    }

    public function unblock(int $id): void
    {
        $me = $this->me();
        DB::query('DELETE FROM blocks WHERE user_id = ? AND blocked_id = ?', [$me['id'], $id]);
        flash('success', 'Unblocked. You may see each other in Discover again.');
        redirect('/settings/blocked');
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
