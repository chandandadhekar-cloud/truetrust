<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;

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
}
