<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\HttpException;
use App\Core\Request;
use App\Models\Block;
use App\Models\MatchModel;
use App\Models\Message;
use App\Models\Photo;
use App\Models\User;

final class ChatController extends Controller
{
    public function index(): string
    {
        $me = $this->me();
        $all = MatchModel::listFor((int) $me['id']);
        $fresh = array_values(array_filter($all, static fn ($m) => $m['last'] === null));
        $convos = array_values(array_filter($all, static fn ($m) => $m['last'] !== null));
        return view('chat/index', [
            'title'  => 'Chats',
            'nav'    => true,
            'fresh'  => $fresh,
            'convos' => $convos,
            'me'     => $me,
        ]);
    }

    public function show(int $matchId): string
    {
        $me = $this->me();
        [$match, $other] = $this->load($matchId, (int) $me['id']);
        MatchModel::markSeen($match, (int) $me['id']);
        Message::markRead($matchId, (int) $me['id']);

        $messages = array_map(static fn ($m) => Message::toJson($m, (int) $me['id']), Message::forMatch($matchId));
        return view('chat/show', [
            'title'    => $other['first_name'],
            'nav'      => false,
            'match'    => $match,
            'other'    => $other,
            'photo'    => Photo::primaryId((int) $other['id']),
            'messages' => $messages,
            'ended'    => $match['unmatched_at'] !== null,
            'scripts'  => ['chat'],
            'bodyClass' => 'is-chat',
        ]);
    }

    public function poll(int $matchId): string
    {
        $me = $this->me();
        [$match] = $this->load($matchId, (int) $me['id']);
        $after = Request::int('after');
        $rows = Message::forMatch($matchId, $after);
        Message::markRead($matchId, (int) $me['id']);
        $lastMineRead = \App\Core\DB::value(
            'SELECT MAX(id) FROM messages WHERE match_id = ? AND sender_id = ? AND read_at IS NOT NULL',
            [$matchId, $me['id']]
        );
        return json([
            'ok'       => true,
            'messages' => array_map(static fn ($m) => Message::toJson($m, (int) $me['id']), $rows),
            'readUpTo' => (int) $lastMineRead,
            'ended'    => $match['unmatched_at'] !== null,
        ]);
    }

    public function send(int $matchId): string
    {
        $me = $this->me();
        [$match] = $this->load($matchId, (int) $me['id']);
        $msg = Message::send($match, (int) $me['id'], Request::str('body'));
        return json(['ok' => true, 'message' => Message::toJson($msg, (int) $me['id'])]);
    }

    public function unmatch(int $matchId): void
    {
        $me = $this->me();
        [$match] = $this->load($matchId, (int) $me['id']);
        MatchModel::unmatch($match, (int) $me['id']);
        flash('success', 'Unmatched. They won\'t be able to message you.');
        redirect('/matches');
    }

    /** @return array{0: array, 1: array} */
    private function load(int $matchId, int $userId): array
    {
        $match = MatchModel::forMember($matchId, $userId);
        if (!$match) {
            throw new HttpException(404, 'Conversation not found.');
        }
        $otherId = MatchModel::otherId($match, $userId);
        $other = User::find($otherId);
        if (!$other || $other['status'] !== 'active' || Block::between($userId, $otherId)) {
            throw new HttpException(404, 'This conversation is no longer available.');
        }
        return [$match, $other];
    }
}
