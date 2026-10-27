<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\RateLimit;
use App\Core\Request;

final class AuthController extends AdminController
{
    public function loginForm(): string
    {
        if (AdminAuth::admin()) {
            redirect('/admin');
        }
        return view('admin/login', ['title' => 'Admin sign in'], null);
    }

    public function login(): void
    {
        $email = mb_strtolower(Request::str('email'));
        $bucket = 'admin-login:' . Request::ip();
        if (!RateLimit::hit($bucket, 6, 900)) {
            flash('error', 'Too many attempts. Try again in 15 minutes.');
            redirect('/admin/login');
        }
        $admin = DB::one('SELECT * FROM admins WHERE email = ? AND is_active = 1', [$email]);
        if (!$admin || !password_verify((string) Request::input('password', ''), $admin['password_hash'])) {
            flash('error', "Those details don't match an admin account.");
            remember_input();
            redirect('/admin/login');
        }
        RateLimit::clear($bucket);
        AdminAuth::login((int) $admin['id']);
        AdminAuth::log('admin.login');
        redirect($admin['must_change_password'] ? '/admin/password' : '/admin');
    }

    public function logout(): void
    {
        AdminAuth::logout();
        redirect('/admin/login');
    }

    public function passwordForm(): string
    {
        return $this->render('password', ['title' => 'Change password', 'forced' => (bool) AdminAuth::admin()['must_change_password']]);
    }

    public function password(): void
    {
        $admin = AdminAuth::admin();
        $current = (string) Request::input('current_password', '');
        $new = (string) Request::input('new_password', '');
        if (!password_verify($current, $admin['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            redirect('/admin/password');
        }
        if (mb_strlen($new) < 10 || $new === $current) {
            flash('error', 'Choose a new password with at least 10 characters.');
            redirect('/admin/password');
        }
        DB::update('admins', ['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'must_change_password' => 0], 'id = ?', [$admin['id']]);
        AdminAuth::log('admin.password_changed');
        flash('success', 'Password updated.');
        redirect('/admin');
    }
}
