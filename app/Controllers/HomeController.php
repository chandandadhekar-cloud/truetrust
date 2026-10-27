<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Models\MatchModel;
use App\Models\Message;
use App\Models\Swipe;

final class HomeController extends Controller
{
    public function index(): string
    {
        if (Auth::check()) {
            redirect('/discover');
        }
        return view('home/welcome', ['title' => '']);
    }

    public function badges(): string
    {
        $me = $this->me();
        return json([
            'likes'   => Swipe::newLikesCount($me),
            'matches' => MatchModel::unseenCount((int) $me['id']),
            'unread'  => Message::unreadTotal((int) $me['id']),
        ]);
    }
}
