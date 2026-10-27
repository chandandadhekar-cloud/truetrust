<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>New profile</h1><p>Create a profile by hand. You can add photos on the next screen.</p></div></div>
<form class="panel" method="post" action="/admin/users/new" style="max-width:900px">
    <?= csrf_field() ?>
    <div class="setting-row">
        <div><b>Seed profile</b><small>Managed by the team. Can't be signed into, and can be bulk-hidden or removed from Seed profiles.</small></div>
        <span class="switch"><input type="checkbox" name="is_seed" value="1" checked><span></span></span>
    </div>
    <div class="panel-b" style="border-top:1px solid var(--line)">
        <?= partial('admin/users/_form', ['u' => []]) ?>
        <label class="field" style="max-width:420px"><span>Password (only for real accounts)</span><input class="input" type="text" name="password" autocomplete="off"></label>
        <button class="btn btn-dark">Create profile</button>
    </div>
</form>
