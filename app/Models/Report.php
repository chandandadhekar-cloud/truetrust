<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Settings;

final class Report
{
    public static function create(int $reporterId, int $reportedId, string $reason, string $details): int
    {
        return DB::insert('reports', [
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason'      => array_key_exists($reason, opt('report_reasons')) ? $reason : 'other',
            'details'     => mb_substr(trim($details), 0, 2000),
            'created_at'  => now(),
        ]);
    }

    /**
     * Hides a profile from Discover once enough different members have open
     * reports against it, so moderators can review before more people see it.
     */
    public static function autoHide(int $userId): void
    {
        $threshold = Settings::int('auto_hide_reports');
        if ($threshold <= 0) {
            return;
        }
        $reporters = (int) DB::value("SELECT COUNT(DISTINCT reporter_id) FROM reports WHERE reported_id = ? AND status = 'open'", [$userId]);
        if ($reporters >= $threshold && DB::query('UPDATE users SET is_hidden = 1 WHERE id = ? AND is_hidden = 0', [$userId])->rowCount()) {
            AdminAuth::log('auto.hide', 'user', $userId, "Hidden after {$reporters} reports");
        }
    }
}
