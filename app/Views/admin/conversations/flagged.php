<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Flagged messages</h1><p>Auto-flagged for money requests, contact details, links or keywords you set in Settings.</p></div></div>
<div class="panel">
    <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Sender</th><th>Message</th><th>Why</th><th>When</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= partial('admin/partials/who', ['u' => ['id' => $r['sender_id']] + $r, 'photoId' => $photos[(int) $r['sender_id']][0] ?? null]) ?></td>
                <td style="max-width:420px"><?= e($r['body']) ?></td>
                <td><span class="tag tag-warn"><?= e($r['flag_reason']) ?></span></td>
                <td class="muted"><?= time_ago($r['created_at']) ?></td>
                <td class="num" style="white-space:nowrap">
                    <form method="post" action="/admin/messages/<?= (int) $r['id'] ?>" style="display:inline-flex;gap:6px"><?= csrf_field() ?>
                        <a class="btn btn-outline btn-xs" href="/admin/conversations/<?= (int) $r['match_id'] ?>">Context</a>
                        <button class="btn btn-outline btn-xs" name="action" value="clear">Looks fine</button>
                        <button class="btn btn-danger btn-xs" name="action" value="remove">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="padding:30px;text-align:center">Nothing flagged. All clear.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?= partial('admin/partials/pager', ['p' => $p]) ?>
</div>
