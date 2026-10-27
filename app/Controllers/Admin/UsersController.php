<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Session;
use App\Models\Photo;
use App\Models\User;

final class UsersController extends AdminController
{
    private const FLAGS = [
        'verify'      => ['is_verified', 1, 'Marked verified'],
        'unverify'    => ['is_verified', 0, 'Verified badge removed'],
        'feature'     => ['is_featured', 1, 'Boosted to the top of Discover'],
        'unfeature'   => ['is_featured', 0, 'Boost removed'],
        'spotlight'   => ['is_spotlight', 1, 'Exclusive spotlight on'],
        'unspotlight' => ['is_spotlight', 0, 'Exclusive spotlight off'],
        'hide'        => ['is_hidden', 1, 'Hidden from Discover'],
        'unhide'      => ['is_hidden', 0, 'Visible in Discover'],
    ];

    public function index(): string
    {
        [$where, $params] = $this->filters();
        $total = (int) DB::value("SELECT COUNT(*) FROM users u WHERE {$where}", $params);
        $p = $this->paginate($total, 40);
        $sorts = [
            'newest' => 'u.id DESC',
            'oldest' => 'u.id ASC',
            'active' => 'u.last_active_at IS NULL, u.last_active_at DESC',
            'name'   => 'u.first_name ASC',
        ];
        $order = $sorts[Request::str('sort')] ?? $sorts['newest'];
        $users = DB::all(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.gender, u.dob, u.city, u.status, u.is_seed, u.is_verified,
                    u.is_featured, u.is_spotlight, u.is_hidden, u.onboarded_at, u.last_active_at, u.created_at,
                    (SELECT COUNT(*) FROM photos p WHERE p.user_id = u.id AND p.status <> 'rejected') AS photo_count,
                    (SELECT COUNT(*) FROM reports r WHERE r.reported_id = u.id) AS report_count
             FROM users u WHERE {$where} ORDER BY {$order} LIMIT {$p['per']} OFFSET {$p['offset']}",
            $params
        );
        $photos = Photo::idsForMany(array_column($users, 'id'));
        return $this->render('users/index', ['title' => 'Members', 'users' => $users, 'photos' => $photos, 'p' => $p]);
    }

    /** Streams the current member list (same filters as the table) as CSV. Owner only. */
    public function export(): void
    {
        [$where, $params] = $this->filters();
        AdminAuth::log('users.export', null, null, http_build_query($_GET));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="truetrust-members-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'First name', 'Last name', 'Email', 'Gender', 'Age', 'City', 'State', 'Status', 'Type', 'Verified', 'Email confirmed', 'Onboarded', 'Joined', 'Last active']);
        $st = DB::query(
            "SELECT id, first_name, last_name, email, gender, dob, city, state, status, is_seed, is_verified, email_verified_at, onboarded_at, created_at, last_active_at
             FROM users u WHERE {$where} ORDER BY id",
            $params
        );
        while ($u = $st->fetch()) {
            fputcsv($out, [
                $u['id'], $u['first_name'], $u['last_name'], $u['email'], $u['gender'], $u['dob'] ? age($u['dob']) : '',
                $u['city'], $u['state'], $u['status'], $u['is_seed'] ? 'Seed' : 'Real', $u['is_verified'] ? 'Yes' : 'No',
                $u['email_verified_at'] ? 'Yes' : 'No', $u['onboarded_at'] ? 'Yes' : 'No', $u['created_at'], $u['last_active_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    /** @return array{0:string,1:array} */
    private function filters(): array
    {
        $where = "u.status <> 'deleted'";
        $params = [];
        $q = Request::str('q');
        if ($q !== '') {
            if (ctype_digit($q)) {
                $where .= ' AND u.id = ?';
                $params[] = (int) $q;
            } else {
                $where .= " AND (u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.city LIKE ?)";
                array_push($params, "%{$q}%", "%{$q}%", "{$q}%");
            }
        }
        $type = Request::str('type');
        if ($type === 'real') {
            $where .= ' AND u.is_seed = 0';
        } elseif ($type === 'seed') {
            $where .= ' AND u.is_seed = 1';
        }
        $status = Request::str('status');
        if (in_array($status, ['active', 'suspended', 'banned'], true)) {
            $where .= ' AND u.status = ?';
            $params[] = $status;
        } elseif ($status === 'deleted') {
            $where = str_replace("u.status <> 'deleted'", "u.status = 'deleted'", $where);
        } elseif ($status === 'incomplete') {
            $where .= ' AND u.onboarded_at IS NULL';
        }
        $gender = Request::str('gender');
        if (array_key_exists($gender, opt('gender'))) {
            $where .= ' AND u.gender = ?';
            $params[] = $gender;
        }
        $flags = [
            'verified'  => 'u.is_verified = 1',
            'featured'  => 'u.is_featured = 1',
            'spotlight' => 'u.is_spotlight = 1',
            'hidden'    => 'u.is_hidden = 1',
            'nophoto'   => "NOT EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id AND p.status <> 'rejected')",
            'reported'  => 'EXISTS (SELECT 1 FROM reports r WHERE r.reported_id = u.id)',
        ];
        $flag = Request::str('flag');
        if (isset($flags[$flag])) {
            $where .= ' AND ' . $flags[$flag];
        }
        $city = Request::str('city');
        if ($city !== '') {
            $where .= ' AND u.city = ?';
            $params[] = $city;
        }
        return [$where, $params];
    }

    public function show(int $id): string
    {
        $u = User::find($id);
        if (!$u) {
            abort(404);
        }
        $stats = DB::one(
            "SELECT
                (SELECT COUNT(*) FROM swipes WHERE user_id = ? AND action <> 'pass') AS likes_sent,
                (SELECT COUNT(*) FROM swipes WHERE user_id = ? AND action = 'pass') AS passes,
                (SELECT COUNT(*) FROM swipes WHERE target_id = ? AND action <> 'pass') AS likes_received,
                (SELECT COUNT(*) FROM matches WHERE user_a = ? OR user_b = ?) AS matches,
                (SELECT COUNT(*) FROM messages WHERE sender_id = ?) AS messages,
                (SELECT COUNT(*) FROM messages WHERE sender_id = ? AND is_flagged = 1) AS flagged,
                (SELECT COUNT(*) FROM reports WHERE reported_id = ?) AS reports_against,
                (SELECT COUNT(*) FROM reports WHERE reporter_id = ?) AS reports_made,
                (SELECT COUNT(*) FROM blocks WHERE blocked_id = ?) AS blocked_by",
            array_fill(0, 10, $id)
        );
        $matches = DB::all(
            "SELECT m.id, m.created_at, m.unmatched_at, m.last_message_at, o.id AS other_id, o.first_name, o.last_name, o.email, o.is_verified, o.is_seed,
                    (SELECT COUNT(*) FROM messages WHERE match_id = m.id) AS msg_count
             FROM matches m JOIN users o ON o.id = IF(m.user_a = ?, m.user_b, m.user_a)
             WHERE m.user_a = ? OR m.user_b = ? ORDER BY COALESCE(m.last_message_at, m.created_at) DESC LIMIT 30",
            [$id, $id, $id]
        );
        $reports = DB::all(
            'SELECT r.*, u.first_name AS reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.reported_id = ? ORDER BY r.id DESC LIMIT 20',
            [$id]
        );
        return $this->render('users/show', [
            'title'   => trim($u['first_name'] . ' ' . $u['last_name']) ?: 'Member #' . $id,
            'u'       => $u,
            'photos'  => DB::all('SELECT id, status, position FROM photos WHERE user_id = ? ORDER BY position, id', [$id]),
            'stats'   => array_map('intval', $stats),
            'matches' => $matches,
            'reports' => $reports,
            'secret'  => Session::pull('secret'),
        ]);
    }

    public function create(): string
    {
        return $this->render('users/create', ['title' => 'New profile']);
    }

    public function store(): void
    {
        $seed = Request::bool('is_seed');
        $email = mb_strtolower(Request::str('email'));
        $password = (string) Request::input('password', '');
        $first = trim(Request::str('first_name'));
        if ($first === '') {
            flash('error', 'First name is required.');
            remember_input();
            redirect('/admin/users/new');
        }
        if ($email === '') {
            $email = strtolower(preg_replace('/[^a-z]/i', '', $first)) . '.' . bin2hex(random_bytes(3)) . '@seed.truetrust.invalid';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || User::findByEmail($email)) {
            flash('error', 'Use a valid email that is not already registered.');
            remember_input();
            redirect('/admin/users/new');
        }
        if (!$seed && mb_strlen($password) < 8) {
            flash('error', 'Real accounts need a password of at least 8 characters.');
            remember_input();
            redirect('/admin/users/new');
        }
        $data = $this->profileData() + [
            'email'             => $email,
            'password_hash'     => password_hash($password !== '' ? $password : bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'is_seed'           => $seed ? 1 : 0,
            'email_verified_at' => now(),
            'onboarded_at'      => now(),
            'last_active_at'    => now(),
        ];
        $id = User::create($data);
        AdminAuth::log('user.create', 'user', $id, ($seed ? 'Seed' : 'Real') . " profile {$first}");
        flash('success', 'Profile created. Add photos so it appears in Discover.');
        redirect('/admin/users/' . $id);
    }

    public function update(int $id): void
    {
        $u = User::find($id);
        if (!$u) {
            abort(404);
        }
        $data = $this->profileData();
        $email = mb_strtolower(Request::str('email'));
        if ($email !== '' && $email !== $u['email']) {
            $other = User::findByEmail($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($other && (int) $other['id'] !== $id)) {
                flash('error', 'That email is invalid or already used.');
                redirect('/admin/users/' . $id);
            }
            $data['email'] = $email;
        }
        User::update($id, $data);
        AdminAuth::log('user.update', 'user', $id, 'Profile edited');
        flash('success', 'Profile saved.');
        redirect('/admin/users/' . $id);
    }

    /** Profile fields shared by create and update. */
    private function profileData(): array
    {
        $city = find_city(Request::str('city'));
        $pick = static fn (string $key, array $allowed) => in_array(Request::str($key), $allowed, true) ? Request::str($key) : '';
        $dob = Request::str('dob');
        $prompts = [];
        foreach (Request::arr('prompt_q') as $i => $q) {
            $a = trim((string) (Request::arr('prompt_a')[$i] ?? ''));
            if (is_string($q) && $q !== '' && $a !== '') {
                $prompts[] = ['q' => $q, 'a' => mb_substr($a, 0, 200)];
            }
        }
        $data = [
            'first_name'    => mb_substr(trim(Request::str('first_name')), 0, 60),
            'last_name'     => mb_substr(trim(Request::str('last_name')), 0, 60),
            'gender'        => $pick('gender', array_keys(opt('gender'))) ?: null,
            'interested_in' => $pick('interested_in', array_keys(opt('interested_in'))) ?: null,
            'dob'           => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ? $dob : null,
            'bio'           => mb_substr(trim(Request::str('bio')), 0, 1000),
            'job_title'     => mb_substr(Request::str('job_title'), 0, 100),
            'company'       => mb_substr(Request::str('company'), 0, 100),
            'education'     => mb_substr(Request::str('education'), 0, 120),
            'height_cm'     => Request::int('height_cm') ?: null,
            'religion'      => $pick('religion', opt('religion')),
            'mother_tongue' => $pick('mother_tongue', opt('languages')),
            'languages'     => implode(',', array_values(array_intersect(Request::arr('languages'), opt('languages')))),
            'drinking'      => $pick('drinking', array_keys(opt('drinking'))),
            'smoking'       => $pick('smoking', array_keys(opt('smoking'))),
            'diet'          => $pick('diet', array_keys(opt('diet'))),
            'looking_for'   => $pick('looking_for', array_keys(opt('looking_for'))),
            'interests'     => implode(',', array_values(array_intersect(Request::arr('interests'), opt('interests')))),
            'prompts'       => json_encode($prompts, JSON_UNESCAPED_UNICODE),
        ];
        if ($city) {
            $data += ['city' => $city[0], 'state' => $city[1], 'lat' => $city[2], 'lng' => $city[3]];
        }
        return $data;
    }

    public function action(int $id): void
    {
        $u = User::find($id);
        if (!$u) {
            abort(404);
        }
        $action = Request::str('action');
        $reason = mb_substr(Request::str('reason'), 0, 255);
        $msg = $this->apply($u, $action, $reason);
        flash('success', $msg);
        redirect($action === 'purge' ? '/admin/users' : '/admin/users/' . $id);
    }

    public function bulk(): void
    {
        $ids = array_values(array_filter(array_map('intval', Request::arr('ids'))));
        $action = Request::str('action');
        if (!$ids || $action === '' || $action === 'purge') {
            flash('error', 'Select members and an action.');
            back('/admin/users');
        }
        $n = 0;
        foreach ($ids as $id) {
            $u = User::find($id);
            if ($u) {
                $this->apply($u, $action, Request::str('reason'));
                $n++;
            }
        }
        flash('success', "Applied to {$n} " . ($n === 1 ? 'member' : 'members') . '.');
        back('/admin/users');
    }

    /** Executes one moderation action and returns a confirmation message. */
    private function apply(array $u, string $action, string $reason): string
    {
        $id = (int) $u['id'];
        if (isset(self::FLAGS[$action])) {
            [$col, $val, $label] = self::FLAGS[$action];
            User::update($id, [$col => $val]);
            AdminAuth::log('user.' . $action, 'user', $id);
            return $label . '.';
        }
        switch ($action) {
            case 'ban':
            case 'suspend':
                User::update($id, ['status' => $action === 'ban' ? 'banned' : 'suspended', 'ban_reason' => $reason]);
                Auth::revokeAll($id);
                AdminAuth::log('user.' . $action, 'user', $id, $reason);
                return $action === 'ban' ? 'Member banned.' : 'Member suspended.';
            case 'unban':
                User::update($id, ['status' => 'active', 'ban_reason' => '']);
                AdminAuth::log('user.unban', 'user', $id);
                return 'Account restored.';
            case 'verify_email':
                User::update($id, ['email_verified_at' => now()]);
                AdminAuth::log('user.verify_email', 'user', $id);
                return 'Email marked as confirmed.';
            case 'reset_password':
                $temp = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
                User::update($id, ['password_hash' => password_hash($temp, PASSWORD_DEFAULT)]);
                Auth::revokeAll($id);
                Session::flash('secret', $temp);
                AdminAuth::log('user.reset_password', 'user', $id);
                return 'Temporary password created.';
            case 'delete':
                User::softDelete($id);
                AdminAuth::log('user.delete', 'user', $id, $u['email']);
                return 'Account deleted.';
            case 'purge':
                if (!AdminAuth::isOwner()) {
                    abort(403, 'Only the owner can permanently erase accounts.');
                }
                DB::query('DELETE FROM users WHERE id = ?', [$id]);
                AdminAuth::log('user.purge', 'user', $id, $u['email']);
                return 'Account and all its data permanently erased.';
        }
        abort(422, 'Unknown action.');
        return '';
    }

    public function uploadPhotos(int $id): void
    {
        if (!User::find($id)) {
            abort(404);
        }
        $files = Request::files('photos');
        $ok = 0;
        $errors = [];
        foreach ($files as $f) {
            try {
                Photo::store($id, $f, true);
                $ok++;
            } catch (\RuntimeException $e) {
                $errors[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        AdminAuth::log('photo.upload', 'user', $id, "{$ok} photo(s)");
        flash($errors ? 'error' : 'success', $ok . ' photo' . ($ok === 1 ? '' : 's') . ' added.' . ($errors ? ' ' . implode(' ', array_slice($errors, 0, 2)) : ''));
        redirect('/admin/users/' . $id . '#photos');
    }
}
