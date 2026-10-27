<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Models\Photo;
use App\Models\Swipe;
use App\Models\User;
use App\Services\Discovery;

final class DiscoverController extends Controller
{
    public function index(): string
    {
        $me = $this->me();
        return view('discover/index', [
            'title'     => 'Discover',
            'nav'       => true,
            'me'        => $me,
            'remaining' => Swipe::remaining((int) $me['id']),
            'likes'     => Swipe::newLikesCount($me),
            'scripts'   => ['deck'],
            'bodyClass' => 'is-discover',
        ]);
    }

    public function deck(): string
    {
        $me = $this->me();
        $exclude = array_slice(array_map('intval', explode(',', (string) ($_GET['exclude'] ?? ''))), 0, 60);
        return json(['ok' => true, 'cards' => Discovery::deck($me, 10, $exclude)]);
    }

    public function swipe(): string
    {
        $me = $this->me();
        $targetId = Request::int('id');
        $action = Request::str('action');
        $result = Swipe::record($me, $targetId, $action);

        $payload = ['ok' => true, 'remaining' => $result['remaining'], 'match' => null];
        if ($result['match']) {
            $other = User::find($targetId);
            $payload['match'] = [
                'id'      => (int) $result['match']['id'],
                'name'    => $other['first_name'],
                'photo'   => photo_url(Photo::primaryId($targetId)),
                'myPhoto' => photo_url(Photo::primaryId((int) $me['id'], false)),
                'myName'  => $me['first_name'],
            ];
        }
        return json($payload);
    }

    public function rewind(): string
    {
        $me = $this->me();
        $targetId = Swipe::rewind((int) $me['id']);
        if (!$targetId) {
            throw new HttpException(422, 'Nothing to undo. You can only bring back the last person you passed on.');
        }
        $u = User::find($targetId);
        if (!$u || $u['status'] !== 'active') {
            throw new HttpException(404, 'That profile is no longer available.');
        }
        return json(['ok' => true, 'card' => User::card($u, $me) + ['liked_you' => false, 'superliked_you' => false]]);
    }

    public function filters(): string
    {
        $me = $this->me();
        $min = max(18, min(70, Request::int('age_min', 18)));
        $max = max($min, min(70, Request::int('age_max', 40)));
        $dist = Request::int('distance', 0);
        $allowed = [0, 10, 25, 50, 100, 250, 500];
        User::update((int) $me['id'], [
            'pref_age_min'     => $min,
            'pref_age_max'     => $max,
            'pref_distance_km' => in_array($dist, $allowed, true) ? $dist : 0,
            'interested_in'    => array_key_exists(Request::str('interested_in'), opt('interested_in')) ? Request::str('interested_in') : $me['interested_in'],
        ]);
        Auth::refresh();
        return json(['ok' => true]);
    }
}
