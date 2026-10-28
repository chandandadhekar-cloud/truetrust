<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Models\Photo;
use App\Models\Swipe;

final class LikesController extends Controller
{
    public function index(): string
    {
        $me = $this->me();
        $rows = Swipe::likesReceived((int) $me['id']);
        $photos = Photo::idsForMany(array_column($rows, 'id'));
        $newCount = Swipe::newLikesCount($me);
        DB::query('UPDATE users SET likes_seen_at = NOW() WHERE id = ?', [$me['id']]);

        return view('likes/index', [
            'title'    => 'Likes',
            'nav'      => true,
            'people'   => $rows,
            'photos'   => $photos,
            'ai'       => Photo::showAiLabel($me) ? Photo::aiUsers(array_column($rows, 'id')) : [],
            'newCount' => $newCount,
        ]);
    }
}
