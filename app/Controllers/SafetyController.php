<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\RateLimit;
use App\Core\Request;
use App\Models\Block;
use App\Models\Report;
use App\Models\User;

final class SafetyController extends Controller
{
    public function report(): string
    {
        $me = $this->me();
        $target = $this->target();
        if (!RateLimit::hit('report:' . $me['id'], 20, 86400)) {
            $this->fail('You have sent a lot of reports today. Our team is reviewing them.', 429);
        }
        Report::create((int) $me['id'], (int) $target['id'], Request::str('reason'), Request::str('details'));
        Block::add((int) $me['id'], (int) $target['id']);
        return json(['ok' => true, 'message' => 'Thanks for telling us. Our team will review this.']);
    }

    public function block(): string
    {
        $me = $this->me();
        $target = $this->target();
        Block::add((int) $me['id'], (int) $target['id']);
        return json(['ok' => true, 'message' => $target['first_name'] . ' is blocked.']);
    }

    private function target(): array
    {
        $me = $this->me();
        $id = Request::int('id');
        $target = $id && $id !== (int) $me['id'] ? User::find($id) : null;
        if (!$target) {
            $this->fail('Profile not found.', 404);
        }
        return $target;
    }
}
