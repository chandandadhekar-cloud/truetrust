<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\Request;
use App\Core\RateLimit;
use App\Core\Session;
use App\Core\Settings;
use App\Models\EmailToken;
use App\Models\User;
use App\Services\Emails;

final class AuthController extends Controller
{
    public function signupForm(): string
    {
        return view('auth/signup', ['title' => 'Create account', 'closed' => !Settings::bool('registration_open')]);
    }

    public function signup(): void
    {
        if (!Settings::bool('registration_open')) {
            flash('error', 'New sign-ups are paused right now. Please check back soon.');
            redirect('/signup');
        }
        $email = mb_strtolower(Request::str('email'));
        $password = (string) Request::input('password', '');
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (User::findByEmail($email)) {
            $errors['email'] = 'An account with this email already exists. Try signing in.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        } elseif (mb_strlen($password) > 200) {
            $errors['password'] = 'That password is too long.';
        }
        if (!Request::bool('agree')) {
            $errors['agree'] = 'Please confirm you are 18+ and agree to the terms.';
        }
        if ($errors) {
            with_errors($errors);
            redirect('/signup');
        }
        if (!RateLimit::hit('signup:' . Request::ip(), 8, 3600)) {
            flash('error', 'Too many sign-ups from this network. Please try again in a while.');
            redirect('/signup');
        }

        $id = User::create([
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'signup_ip'     => Request::ip(),
        ]);
        Auth::login($id);

        if (Settings::bool('email_verification')) {
            Emails::verification(User::find($id));
            redirect('/verify-email');
        }
        redirect('/onboarding/basics');
    }

    public function loginForm(): string
    {
        return view('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        $email = mb_strtolower(Request::str('email'));
        $password = (string) Request::input('password', '');
        $bucket = 'login:' . sha1($email . '|' . Request::ip());

        if (!RateLimit::hit($bucket, 8, 900)) {
            with_errors(['email' => 'Too many attempts. Wait 15 minutes and try again, or reset your password.']);
            redirect('/login');
        }
        $user = User::findByEmail($email);
        if (!$user || $user['status'] === 'deleted' || !password_verify($password, $user['password_hash'])) {
            with_errors(['email' => "That email and password don't match."]);
            redirect('/login');
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }
        RateLimit::clear($bucket);
        Auth::login((int) $user['id']);

        $intended = Session::get('intended');
        Session::forget('intended');
        redirect(is_string($intended) && strpos($intended, '/') === 0 && strpos($intended, '//') !== 0 ? $intended : '/discover');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }

    public function forgotForm(): string
    {
        return view('auth/forgot', ['title' => 'Reset password']);
    }

    public function forgot(): void
    {
        $email = mb_strtolower(Request::str('email'));
        if (RateLimit::hit('forgot:' . Request::ip(), 5, 900)) {
            $user = User::findByEmail($email);
            if ($user && $user['status'] !== 'deleted' && !$user['is_seed']) {
                Emails::passwordReset($user);
            }
        }
        flash('success', 'If that email has an account, a reset link is on its way.');
        redirect('/login');
    }

    public function resetForm(string $token): string
    {
        $valid = EmailToken::consume($token, 'reset', false) !== null;
        return view('auth/reset', ['title' => 'Choose a new password', 'token' => $token, 'valid' => $valid]);
    }

    public function reset(string $token): void
    {
        $password = (string) Request::input('password', '');
        if (mb_strlen($password) < 8) {
            with_errors(['password' => 'Use at least 8 characters.']);
            redirect('/reset-password/' . $token);
        }
        $userId = EmailToken::consume($token, 'reset');
        if (!$userId) {
            flash('error', 'That reset link has expired. Request a new one.');
            redirect('/forgot-password');
        }
        User::update($userId, ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'email_verified_at' => now()]);
        Auth::revokeAll($userId);
        Auth::login($userId);
        flash('success', 'Password updated. Welcome back.');
        redirect('/discover');
    }

    public function verify(string $token): void
    {
        $userId = EmailToken::consume($token, 'verify');
        if (!$userId) {
            flash('error', 'That link has expired or was already used.');
            redirect(Auth::check() ? '/verify-email' : '/login');
        }
        User::update($userId, ['email_verified_at' => now()]);
        flash('success', 'Email confirmed. You are all set.');
        redirect(Auth::check() ? '/discover' : '/login');
    }

    public function verifyNotice(): string
    {
        $me = $this->me();
        if ($me['email_verified_at'] || !Settings::bool('email_verification')) {
            redirect('/discover');
        }
        return view('auth/verify', ['title' => 'Confirm your email', 'email' => $me['email']]);
    }

    public function resendVerification(): void
    {
        $me = $this->me();
        if (!RateLimit::hit('verify:' . $me['id'], 3, 600)) {
            flash('error', 'Please wait a few minutes before asking for another email.');
            redirect('/verify-email');
        }
        if (Emails::verification($me)) {
            flash('success', 'Sent. Check your inbox and spam folder.');
        } else {
            flash('error', "We couldn't send the email right now. Please try again later.");
        }
        redirect('/verify-email');
    }

    public function restricted(): string
    {
        $me = $this->me();
        if (!in_array($me['status'], ['banned', 'suspended'], true)) {
            redirect('/discover');
        }
        return view('auth/restricted', ['title' => 'Account restricted', 'user' => $me]);
    }
}
