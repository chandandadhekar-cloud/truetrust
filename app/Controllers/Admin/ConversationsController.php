<?php
namespace App\Controllers\Admin;

defined('APP_ROOT') || exit;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\Request;
use App\Models\Photo;

final class ConversationsController extends AdminController
{
    public function index(): string
    {
        $where = 'EXISTS (SELECT 1 FROM messages x WHERE x.match_id = m.id)';
        $params = [];
        $q = Request::str('q');
        if ($q !== '') {
            $where .= " AND (ua.first_name LIKE ? OR ub.first_name LIKE ? OR ua.email LIKE ? OR ub.email LIKE ?
                        OR EXISTS (SELECT 1 FROM messages s WHERE s.match_id = m.id AND s.body LIKE ?))";
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if (Request::str('filter') === 'flagged') {
            $where .= ' AND EXISTS (SELECT 1 FROM messages f WHERE f.match_id = m.id AND f.is_flagged = 1)';
        }
        if (Request::str('filter') === 'all') {
            $where = str_replace('EXISTS (SELECT 1 FROM messages x WHERE x.match_id = m.id)', '1', $where);
        }
        $from = 'FROM matches m JOIN users ua ON ua.id = m.user_a JOIN users ub ON ub.id = m.user_b';
        $total = (int) DB::value("SELECT COUNT(*) {$from} WHERE {$where}", $params);
        $p = $this->paginate($total, 30);
        $rows = DB::all(
            "SELECT m.*, ua.first_name AS a_name, ua.email AS a_email, ua.is_seed AS a_seed, ub.first_name AS b_name, ub.email AS b_email, ub.is_seed AS b_seed,
                    (SELECT COUNT(*) FROM messages c WHERE c.match_id = m.id) AS msg_count,
                    (SELECT COUNT(*) FROM messages c WHERE c.match_id = m.id AND c.is_flagged = 1) AS flag_count,
                    (SELECT body FROM messages l WHERE l.match_id = m.id ORDER BY l.id DESC LIMIT 1) AS last_body
             {$from} WHERE {$where}
             ORDER BY COALESCE(m.last_message_at, m.created_at) DESC LIMIT {$p['per']} OFFSET {$p['offset']}",
            $params
        );
        $photos = Photo::idsForMany(array_merge(array_column($rows, 'user_a'), array_column($rows, 'user_b')));
        return $this->render('conversations/index', ['title' => 'Conversations', 'rows' => $rows, 'photos' => $photos, 'p' => $p, 'q' => $q]);
    }

    public function show(int $id): string
    {
        $m = DB::one('SELECT * FROM matches WHERE id = ?', [$id]);
        if (!$m) {
            abort(404);
        }
        $a = DB::one('SELECT * FROM users WHERE id = ?', [$m['user_a']]);
        $b = DB::one('SELECT * FROM users WHERE id = ?', [$m['user_b']]);
        $messages = DB::all('SELECT * FROM messages WHERE match_id = ? ORDER BY id', [$id]);
        AdminAuth::log('conversation.view', 'match', $id);
        return $this->render('conversations/show', [
            'title'    => 'Conversation',
            'm'        => $m,
            'a'        => $a,
            'b'        => $b,
            'messages' => $messages,
            'photoA'   => Photo::primaryId((int) $a['id'], false),
            'photoB'   => Photo::primaryId((int) $b['id'], false),
        ]);
    }

    public function flagged(): string
    {
        $total = (int) DB::value('SELECT COUNT(*) FROM messages WHERE is_flagged = 1 AND is_removed = 0');
        $p = $this->paginate($total, 40);
        $rows = DB::all(
            "SELECT msg.*, u.first_name, u.last_name, u.email, u.is_verified, u.is_seed, u.status AS user_status
             FROM messages msg JOIN users u ON u.id = msg.sender_id
             WHERE msg.is_flagged = 1 AND msg.is_removed = 0
             ORDER BY msg.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}"
        );
        $photos = Photo::idsForMany(array_column($rows, 'sender_id'));
        return $this->render('conversations/flagged', ['title' => 'Flagged messages', 'rows' => $rows, 'photos' => $photos, 'p' => $p]);
    }

    public function moderate(int $id): void
    {
        $msg = DB::one('SELECT * FROM messages WHERE id = ?', [$id]);
        if (!$msg) {
            abort(404);
        }
        switch (Request::str('action')) {
            case 'remove':
                DB::update('messages', ['is_removed' => 1], 'id = ?', [$id]);
                AdminAuth::log('message.remove', 'message', $id, mb_substr($msg['body'], 0, 200));
                flash('success', 'Message removed. Both members now see it as removed.');
                break;
            case 'restore':
                DB::update('messages', ['is_removed' => 0], 'id = ?', [$id]);
                AdminAuth::log('message.restore', 'message', $id);
                flash('success', 'Message restored.');
                break;
            case 'clear':
                DB::update('messages', ['is_flagged' => 0], 'id = ?', [$id]);
                AdminAuth::log('message.clear_flag', 'message', $id);
                flash('success', 'Marked as reviewed.');
                break;
        }
        back('/admin/messages/flagged');
    }
}
