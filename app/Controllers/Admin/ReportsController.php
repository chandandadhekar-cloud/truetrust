<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Models\Photo;
use App\Models\User;

final class ReportsController extends AdminController
{
    public function index(): string
    {
        $status = in_array(Request::str('status'), ['open', 'resolved', 'dismissed'], true) ? Request::str('status') : 'open';
        $total = (int) DB::value('SELECT COUNT(*) FROM reports WHERE status = ?', [$status]);
        $p = $this->paginate($total, 30);
        $rows = DB::all(
            "SELECT r.*,
                    t.first_name AS t_first, t.last_name AS t_last, t.email AS t_email, t.is_verified AS t_verified, t.status AS t_status, t.is_seed AS t_seed,
                    s.first_name AS s_first, s.email AS s_email,
                    (SELECT COUNT(*) FROM reports x WHERE x.reported_id = r.reported_id) AS times_reported,
                    (SELECT m.id FROM matches m WHERE (m.user_a = r.reporter_id AND m.user_b = r.reported_id) OR (m.user_b = r.reporter_id AND m.user_a = r.reported_id) LIMIT 1) AS match_id
             FROM reports r JOIN users t ON t.id = r.reported_id JOIN users s ON s.id = r.reporter_id
             WHERE r.status = ? ORDER BY r.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}",
            [$status]
        );
        $photos = Photo::idsForMany(array_column($rows, 'reported_id'));
        return $this->render('reports/index', ['title' => 'Reports', 'rows' => $rows, 'photos' => $photos, 'p' => $p, 'status' => $status]);
    }

    public function resolve(int $id): void
    {
        $r = DB::one('SELECT * FROM reports WHERE id = ?', [$id]);
        if (!$r) {
            abort(404);
        }
        $outcome = Request::str('outcome');
        $note = mb_substr(Request::str('note'), 0, 500);
        $target = (int) $r['reported_id'];

        if (in_array($outcome, ['ban', 'suspend'], true)) {
            User::update($target, ['status' => $outcome === 'ban' ? 'banned' : 'suspended', 'ban_reason' => $note ?: opt_label('report_reasons', $r['reason'])]);
            Auth::revokeAll($target);
            AdminAuth::log('user.' . $outcome, 'user', $target, 'From report #' . $id);
        }
        $status = $outcome === 'dismiss' ? 'dismissed' : 'resolved';
        DB::update('reports', [
            'status'      => $status,
            'admin_note'  => $note,
            'resolved_by' => AdminAuth::id(),
            'resolved_at' => now(),
        ], 'id = ?', [$id]);
        AdminAuth::log('report.' . $status, 'report', $id, $outcome);
        flash('success', $status === 'dismissed' ? 'Report dismissed.' : 'Report resolved.');
        back('/admin/reports');
    }
}
