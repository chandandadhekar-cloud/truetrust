<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Controllers\VerificationController;
use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Request;
use App\Models\Photo;
use App\Services\Push;

final class VerificationsController extends AdminController
{
    public function index(): string
    {
        $status = in_array(Request::str('status'), ['pending', 'approved', 'rejected'], true) ? Request::str('status') : 'pending';
        $rows = DB::all(
            "SELECT v.id, v.user_id, v.pose, v.status, v.note, v.created_at, v.reviewed_at, LENGTH(v.selfie) AS has_selfie,
                    u.first_name, u.last_name, u.email, u.is_verified, u.dob, u.city
             FROM verification_requests v JOIN users u ON u.id = v.user_id
             WHERE v.status = ? ORDER BY v.id " . ($status === 'pending' ? 'ASC' : 'DESC') . ' LIMIT 60',
            [$status]
        );
        return $this->render('verifications/index', [
            'title'  => 'Verification',
            'rows'   => $rows,
            'photos' => Photo::idsForMany(array_column($rows, 'user_id')),
            'status' => $status,
            'poses'  => VerificationController::POSES,
        ]);
    }

    public function selfie(int $id): void
    {
        $row = DB::one('SELECT selfie, mime FROM verification_requests WHERE id = ?', [$id]);
        if (!$row || $row['selfie'] === '') {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . $row['mime']);
        header('Cache-Control: private, no-store');
        echo $row['selfie'];
        exit;
    }

    public function review(int $id): void
    {
        $v = DB::one("SELECT id, user_id, status FROM verification_requests WHERE id = ?", [$id]);
        if (!$v || $v['status'] !== 'pending') {
            flash('error', 'That request was already reviewed.');
            redirect('/admin/verifications');
        }
        $approve = Request::str('decision') === 'approve';
        $note = mb_substr(Request::str('note'), 0, 255);
        // Selfies are only kept until a decision is made.
        DB::update('verification_requests', [
            'status'      => $approve ? 'approved' : 'rejected',
            'note'        => $note,
            'selfie'      => '',
            'reviewed_by' => AdminAuth::id(),
            'reviewed_at' => now(),
        ], 'id = ?', [$id]);
        if ($approve) {
            DB::update('users', ['is_verified' => 1], 'id = ?', [$v['user_id']]);
            Push::notify((int) $v['user_id'], 'system', "You're verified", 'Your profile now shows the blue badge.', '/profile');
        } else {
            Push::notify((int) $v['user_id'], 'system', 'Verification not approved', $note !== '' ? $note : 'Please try again with a clear selfie.', '/verify-profile');
        }
        AdminAuth::log('verification.' . ($approve ? 'approve' : 'reject'), 'user', (int) $v['user_id'], $note);
        flash('success', $approve ? 'Approved. The member now has the verified badge.' : 'Rejected. The member can try again.');
        redirect('/admin/verifications');
    }
}
