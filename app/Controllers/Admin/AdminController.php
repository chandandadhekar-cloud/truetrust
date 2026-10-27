<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\DB;

abstract class AdminController
{
    protected function render(string $view, array $data = []): string
    {
        return view('admin/' . $view, $data, 'admin/layout');
    }

    /** Pending-work counters shown in the sidebar. */
    public static function navCounts(): array
    {
        static $counts = null;
        if ($counts === null) {
            $row = DB::one(
                "SELECT
                    (SELECT COUNT(*) FROM photos WHERE status = 'pending') AS photos,
                    (SELECT COUNT(*) FROM messages WHERE is_flagged = 1 AND is_removed = 0) AS flagged,
                    (SELECT COUNT(*) FROM reports WHERE status = 'open') AS reports,
                    (SELECT COUNT(*) FROM verification_requests WHERE status = 'pending') AS verifications"
            );
            $counts = array_map('intval', $row);
        }
        return $counts;
    }

    /** @return array{page:int, pages:int, offset:int, total:int, per:int} */
    protected function paginate(int $total, int $per = 30): array
    {
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) ($_GET['page'] ?? 1)));
        return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per, 'total' => $total, 'per' => $per];
    }
}
