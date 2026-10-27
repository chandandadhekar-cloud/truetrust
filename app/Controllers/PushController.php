<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\Request;
use App\Models\User;
use App\Services\Push;

final class PushController extends Controller
{
    public function subscribe(): string
    {
        $me = $this->me();
        $endpoint = Request::str('endpoint');
        if (!preg_match('#^https://[^\s]{10,690}$#', $endpoint)) {
            $this->fail('Invalid subscription.');
        }
        Push::subscribe((int) $me['id'], $endpoint, Request::userAgent());
        return json(['ok' => true]);
    }

    public function unsubscribe(): string
    {
        $me = $this->me();
        Push::unsubscribe((int) $me['id'], Request::str('endpoint'));
        return json(['ok' => true]);
    }

    /** Called by the service worker when a push arrives. */
    public function pending(): string
    {
        $me = $this->me();
        return json(['ok' => true, 'items' => Push::pending((int) $me['id'])]);
    }

    public function preferences(): void
    {
        $me = $this->me();
        User::update((int) $me['id'], [
            'notify_matches'  => Request::bool('notify_matches') ? 1 : 0,
            'notify_messages' => Request::bool('notify_messages') ? 1 : 0,
            'notify_likes'    => Request::bool('notify_likes') ? 1 : 0,
        ]);
        Auth::refresh();
        flash('success', 'Notification settings saved.');
        redirect('/settings#notifications');
    }
}
