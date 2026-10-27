<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Request;
use App\Models\Photo;

final class PhotosController extends AdminController
{
    public function index(): string
    {
        $rows = DB::all(
            "SELECT p.id, p.user_id, p.created_at, u.first_name, u.last_name, u.email, u.is_verified, u.is_seed
             FROM photos p JOIN users u ON u.id = p.user_id WHERE p.status = 'pending' ORDER BY p.id LIMIT 120"
        );
        return $this->render('photos/index', ['title' => 'Photo review', 'rows' => $rows]);
    }

    public function moderate(int $id): void
    {
        $photo = DB::one('SELECT * FROM photos WHERE id = ?', [$id]);
        if (!$photo) {
            abort(404);
        }
        $action = Request::str('action');
        switch ($action) {
            case 'approve':
                DB::update('photos', ['status' => 'approved'], 'id = ?', [$id]);
                break;
            case 'reject':
                DB::update('photos', ['status' => 'rejected'], 'id = ?', [$id]);
                break;
            case 'delete':
                Photo::delete($id);
                break;
            case 'primary':
                Photo::makePrimary((int) $photo['user_id'], $id);
                break;
            default:
                abort(422);
        }
        AdminAuth::log('photo.' . $action, 'user', (int) $photo['user_id'], 'Photo #' . $id);
        if (Request::wantsJson()) {
            echo json(['ok' => true]);
            exit;
        }
        back('/admin/users/' . $photo['user_id']);
    }
}
