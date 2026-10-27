<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Session;

final class TeamController extends AdminController
{
    public function index(): string
    {
        return $this->render('team/index', [
            'title'  => 'Team',
            'admins' => DB::all("SELECT * FROM admins ORDER BY role = 'owner' DESC, id"),
            'secret' => Session::pull('secret'),
        ]);
    }

    public function store(): void
    {
        $email = mb_strtolower(Request::str('email'));
        $name = Request::str('name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || DB::value('SELECT 1 FROM admins WHERE email = ?', [$email])) {
            flash('error', 'Enter a name and an email that is not already on the team.');
            redirect('/admin/team');
        }
        $temp = $this->tempPassword();
        $id = DB::insert('admins', [
            'name'                 => mb_substr($name, 0, 80),
            'email'                => $email,
            'password_hash'        => password_hash($temp, PASSWORD_DEFAULT),
            'role'                 => Request::str('role') === 'owner' ? 'owner' : 'moderator',
            'must_change_password' => 1,
            'created_at'           => now(),
        ]);
        Session::flash('secret', ['email' => $email, 'password' => $temp]);
        AdminAuth::log('team.add', 'admin', $id, $email);
        flash('success', 'Team member added.');
        redirect('/admin/team');
    }

    public function update(int $id): void
    {
        $admin = DB::one('SELECT * FROM admins WHERE id = ?', [$id]);
        if (!$admin) {
            abort(404);
        }
        if ($id === AdminAuth::id()) {
            flash('error', "You can't change your own access here.");
            redirect('/admin/team');
        }
        switch (Request::str('action')) {
            case 'deactivate':
                DB::update('admins', ['is_active' => 0], 'id = ?', [$id]);
                break;
            case 'activate':
                DB::update('admins', ['is_active' => 1], 'id = ?', [$id]);
                break;
            case 'reset':
                $temp = $this->tempPassword();
                DB::update('admins', ['password_hash' => password_hash($temp, PASSWORD_DEFAULT), 'must_change_password' => 1], 'id = ?', [$id]);
                Session::flash('secret', ['email' => $admin['email'], 'password' => $temp]);
                break;
            case 'role':
                DB::update('admins', ['role' => $admin['role'] === 'owner' ? 'moderator' : 'owner'], 'id = ?', [$id]);
                break;
        }
        AdminAuth::log('team.' . Request::str('action'), 'admin', $id, $admin['email']);
        flash('success', 'Team member updated.');
        redirect('/admin/team');
    }

    private function tempPassword(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(15))), 0, 14);
    }
}
