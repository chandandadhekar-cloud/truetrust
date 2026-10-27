<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Verification</h1><p>Compare the selfie with the profile photos. Approve only if it's clearly the same person doing the requested pose.</p></div></div>
<div class="row" style="margin-bottom:14px;gap:6px">
    <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?>
        <a href="?status=<?= $k ?>" class="btn btn-xs <?= $status === $k ? 'btn-dark' : 'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>
<?php foreach ($rows as $r): $pids = $photos[(int) $r['user_id']] ?? []; ?>
    <div class="panel">
        <div class="panel-b">
            <div class="verif">
                <div class="verif-selfie">
                    <?php if ($r['has_selfie']): ?><img src="/admin/verifications/<?= (int) $r['id'] ?>/selfie" alt="Selfie" loading="lazy"><?php else: ?><div class="muted small" style="padding:20px">Selfie deleted after review</div><?php endif; ?>
                    <span class="tag tag-sky">Selfie</span>
                </div>
                <div class="verif-photos">
                    <?php foreach (array_slice($pids, 0, 4) as $pid): ?><img src="<?= photo_url($pid, 'thumb') ?>" alt="" loading="lazy"><?php endforeach; ?>
                    <?php if (!$pids): ?><p class="muted small">No profile photos.</p><?php endif; ?>
                </div>
                <div class="verif-side">
                    <?= partial('admin/partials/who', ['u' => ['id' => $r['user_id'], 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'email' => $r['email'], 'is_verified' => $r['is_verified']]]) ?>
                    <p class="small muted" style="margin:8px 0 12px"><?= $r['dob'] ? age($r['dob']) . ' · ' : '' ?><?= e($r['city']) ?> · sent <?= time_ago($r['created_at']) ?></p>
                    <div class="notice" style="margin-bottom:12px"><?= icon('info', 18) ?><span>Requested pose: <b><?= e($poses[$r['pose']] ?? $r['pose']) ?></b></span></div>
                    <?php if ($r['status'] === 'pending'): ?>
                        <form method="post" action="/admin/verifications/<?= (int) $r['id'] ?>">
                            <?= csrf_field() ?>
                            <label class="field"><span>Note for the member (if rejecting)</span><input class="input" name="note" placeholder="e.g. Face not clearly visible"></label>
                            <div class="action-grid">
                                <button class="btn btn-dark btn-xs" name="decision" value="approve"><?= icon('check', 16) ?> Approve</button>
                                <button class="btn btn-danger btn-xs" name="decision" value="reject">Reject</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p><span class="tag <?= $r['status'] === 'approved' ? 'tag-ok' : 'tag-bad' ?>"><?= ucfirst($r['status']) ?></span> <?= $r['reviewed_at'] ? time_ago($r['reviewed_at']) . ' ago' : '' ?><?= $r['note'] ? ' · ' . e($r['note']) : '' ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="panel"><div class="empty" style="padding:50px"><div class="empty-art"><?= icon('check', 40) ?></div><h2>All clear</h2><p>No <?= $status === 'pending' ? 'selfies waiting for review' : e($status) . ' requests' ?>.</p></div></div><?php endif; ?>
