<?php defined('APP_ROOT') || exit; $filter = $_GET['filter'] ?? ''; ?>
<div class="ph">
    <div><h1>Conversations</h1><p>Every chat on the platform. Opening a conversation is recorded in the activity log.</p></div>
    <div class="actions"><a href="/admin/messages/flagged" class="btn btn-outline"><?= icon('alert', 18) ?> Flagged messages</a></div>
</div>
<div class="panel">
    <form class="filters">
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by member name, email or message text">
        <select class="select" name="filter">
            <option value="">With messages</option>
            <option value="flagged"<?= selected($filter, 'flagged') ?>>Has flagged messages</option>
            <option value="all"<?= selected($filter, 'all') ?>>All matches</option>
        </select>
        <button class="btn btn-dark btn-xs">Search</button>
    </form>
    <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Between</th><th>Last message</th><th class="num">Messages</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td style="min-width:230px">
                    <div class="row" style="gap:6px">
                        <span class="avatar" style="width:32px;height:32px;font-size:12px"><?php if ($pa = $photos[(int) $r['user_a']][0] ?? null): ?><img src="<?= photo_url($pa, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($r['a_name'])) ?><?php endif; ?></span>
                        <span class="avatar" style="width:32px;height:32px;font-size:12px;margin-left:-14px;box-shadow:0 0 0 2px var(--surface)"><?php if ($pb = $photos[(int) $r['user_b']][0] ?? null): ?><img src="<?= photo_url($pb, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($r['b_name'])) ?><?php endif; ?></span>
                        <span><b><?= e($r['a_name']) ?></b><?= $r['a_seed'] ? ' <span class="tag">Seed</span>' : '' ?> &amp; <b><?= e($r['b_name']) ?></b><?= $r['b_seed'] ? ' <span class="tag">Seed</span>' : '' ?></span>
                    </div>
                </td>
                <td class="muted" style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['last_body'] ?? '—') ?></td>
                <td class="num"><?= (int) $r['msg_count'] ?><?= $r['flag_count'] ? ' <span class="tag tag-warn">' . (int) $r['flag_count'] . ' flagged</span>' : '' ?></td>
                <td><?= $r['unmatched_at'] ? '<span class="tag">Unmatched</span>' : '<span class="tag tag-ok">Active</span>' ?></td>
                <td class="muted"><?= time_ago($r['last_message_at'] ?? $r['created_at']) ?></td>
                <td class="num"><a class="btn btn-outline btn-xs" href="/admin/conversations/<?= (int) $r['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="padding:30px;text-align:center">No conversations found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?= partial('admin/partials/pager', ['p' => $p]) ?>
</div>
