<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;

final class PageController extends Controller
{
    public function terms(): string
    {
        return $this->page('terms', 'Terms of Service');
    }

    public function privacy(): string
    {
        return $this->page('privacy', 'Privacy Policy');
    }

    public function safety(): string
    {
        return $this->page('safety', 'Dating safety');
    }

    public function guidelines(): string
    {
        return $this->page('guidelines', 'Community Guidelines');
    }

    private function page(string $view, string $title): string
    {
        return view('pages/' . $view, ['title' => $title, 'nav' => Auth::check() && (bool) Auth::user()['onboarded_at']], 'layouts/app');
    }
}
