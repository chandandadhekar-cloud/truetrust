<?php defined('APP_ROOT') || exit;
$st = $u['status'];
$act = static function (string $action, string $label, string $cls = 'btn-outline', string $confirm = '') use ($u) {
    return '<form method="post" action="/admin/users/' . (int) $u['id'] . '/action"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field()
        . '<input type="hidden" name="action" value="' . $action . '"><button class="btn ' . $cls . ' btn-xs">' . e($label) . '</button></form>';
};
?>
<div class="ph">
    <div>
        <h1 class="row" style="gap:10px"><?= e($title) ?><?php if ($u['is_verified']): ?><?= icon('verified', 24) ?><?php endif; ?></h1>
        <p>
            #<?= (int) $u['id'] ?> · <?= e($u['email']) ?>
            <?= $u['dob'] ? ' · ' . age($u['dob']) . ' yrs' : '' ?>
            <?= $u['city'] ? ' · ' . e($u['city']) : '' ?>
        </p>
        <div class="tags" style="margin-top:8px">
            <?php if ($st === 'banned'): ?><span class="tag tag-bad">Banned</span><?php elseif ($st === 'suspended'): ?><span class="tag tag-warn">Suspended</span><?php elseif ($st === 'deleted'): ?><span class="tag">Deleted</span><?php else: ?><span class="tag tag-ok">Active</span><?php endif; ?>
            <?php if ($u['is_seed']): ?><span class="tag">Seed profile</span><?php else: ?><span class="tag tag-sky">Real member</span><?php endif; ?>
            <?php if ($u['is_featured']): ?><span class="tag tag-brand">Boosted</span><?php endif; ?>
            <?php if ($u['is_spotlight']): ?><span class="tag tag-brand">Exclusive spotlight</span><?php endif; ?>
            <?php if ($u['is_hidden']): ?><span class="tag">Hidden</span><?php endif; ?>
            <?php if (!empty($u['is_tester'])): ?><span class="tag tag-sky">Tester</span><?php endif; ?>
            <?php if (!$u['onboarded_at']): ?><span class="tag tag-warn">Onboarding not finished</span><?php endif; ?>
            <?php if ($u['email_verified_at']): ?><span class="tag tag-ok">Email confirmed</span><?php else: ?><span class="tag">Email not confirmed</span><?php endif; ?>
        </div>
    </div>
    <div class="actions"><a href="/admin/users" class="btn btn-ghost"><?= icon('back', 18) ?> Members</a></div>
</div>

<?php if ($secret): ?>
    <div class="secret">Temporary password: <code><?= e($secret) ?></code> <button type="button" class="btn btn-outline btn-xs" data-copy="<?= e($secret) ?>">Copy</button><br><small class="muted">Shown once. Share it privately with the member.</small></div>
<?php endif; ?>
<?php if ($st !== 'active' && $u['ban_reason'] !== ''): ?>
    <div class="notice" style="margin-bottom:18px"><?= icon('info', 18) ?><span>Reason: <?= e($u['ban_reason']) ?></span></div>
<?php endif; ?>

<div class="grid-side">
    <div>
        <div class="panel" id="photos">
            <div class="panel-h"><h2>Photos</h2><span class="muted small"><?= count($photos) ?>/<?= (int) setting('max_photos') ?> · first approved photo is the cover</span></div>
            <div class="panel-b">
                <div class="photo-admin">
                    <?php foreach ($photos as $i => $ph): ?>
                        <div class="pa">
                            <img src="<?= photo_url((int) $ph['id'], 'thumb') ?>" alt="" loading="lazy">
                            <?php if ($ph['status'] !== 'approved'): ?><span class="tag <?= $ph['status'] === 'pending' ? 'tag-warn' : 'tag-bad' ?>"><?= ucfirst($ph['status']) ?></span><?php elseif ($i === 0): ?><span class="tag">Cover</span><?php endif; ?>
                            <div class="pa-bar">
                                <?php if ($i > 0): ?><form method="post" action="/admin/photos/<?= (int) $ph['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="primary"><button title="Make cover">Cover</button></form><?php endif; ?>
                                <?php if ($ph['status'] !== 'approved'): ?><form method="post" action="/admin/photos/<?= (int) $ph['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><button>Approve</button></form><?php endif; ?>
                                <form method="post" action="/admin/photos/<?= (int) $ph['id'] ?>" data-confirm="Delete this photo?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button>Delete</button></form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($u['is_seed'] && count($photos) < (int) setting('max_photos')): ?>
                <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/ai-photos" class="row" style="margin-top:14px;flex-wrap:wrap;gap:8px">
                    <?= csrf_field() ?>
                    <select class="select" name="shots" style="width:auto;min-height:40px"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"<?= $i === 3 ? ' selected' : '' ?>><?= $i ?> AI photo<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select>
                    <button class="btn btn-outline"><?= icon('sparkle', 18) ?> Generate with AI</button>
                </form>
                <?php endif; ?>
                <?php if (count($photos) < (int) setting('max_photos')): ?>
                <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/photos" enctype="multipart/form-data" class="row" style="margin-top:14px;flex-wrap:wrap">
                    <?= csrf_field() ?>
                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="input" style="flex:1;min-width:220px;padding:9px">
                    <button class="btn btn-dark"><?= icon('camera', 18) ?> Upload</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <form class="panel" method="post" action="/admin/users/<?= (int) $u['id'] ?>">
            <?= csrf_field() ?>
            <div class="panel-h"><h2>Profile</h2><div class="actions"><button class="btn btn-dark btn-xs">Save changes</button></div></div>
            <div class="panel-b"><?= partial('admin/users/_form', ['u' => $u]) ?><button class="btn btn-dark">Save changes</button></div>
        </form>

        <div class="panel">
            <div class="panel-h"><h2>Matches & conversations</h2></div>
            <div class="tbl-wrap"><table class="tbl">
                <tbody>
                <?php foreach ($matches as $m): ?>
                    <tr>
                        <td><?= partial('admin/partials/who', ['u' => ['id' => $m['other_id'], 'first_name' => $m['first_name'], 'last_name' => $m['last_name'], 'email' => $m['email'], 'is_verified' => $m['is_verified']], 'photoId' => App\Models\Photo::primaryId((int) $m['other_id'], false)]) ?></td>
                        <td><?= $m['unmatched_at'] ? '<span class="tag">Unmatched</span>' : '<span class="tag tag-ok">Matched</span>' ?></td>
                        <td class="num"><?= (int) $m['msg_count'] ?> msgs</td>
                        <td class="num"><a class="btn btn-outline btn-xs" href="/admin/conversations/<?= (int) $m['id'] ?>">Open chat</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$matches): ?><tr><td class="muted">No matches yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-h"><h2>Visibility</h2></div>
            <div class="panel-b">
                <div class="action-grid">
                    <?= $u['is_verified'] ? $act('unverify', 'Remove verified') : $act('verify', 'Verify badge', 'btn-dark') ?>
                    <?= $u['is_featured'] ? $act('unfeature', 'Remove boost') : $act('feature', 'Boost to top') ?>
                    <?= $u['is_spotlight'] ? $act('unspotlight', 'End spotlight') : $act('spotlight', 'Exclusive spotlight', 'btn-outline', 'While any profile is in exclusive spotlight, members see ONLY spotlight profiles in Discover. Continue?') ?>
                    <?= $u['is_hidden'] ? $act('unhide', 'Show in Discover') : $act('hide', 'Hide from Discover') ?>
                    <?php if (!$u['is_seed']): ?><?= $u['is_tester'] ? $act('untester', 'Remove tester') : $act('tester', 'Make tester') ?><?php endif; ?>
                </div>
                <p class="hint" style="margin-top:12px"><b>Tester</b> accounts can see AI-photo profiles when AI photos are in test mode. <b>Boost</b> puts this profile first in everyone's Discover. <b>Exclusive spotlight</b> shows only spotlight profiles to everyone.</p>
            </div>
        </div>

        <div class="panel">
            <div class="panel-h"><h2>Account</h2></div>
            <div class="panel-b">
                <?php if ($st === 'active'): ?>
                    <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/action" data-confirm="Restrict this account?">
                        <?= csrf_field() ?>
                        <label class="field"><span>Reason (shown to the member)</span><input class="input" name="reason" placeholder="e.g. Fake profile, harassment"></label>
                        <div class="action-grid">
                            <button class="btn btn-outline btn-xs" name="action" value="suspend">Suspend</button>
                            <button class="btn btn-danger btn-xs" name="action" value="ban">Ban</button>
                        </div>
                    </form>
                <?php elseif ($st !== 'deleted'): ?>
                    <?= $act('unban', 'Restore account', 'btn-dark') ?>
                <?php endif; ?>
                <div class="action-grid" style="margin-top:10px">
                    <?php if (!$u['email_verified_at']): ?><?= $act('verify_email', 'Confirm email') ?><?php endif; ?>
                    <?php if (!$u['is_seed']): ?><?= $act('reset_password', 'Temp password', 'btn-outline', 'Create a temporary password? The member will be signed out everywhere.') ?><?php endif; ?>
                    <?php if ($st !== 'deleted'): ?><?= $act('delete', 'Delete account', 'btn-danger', 'Delete this account? Photos and matches are removed.') ?><?php endif; ?>
                    <?php if (App\Core\AdminAuth::isOwner()): ?><?= $act('purge', 'Erase forever', 'btn-danger', 'Permanently erase this account and ALL its messages, reports and history? This cannot be undone.') ?><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-h"><h2>Activity</h2></div>
            <div class="panel-b">
                <dl class="dl">
                    <dt>Likes sent</dt><dd><?= $stats['likes_sent'] ?> (<?= $stats['passes'] ?> passes)</dd>
                    <dt>Likes received</dt><dd><?= $stats['likes_received'] ?></dd>
                    <dt>Matches</dt><dd><?= $stats['matches'] ?></dd>
                    <dt>Messages sent</dt><dd><?= $stats['messages'] ?><?= $stats['flagged'] ? ' · <span class="tag tag-warn">' . $stats['flagged'] . ' flagged</span>' : '' ?></dd>
                    <dt>Reported</dt><dd><?= $stats['reports_against'] ?> times · blocked by <?= $stats['blocked_by'] ?></dd>
                    <dt>Reports made</dt><dd><?= $stats['reports_made'] ?></dd>
                    <dt>Joined</dt><dd><?= date('j M Y, g:i A', strtotime($u['created_at'])) ?></dd>
                    <dt>Last active</dt><dd><?= $u['last_active_at'] ? date('j M Y, g:i A', strtotime($u['last_active_at'])) : '—' ?></dd>
                    <dt>Sign-up IP</dt><dd class="mono"><?= e($u['signup_ip'] ?: '—') ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($reports): ?>
        <div class="panel">
            <div class="panel-h"><h2>Reports against</h2></div>
            <div class="panel-b">
                <?php foreach ($reports as $r): ?>
                    <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                        <div class="row"><span class="tag <?= $r['status'] === 'open' ? 'tag-bad' : '' ?>"><?= e(opt_label('report_reasons', $r['reason'])) ?></span><span class="spacer"></span><small class="muted"><?= time_ago($r['created_at']) ?></small></div>
                        <?php if ($r['details']): ?><p class="small" style="margin-top:6px"><?= e($r['details']) ?></p><?php endif; ?>
                        <small class="muted">by <?= e($r['reporter_name']) ?> · <a class="link" href="/admin/reports?status=<?= e($r['status']) ?>">view</a></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
