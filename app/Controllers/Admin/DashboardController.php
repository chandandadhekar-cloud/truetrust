<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Request;

final class DashboardController extends AdminController
{
    public function index(): string
    {
        $s = DB::one(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND status <> 'deleted') AS members,
                (SELECT COUNT(*) FROM users WHERE is_seed = 1 AND status <> 'deleted') AS seeds,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND created_at >= CURDATE()) AS new_today,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND created_at >= NOW() - INTERVAL 7 DAY) AS new_week,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND last_active_at >= NOW() - INTERVAL 1 DAY) AS active_day,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND last_active_at >= NOW() - INTERVAL 10 MINUTE) AS online,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND onboarded_at IS NULL AND status = 'active') AS incomplete,
                (SELECT COUNT(*) FROM users WHERE status = 'banned') AS banned,
                (SELECT COUNT(*) FROM matches) AS matches,
                (SELECT COUNT(*) FROM matches WHERE created_at >= CURDATE()) AS matches_today,
                (SELECT COUNT(*) FROM messages) AS messages,
                (SELECT COUNT(*) FROM messages WHERE created_at >= CURDATE()) AS messages_today,
                (SELECT COUNT(*) FROM swipes WHERE created_at >= CURDATE()) AS swipes_today,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND gender = 'man' AND status = 'active') AS men,
                (SELECT COUNT(*) FROM users WHERE is_seed = 0 AND gender = 'woman' AND status = 'active') AS women"
        );

        $signups = $this->series("SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE is_seed = 0 AND created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d");
        $messages = $this->series("SELECT DATE(created_at) d, COUNT(*) n FROM messages WHERE created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d");
        $recent = DB::all("SELECT id, first_name, last_name, email, is_verified, is_seed, city, gender, dob, onboarded_at, created_at FROM users WHERE is_seed = 0 ORDER BY id DESC LIMIT 8");
        $topCities = DB::all("SELECT city, COUNT(*) n FROM users WHERE is_seed = 0 AND city <> '' AND status = 'active' GROUP BY city ORDER BY n DESC LIMIT 8");

        return $this->render('dashboard', [
            'title'     => 'Dashboard',
            's'         => array_map('intval', $s),
            'signups'   => $signups,
            'messages'  => $messages,
            'recent'    => $recent,
            'topCities' => $topCities,
            'counts'    => self::navCounts(),
        ]);
    }

    /** Fills a 14-day series with zeros for days without rows. */
    private function series(string $sql): array
    {
        $rows = [];
        foreach (DB::all($sql) as $r) {
            $rows[$r['d']] = (int) $r['n'];
        }
        $out = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $out[$d] = $rows[$d] ?? 0;
        }
        return $out;
    }

    public function logs(): string
    {
        $where = '1';
        $params = [];
        $q = Request::str('q');
        if ($q !== '') {
            $where .= ' AND (l.action LIKE ? OR l.details LIKE ?)';
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }
        $total = (int) DB::value("SELECT COUNT(*) FROM admin_logs l WHERE {$where}", $params);
        $p = $this->paginate($total, 50);
        $logs = DB::all(
            "SELECT l.*, a.name AS admin_name FROM admin_logs l LEFT JOIN admins a ON a.id = l.admin_id WHERE {$where} ORDER BY l.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}",
            $params
        );
        return $this->render('logs', ['title' => 'Activity log', 'logs' => $logs, 'p' => $p, 'q' => $q]);
    }

    public function system(): string
    {
        $dbSize = DB::value('SELECT ROUND(SUM(data_length + index_length) / 1048576, 1) FROM information_schema.tables WHERE table_schema = DATABASE()');
        $photoSize = DB::value('SELECT ROUND(SUM(LENGTH(data) + LENGTH(thumb)) / 1048576, 1) FROM photo_blobs');
        $checks = [
            ['PHP version', PHP_VERSION, version_compare(PHP_VERSION, '7.4', '>=')],
            ['Database', (string) DB::value('SELECT VERSION()'), true],
            ['GD (image processing)', extension_loaded('gd') ? 'Installed' : 'Missing: photos are stored unprocessed', extension_loaded('gd')],
            ['EXIF (photo rotation)', function_exists('exif_read_data') ? 'Installed' : 'Missing: some phone photos may appear sideways', function_exists('exif_read_data')],
            ['mbstring', extension_loaded('mbstring') ? 'Installed' : 'Missing', extension_loaded('mbstring')],
            ['OpenSSL (SMTP)', extension_loaded('openssl') ? 'Installed' : 'Missing', extension_loaded('openssl')],
            ['PHP mail()', function_exists('mail') ? 'Available' : 'Disabled: configure SMTP', function_exists('mail')],
            ['HTTPS', Request::isHttps() ? 'Yes' : 'No', Request::isHttps()],
            ['upload_max_filesize', ini_get('upload_max_filesize'), $this->bytes(ini_get('upload_max_filesize')) >= 8 * 1048576],
            ['post_max_size', ini_get('post_max_size'), $this->bytes(ini_get('post_max_size')) >= 8 * 1048576],
            ['memory_limit', ini_get('memory_limit'), ini_get('memory_limit') === '-1' || $this->bytes(ini_get('memory_limit')) >= 128 * 1048576],
            ['max_allowed_packet', round((int) DB::value('SELECT @@max_allowed_packet') / 1048576) . ' MB', (int) DB::value('SELECT @@max_allowed_packet') >= 4 * 1048576],
            ['Database size', ($dbSize ?? 0) . ' MB', true],
            ['Stored photos', ($photoSize ?? 0) . ' MB', true],
            ['Push notifications', \App\Services\Push::publicKey() !== '' ? 'Ready (VAPID keys created)' : 'Unavailable: OpenSSL EC support missing', \App\Services\Push::publicKey() !== ''],
            ['cURL (push delivery)', function_exists('curl_init') ? 'Installed' : 'Missing: using slower fallback', function_exists('curl_init')],
            ['Server time', date('j M Y, g:i A T'), true],
        ];
        $migrations = DB::all('SELECT name, ran_at FROM migrations ORDER BY id DESC');
        $errors = DB::all('SELECT * FROM error_logs ORDER BY id DESC LIMIT 50');
        return $this->render('system', ['title' => 'Health', 'checks' => $checks, 'migrations' => $migrations, 'errors' => $errors]);
    }

    private function bytes(string $v): int
    {
        $n = (int) $v;
        switch (strtolower(substr(trim($v), -1))) {
            case 'g': return $n * 1073741824;
            case 'm': return $n * 1048576;
            case 'k': return $n * 1024;
        }
        return $n;
    }
}
