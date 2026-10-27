<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Change password</h1><p><?= $forced ? 'Choose your own password before continuing. The temporary one stops working after this.' : 'Use a long, unique password.' ?></p></div></div>
<form class="panel" method="post" action="/admin/password" style="max-width:480px">
    <?= csrf_field() ?>
    <div class="panel-b">
        <label class="field"><span>Current password</span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
        <label class="field"><span>New password</span><input class="input" type="password" name="new_password" minlength="10" required autocomplete="new-password"><p class="hint">At least 10 characters.</p></label>
        <button class="btn btn-dark">Save password</button>
    </div>
</form>
