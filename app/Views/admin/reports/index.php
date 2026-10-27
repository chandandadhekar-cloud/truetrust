<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Reports</h1><p>Members reported by other members. Reporters automatically block the person they report.</p></div></div>
<div class="row" style="margin-bottom:14px;gap:6px">
    <?php foreach (['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'] as $k => $l): ?>
        <a href="?status=<?= $k ?>" class="btn btn-xs <?= $status === $k ? 'btn-dark' : 'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>
<?php foreach ($rows as $r): ?>
    <div class="panel">
        <div class="panel-b">
            <div class="row" style="align-items:flex-start;flex-wrap:wrap;gap:16px">
                <div style="flex:1;min-width:260px">
                    <?= partial('admin/partials/who', ['u' => ['id' => $r['reported_id'], 'first_name' => $r['t_first'], 'last_name' => $r['t_last'], 'email' => $r['t_email'], 'is_verified' => $r['t_verified']], 'photoId' => $photos[(int) $r['reported_id']][0] ?? null]) ?>
                    <div class="tags" style="margin:10px 0">
                        <span class="tag tag-bad"><?= e(opt_label('report_reasons', $r['reason'])) ?></span>
                        <?php if ((int) $r['times_reported'] > 1): ?><span class="tag tag-warn">Reported <?= (int) $r['times_reported'] ?> times</span><?php endif; ?>
                        <?php if ($r['t_status'] !== 'active'): ?><span class="tag"><?= e(ucfirst($r['t_status'])) ?></span><?php endif; ?>
                        <?php if ($r['t_seed']): ?><span class="tag">Seed</span><?php endif; ?>
                    </div>
                    <?php if ($r['details']): ?><p style="margin-bottom:8px">"<?= e($r['details']) ?>"</p><?php endif; ?>
                    <p class="small muted">Reported by <a class="link" href="/admin/users/<?= (int) $r['reporter_id'] ?>"><?= e($r['s_first']) ?></a> · <?= date('j M Y, g:i A', strtotime($r['created_at'])) ?>
                        <?php if ($r['match_id']): ?> · <a class="link" href="/admin/conversations/<?= (int) $r['match_id'] ?>">Read their conversation</a><?php endif; ?></p>
                    <?php if ($r['status'] !== 'open'): ?><p class="small" style="margin-top:6px"><b><?= ucfirst($r['status']) ?></b> <?= $r['resolved_at'] ? time_ago($r['resolved_at']) . ' ago' : '' ?><?= $r['admin_note'] ? ': ' . e($r['admin_note']) : '' ?></p><?php endif; ?>
                </div>
                <?php if ($r['status'] === 'open'): ?>
                <form method="post" action="/admin/reports/<?= (int) $r['id'] ?>" style="width:300px;max-width:100%">
                    <?= csrf_field() ?>
                    <label class="field"><span>Note / reason</span><input class="input" name="note" placeholder="Shown to the member if banned"></label>
                    <div class="action-grid">
                        <button class="btn btn-danger btn-xs" name="outcome" value="ban">Ban</button>
                        <button class="btn btn-outline btn-xs" name="outcome" value="suspend">Suspend</button>
                        <button class="btn btn-dark btn-xs" name="outcome" value="warn">Resolve, no action</button>
                        <button class="btn btn-ghost btn-xs" name="outcome" value="dismiss">Dismiss</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="panel"><div class="empty" style="padding:50px"><div class="empty-art"><?= icon('check', 40) ?></div><h2>Nothing here</h2><p>No <?= e($status) ?> reports.</p></div></div><?php endif; ?>
<?= partial('admin/partials/pager', ['p' => $p]) ?>
