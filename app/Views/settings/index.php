<?php defined('APP_ROOT') || exit; ?>
<header class="appbar"><div class="appbar-inner">
    <a href="/profile" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
    <h1>Settings</h1>
</div></header>
<div class="page">
    <p class="group-title" style="margin-top:6px">Visibility</p>
    <form method="post" action="/settings/preferences" class="group" data-autosubmit>
        <?= csrf_field() ?>
        <label class="item" style="cursor:pointer">
            <?= icon('eye-off') ?>
            <span style="flex:1">Pause my profile<br><small class="muted" style="font-weight:500">Hide from Discover. Your matches and chats stay.</small></span>
            <span class="switch"><input type="checkbox" name="paused" value="1"<?= checked((bool) $me['is_hidden']) ?>><span></span></span>
        </label>
    </form>

    <p class="group-title">Account</p>
    <div class="group">
        <div class="item"><?= icon('mail') ?>Email<span class="trail" style="max-width:60%;overflow:hidden;text-overflow:ellipsis"><?= e($me['email']) ?></span></div>
        <div class="item"><?= icon('calendar') ?>Member since<span class="trail"><?= date('M Y', strtotime($me['created_at'])) ?></span></div>
    </div>

    <details class="group" id="email" style="margin-top:16px"<?= isset(errors()['email']) ? ' open' : '' ?>>
        <summary class="item" style="cursor:pointer;list-style:none"><?= icon('edit') ?>Change email<span class="trail"><?= icon('down', 18) ?></span></summary>
        <form method="post" action="/settings/email" style="padding:4px 16px 16px" data-submit-once>
            <?= csrf_field() ?>
            <label class="field"><span>New email</span><input class="input" type="email" name="email" required></label>
            <label class="field"><span>Current password</span><input class="input" type="password" name="password" required autocomplete="current-password"></label>
            <?= error_for('email') ?>
            <button class="btn btn-dark btn-sm">Update email</button>
        </form>
    </details>

    <details class="group" id="password" style="margin-top:16px"<?= isset(errors()['current_password']) || isset(errors()['new_password']) ? ' open' : '' ?>>
        <summary class="item" style="cursor:pointer;list-style:none"><?= icon('lock') ?>Change password<span class="trail"><?= icon('down', 18) ?></span></summary>
        <form method="post" action="/settings/password" style="padding:4px 16px 16px" data-submit-once>
            <?= csrf_field() ?>
            <label class="field"><span>Current password</span><input class="input" type="password" name="current_password" required autocomplete="current-password"><?= error_for('current_password') ?></label>
            <label class="field"><span>New password</span><input class="input" type="password" name="new_password" minlength="8" required autocomplete="new-password"><?= error_for('new_password') ?></label>
            <button class="btn btn-dark btn-sm">Update password</button>
        </form>
    </details>

    <p class="group-title">Help & legal</p>
    <div class="group">
        <a class="item" href="/safety"><?= icon('shield') ?>Safety centre<span class="trail"><?= icon('next', 18) ?></span></a>
        <a class="item" href="/guidelines"><?= icon('users') ?>Community guidelines<span class="trail"><?= icon('next', 18) ?></span></a>
        <a class="item" href="/privacy"><?= icon('lock') ?>Privacy policy<span class="trail"><?= icon('next', 18) ?></span></a>
        <a class="item" href="/terms"><?= icon('info') ?>Terms of service<span class="trail"><?= icon('next', 18) ?></span></a>
        <a class="item" href="mailto:<?= e(setting('support_email')) ?>"><?= icon('mail') ?>Contact support<span class="trail"><?= icon('next', 18) ?></span></a>
    </div>

    <div class="group" style="margin-top:16px">
        <form method="post" action="/logout"><?= csrf_field() ?><button class="item"><?= icon('logout') ?>Sign out</button></form>
    </div>

    <details class="group" id="delete" style="margin-top:16px"<?= isset(errors()['delete_password']) ? ' open' : '' ?>>
        <summary class="item item-danger" style="cursor:pointer;list-style:none"><?= icon('trash') ?>Delete account<span class="trail"><?= icon('down', 18) ?></span></summary>
        <form method="post" action="/settings/delete" style="padding:4px 16px 16px" data-confirm="Delete your account permanently? Your matches, chats and photos will be removed.">
            <?= csrf_field() ?>
            <p class="small muted" style="margin-bottom:12px">This removes your profile, photos and matches. It can't be undone. If you just need a break, pause your profile instead.</p>
            <label class="field"><span>Password</span><input class="input" type="password" name="password" required autocomplete="current-password"><?= error_for('delete_password') ?></label>
            <button class="btn btn-danger btn-sm">Delete my account</button>
        </form>
    </details>
    <p class="center small muted" style="margin-top:24px"><?= e(setting('site_name')) ?> · Made in India · Free forever</p>
</div>
