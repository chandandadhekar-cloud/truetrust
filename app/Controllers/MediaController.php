<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\Auth;
use App\Core\DB;

/** Serves profile photos from the database. Only signed-in members and admins can load them. */
final class MediaController extends Controller
{
    public function photo(int $id, string $size): void
    {
        $viewer = Auth::id();
        $isAdmin = AdminAuth::admin() !== null;
        if (!$viewer && !$isAdmin) {
            http_response_code(403);
            exit;
        }
        $meta = DB::one('SELECT p.id, p.user_id, p.status, p.mime, p.created_at, u.status AS user_status FROM photos p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id]);
        $visible = $meta && ($isAdmin || (int) $meta['user_id'] === $viewer
            || ($meta['status'] === 'approved' && $meta['user_status'] !== 'deleted'));
        if (!$visible) {
            http_response_code(404);
            exit;
        }

        $etag = '"p' . $meta['id'] . '-' . $size . '-' . strtotime($meta['created_at']) . '"';
        header_remove('Pragma');
        header_remove('Expires');
        header('Cache-Control: private, max-age=2592000, immutable');
        header('ETag: ' . $etag);
        if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        $col = $size === 'thumb' ? 'thumb' : 'data';
        $bytes = DB::value("SELECT {$col} FROM photo_blobs WHERE photo_id = ?", [$id]);
        if ($bytes === null) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . $meta['mime']);
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }
}
