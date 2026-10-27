<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Activity log</h1><p>Every admin action is recorded here.</p></div></div>
<div class="panel">
    <form class="filters"><input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search actions or details"><button class="btn btn-outline btn-xs">Search</button></form>
    <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td class="muted" style="white-space:nowrap"><?= date('j M, g:i A', strtotime($l['created_at'])) ?></td>
                <td><?= e($l['admin_name'] ?? 'System') ?></td>
                <td><span class="tag"><?= e($l['action']) ?></span></td>
                <td><?php if ($l['target_type'] === 'user'): ?><a class="link" href="/admin/users/<?= (int) $l['target_id'] ?>">User #<?= (int) $l['target_id'] ?></a><?php elseif ($l['target_type']): ?><?= e(ucfirst($l['target_type'])) ?> #<?= (int) $l['target_id'] ?><?php endif; ?></td>
                <td><?= e($l['details']) ?></td>
                <td class="mono muted"><?= e($l['ip']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="muted">Nothing logged yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?= partial('admin/partials/pager', ['p' => $p]) ?>
</div>
