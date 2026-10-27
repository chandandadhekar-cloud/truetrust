<?php defined('APP_ROOT') || exit; $owner = App\Core\AdminAuth::isOwner(); ?>
<div class="ph"><div><h1>Team</h1><p>People who can sign in to this admin panel. Owners can manage the team, settings and permanent deletions.</p></div></div>
<?php if ($secret): ?>
    <div class="secret">Sign-in for <b><?= e($secret['email']) ?></b> · temporary password <code><?= e($secret['password']) ?></code> <button type="button" class="btn btn-outline btn-xs" data-copy="<?= e($secret['password']) ?>">Copy</button><br><small class="muted">Shown once. They'll choose their own password when they first sign in at <?= e(url('/admin')) ?></small></div>
<?php endif; ?>
<div class="grid-side">
    <div class="panel"><div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td><b><?= e($a['name']) ?></b><br><small class="muted"><?= e($a['email']) ?></small></td>
                <td><span class="tag <?= $a['role'] === 'owner' ? 'tag-brand' : '' ?>"><?= ucfirst($a['role']) ?></span></td>
                <td><?= $a['is_active'] ? '<span class="tag tag-ok">Active</span>' : '<span class="tag">Disabled</span>' ?><?= $a['must_change_password'] ? ' <span class="tag tag-warn">Temp password</span>' : '' ?></td>
                <td class="muted"><?= $a['last_login_at'] ? time_ago($a['last_login_at']) . ' ago' : 'Never' ?></td>
                <td class="num">
                    <?php if ($owner && (int) $a['id'] !== App\Core\AdminAuth::id()): ?>
                    <form method="post" action="/admin/team/<?= (int) $a['id'] ?>" style="display:inline-flex;gap:6px"><?= csrf_field() ?>
                        <button class="btn btn-outline btn-xs" name="action" value="reset">Reset password</button>
                        <button class="btn btn-outline btn-xs" name="action" value="role">Make <?= $a['role'] === 'owner' ? 'moderator' : 'owner' ?></button>
                        <button class="btn btn-<?= $a['is_active'] ? 'danger' : 'dark' ?> btn-xs" name="action" value="<?= $a['is_active'] ? 'deactivate' : 'activate' ?>"><?= $a['is_active'] ? 'Disable' : 'Enable' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <?php if ($owner): ?>
    <form class="panel" method="post" action="/admin/team">
        <?= csrf_field() ?>
        <div class="panel-h"><h2>Add team member</h2></div>
        <div class="panel-b">
            <label class="field"><span>Name</span><input class="input" name="name" required></label>
            <label class="field"><span>Email</span><input class="input" type="email" name="email" required></label>
            <label class="field"><span>Role</span><select class="select" name="role"><option value="moderator">Moderator</option><option value="owner">Owner</option></select></label>
            <button class="btn btn-dark">Add</button>
        </div>
    </form>
    <?php endif; ?>
</div>
