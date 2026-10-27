<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Photo;

final class PhotoController extends Controller
{
    public function upload(): string
    {
        $me = $this->me();
        $id = (int) $me['id'];
        $file = Request::file('photo');
        if (!$file) {
            $this->fail('Choose a photo to upload.');
        }
        if (Photo::countFor($id) >= max(1, Settings::int('max_photos'))) {
            $this->fail('You have reached the photo limit. Remove one to add another.');
        }
        if (!RateLimit::hit("upload:{$id}", 40, 3600)) {
            $this->fail('Too many uploads in a short time. Try again later.', 429);
        }
        try {
            $photoId = Photo::store($id, $file);
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }
        $status = DB::value('SELECT status FROM photos WHERE id = ?', [$photoId]);
        return json(['ok' => true, 'photo' => ['id' => $photoId, 'url' => photo_url($photoId, 'thumb'), 'status' => $status]]);
    }

    public function delete(int $photoId): string
    {
        $me = $this->me();
        $owner = DB::value('SELECT user_id FROM photos WHERE id = ?', [$photoId]);
        if ((int) $owner !== (int) $me['id']) {
            abort(404);
        }
        if ($me['onboarded_at'] && Photo::countFor((int) $me['id']) <= 1) {
            $this->fail('Keep at least one photo on your profile.');
        }
        Photo::delete($photoId);
        return json(['ok' => true]);
    }

    public function reorder(): string
    {
        $me = $this->me();
        Photo::reorder((int) $me['id'], Request::arr('ids'));
        return json(['ok' => true]);
    }
}
