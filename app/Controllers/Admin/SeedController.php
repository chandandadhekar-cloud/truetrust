<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Photo;
use App\Services\AiPhotos;
use App\Services\SeedGenerator;

final class SeedController extends AdminController
{
    public function index(): string
    {
        $stats = DB::one(
            "SELECT
                SUM(gender = 'woman') AS women,
                SUM(gender = 'man') AS men,
                COUNT(*) AS total,
                SUM(NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id)) AS no_photo,
                SUM(NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id) AND gender = 'woman') AS no_photo_women,
                SUM(NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id) AND gender = 'man') AS no_photo_men,
                SUM(is_hidden = 1) AS hidden
             FROM users u WHERE is_seed = 1 AND status <> 'deleted'"
        );
        $byCity = DB::all("SELECT city, COUNT(*) n FROM users WHERE is_seed = 1 AND status <> 'deleted' GROUP BY city ORDER BY n DESC LIMIT 12");
        $recent = DB::all("SELECT id, first_name, last_name, email, is_verified, is_seed, gender, dob, city, job_title FROM users WHERE is_seed = 1 AND status <> 'deleted' ORDER BY id DESC LIMIT 12");
        $failed = DB::all(
            "SELECT j.id, j.user_id, j.shot, j.error, u.first_name FROM ai_photo_jobs j JOIN users u ON u.id = j.user_id
             WHERE j.status = 'failed' ORDER BY j.id DESC LIMIT 10"
        );
        $recentAi = DB::all("SELECT j.photo_id, j.user_id, u.first_name FROM ai_photo_jobs j JOIN users u ON u.id = j.user_id WHERE j.status = 'done' AND j.photo_id IS NOT NULL ORDER BY j.id DESC LIMIT 12");
        return $this->render('seed/index', [
            'ai'       => [
                'configured' => AiPhotos::configured(),
                'counts'     => AiPhotos::counts(),
                'spent'      => AiPhotos::spent(),
                'budget'     => AiPhotos::budget(),
                'quality'    => (string) setting('ai_default_quality'),
                'estimates'  => array_combine(AiPhotos::QUALITIES, array_map(static fn ($q) => AiPhotos::estimate($q), AiPhotos::QUALITIES)),
                'failed'     => $failed,
                'recent'     => $recentAi,
            ],
            'title'   => 'Seed profiles',
            'stats'   => array_map('intval', $stats ?: []),
            'byCity'  => $byCity,
            'recent'  => $recent,
            'photos'  => Photo::idsForMany(array_column($recent, 'id')),
            'visible' => Settings::bool('show_seed_profiles'),
        ]);
    }

    public function generate(): void
    {
        $count = max(1, min(500, Request::int('count', 20)));
        $cities = array_values(array_filter(Request::arr('cities'), static fn ($c) => find_city((string) $c) !== null));
        $ids = (new SeedGenerator())->generate($count, [
            'gender'       => in_array(Request::str('gender'), ['mixed', 'man', 'woman'], true) ? Request::str('gender') : 'mixed',
            'age_min'      => Request::int('age_min', 21),
            'age_max'      => Request::int('age_max', 34),
            'cities'       => $cities,
            'verified_pct' => max(0, min(100, Request::int('verified_pct', 40))),
        ]);
        AdminAuth::log('seed.generate', null, null, count($ids) . ' profiles');
        flash('success', count($ids) . ' seed profiles created. Add photos so they appear in Discover.');
        redirect('/admin/seed');
    }

    /**
     * Distributes uploaded photos across seed profiles of one gender that have
     * no photos yet, a set number per profile.
     */
    public function assignPhotos(): void
    {
        $gender = Request::str('gender') === 'man' ? 'man' : 'woman';
        $per = max(1, min(6, Request::int('per_profile', 1)));
        $files = Request::files('photos');
        if (!$files) {
            flash('error', 'Choose some photos to upload.');
            redirect('/admin/seed');
        }
        $targets = DB::column(
            "SELECT u.id FROM users u WHERE u.is_seed = 1 AND u.status <> 'deleted' AND u.gender = ?
               AND NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id)
             ORDER BY u.id LIMIT " . (int) ceil(count($files) / $per),
            [$gender]
        );
        if (!$targets) {
            flash('error', 'Every ' . ($gender === 'man' ? 'male' : 'female') . ' seed profile already has photos. Generate more profiles first.');
            redirect('/admin/seed');
        }
        $done = 0;
        $failed = 0;
        foreach ($files as $i => $f) {
            $userId = (int) ($targets[intdiv($i, $per)] ?? 0);
            if (!$userId) {
                break;
            }
            try {
                Photo::store($userId, $f, true);
                $done++;
            } catch (\RuntimeException $e) {
                $failed++;
            }
        }
        AdminAuth::log('seed.photos', null, null, "{$done} photos to {$gender} profiles");
        flash($failed ? 'error' : 'success', "{$done} photos added to " . min(count($targets), (int) ceil($done / $per)) . ' profiles.' . ($failed ? " {$failed} files could not be read." : ''));
        redirect('/admin/seed');
    }

    /** Queues AI photos for seed profiles that have no photos yet. */
    public function aiQueue(): void
    {
        if (!AiPhotos::configured()) {
            flash('error', 'Add your OpenAI API key in Settings first.');
            redirect('/admin/settings#ai');
        }
        $gender = Request::str('gender');
        $limit = max(1, min(200, Request::int('profiles', 5)));
        $where = "is_seed = 1 AND status <> 'deleted' AND NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = users.id)
                  AND NOT EXISTS (SELECT 1 FROM ai_photo_jobs j WHERE j.user_id = users.id AND j.status IN ('queued','running'))";
        $params = [];
        if (in_array($gender, ['man', 'woman'], true)) {
            $where .= ' AND gender = ?';
            $params[] = $gender;
        }
        $ids = DB::column("SELECT id FROM users WHERE {$where} ORDER BY id LIMIT {$limit}", $params);
        $n = AiPhotos::queue($ids, Request::int('shots', 3), Request::str('quality', setting('ai_default_quality')));
        AdminAuth::log('ai_photos.queue', null, null, "{$n} profiles");
        flash($n ? 'success' : 'error', $n ? "Queued {$n} profiles. Press Start to generate." : 'No seed profiles without photos match. Generate more profiles first.');
        redirect('/admin/seed#ai');
    }

    /** Queues AI photos for one seed profile (from its admin page). */
    public function aiQueueOne(int $id): void
    {
        if (!AiPhotos::configured()) {
            flash('error', 'Add your OpenAI API key in Settings first.');
            redirect('/admin/settings#ai');
        }
        $n = AiPhotos::queue([$id], Request::int('shots', 3), Request::str('quality', setting('ai_default_quality')));
        flash($n ? 'success' : 'error', $n ? 'Queued. Press Start to generate.' : 'Only seed profiles without a pending queue can get AI photos.');
        redirect('/admin/seed#ai');
    }

    /** Generates one image. Called repeatedly by the browser while the runner is on. */
    public function aiRun(): string
    {
        ignore_user_abort(true);
        @set_time_limit(240);
        return json(['ok' => true] + AiPhotos::runNext());
    }

    public function aiStatus(): string
    {
        return json(['ok' => true, 'counts' => AiPhotos::counts(), 'spent' => AiPhotos::spent(), 'budget' => AiPhotos::budget()]);
    }

    public function aiManage(): void
    {
        if (Request::str('do') === 'retry') {
            $n = AiPhotos::retryFailed();
            flash('success', "{$n} failed photos queued again.");
        } else {
            $n = AiPhotos::clearQueue();
            flash('success', "{$n} queued or failed photos removed.");
        }
        redirect('/admin/seed#ai');
    }

    public function purge(): void
    {
        $mode = Request::str('mode');
        if ($mode === 'hide') {
            $n = DB::query("UPDATE users SET is_hidden = 1 WHERE is_seed = 1")->rowCount();
            AdminAuth::log('seed.hide_all', null, null, "{$n} profiles");
            flash('success', "{$n} seed profiles hidden.");
        } elseif ($mode === 'unhide') {
            $n = DB::query("UPDATE users SET is_hidden = 0 WHERE is_seed = 1")->rowCount();
            AdminAuth::log('seed.unhide_all', null, null, "{$n} profiles");
            flash('success', "{$n} seed profiles visible again.");
        } elseif ($mode === 'delete' && Request::str('confirm') === 'DELETE') {
            $n = DB::query('DELETE FROM users WHERE is_seed = 1')->rowCount();
            AdminAuth::log('seed.delete_all', null, null, "{$n} profiles");
            flash('success', "{$n} seed profiles permanently removed.");
        } else {
            flash('error', 'Type DELETE to confirm.');
        }
        redirect('/admin/seed');
    }
}
