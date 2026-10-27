<?php defined('APP_ROOT') || exit;
$g = static fn ($k) => e($_GET[$k] ?? '');
?>
<div class="ph">
    <div><h1>Members</h1><p>Search, review and manage every profile.</p></div>
    <div class="actions">
        <?php if (App\Core\AdminAuth::isOwner()): ?><a href="/admin/users/export?<?= e(http_build_query($_GET)) ?>" class="btn btn-outline"><?= icon('download', 18) ?> Export CSV</a><?php endif; ?>
        <a href="/admin/users/new" class="btn btn-dark"><?= icon('plus', 18) ?> New profile</a>
    </div>
</div>
<div class="panel">
    <form class="filters" method="get">
        <input class="input" type="search" name="q" value="<?= $g('q') ?>" placeholder="Name, email, city or ID">
        <select class="select" name="type"><option value="">All types</option><option value="real"<?= selected($g('type'), 'real') ?>>Real members</option><option value="seed"<?= selected($g('type'), 'seed') ?>>Seed profiles</option></select>
        <select class="select" name="status"><option value="">Any status</option><?php foreach (['active' => 'Active', 'incomplete' => 'Onboarding', 'suspended' => 'Suspended', 'banned' => 'Banned', 'deleted' => 'Deleted'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($g('status'), $k) ?>><?= $l ?></option><?php endforeach; ?></select>
        <select class="select" name="gender"><option value="">Any gender</option><?php foreach (opt('gender') as $k => $l): ?><option value="<?= $k ?>"<?= selected($g('gender'), $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <select class="select" name="flag"><option value="">Any flag</option><?php foreach (['verified' => 'Verified', 'featured' => 'Boosted', 'spotlight' => 'Spotlight', 'hidden' => 'Hidden', 'nophoto' => 'No photos', 'reported' => 'Reported'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($g('flag'), $k) ?>><?= $l ?></option><?php endforeach; ?></select>
        <select class="select" name="sort"><?php foreach (['newest' => 'Newest', 'active' => 'Recently active', 'oldest' => 'Oldest', 'name' => 'Name'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($g('sort'), $k) ?>><?= $l ?></option><?php endforeach; ?></select>
        <button class="btn btn-dark btn-xs">Filter</button>
        <?php if (array_filter($_GET)): ?><a href="/admin/users" class="btn btn-ghost btn-xs">Clear</a><?php endif; ?>
    </form>
    <form method="post" action="/admin/users/bulk" id="bulk-form" data-confirm="Apply this action to the selected members?">
        <?= csrf_field() ?>
        <div class="bulkbar hidden" data-bulkbar>
            <b><span data-bulk-count>0</span> selected</b>
            <select class="select" name="action" style="min-height:36px;width:auto">
                <option value="">Choose action…</option>
                <option value="verify">Add verified badge</option><option value="unverify">Remove verified badge</option>
                <option value="feature">Boost to top</option><option value="unfeature">Remove boost</option>
                <option value="spotlight">Exclusive spotlight on</option><option value="unspotlight">Exclusive spotlight off</option>
                <option value="hide">Hide from Discover</option><option value="unhide">Show in Discover</option>
                <option value="suspend">Suspend</option><option value="ban">Ban</option><option value="unban">Restore</option>
                <option value="delete">Delete</option>
            </select>
            <input class="input" name="reason" placeholder="Reason (for bans)" style="min-height:36px;width:220px">
            <button class="btn btn-dark btn-xs">Apply</button>
        </div>
        <div class="tbl-wrap"><table class="tbl" id="users-table">
            <thead><tr>
                <th style="width:30px"><input type="checkbox" data-check-all="#users-table" aria-label="Select all"></th>
                <th>Member</th><th>Age</th><th>City</th><th>Status</th><th class="num">Photos</th><th>Last active</th><th>Joined</th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= (int) $u['id'] ?>"></td>
                    <td><?= partial('admin/partials/who', ['u' => $u, 'photoId' => $photos[(int) $u['id']][0] ?? null]) ?></td>
                    <td><?= $u['dob'] ? age($u['dob']) : '—' ?> <span class="muted"><?= e(substr((string) opt_label('gender', $u['gender']), 0, 1)) ?></span></td>
                    <td><?= e($u['city'] ?: '—') ?></td>
                    <td><div class="tags">
                        <?php if ($u['status'] === 'banned'): ?><span class="tag tag-bad">Banned</span>
                        <?php elseif ($u['status'] === 'suspended'): ?><span class="tag tag-warn">Suspended</span>
                        <?php elseif ($u['status'] === 'deleted'): ?><span class="tag">Deleted</span>
                        <?php elseif (!$u['onboarded_at']): ?><span class="tag tag-warn">Onboarding</span>
                        <?php else: ?><span class="tag tag-ok">Active</span><?php endif; ?>
                        <?php if ($u['is_seed']): ?><span class="tag">Seed</span><?php endif; ?>
                        <?php if ($u['is_featured']): ?><span class="tag tag-brand">Boosted</span><?php endif; ?>
                        <?php if ($u['is_spotlight']): ?><span class="tag tag-brand">Spotlight</span><?php endif; ?>
                        <?php if ($u['is_hidden']): ?><span class="tag">Hidden</span><?php endif; ?>
                        <?php if ($u['report_count']): ?><span class="tag tag-bad"><?= (int) $u['report_count'] ?> reports</span><?php endif; ?>
                    </div></td>
                    <td class="num"><?= (int) $u['photo_count'] ?: '<span class="tag tag-warn">0</span>' ?></td>
                    <td class="muted"><?= $u['last_active_at'] ? time_ago($u['last_active_at']) : '—' ?></td>
                    <td class="muted"><?= date('j M y', strtotime($u['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="8" class="muted" style="padding:30px;text-align:center">No members match these filters.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </form>
    <?= partial('admin/partials/pager', ['p' => $p]) ?>
</div>
