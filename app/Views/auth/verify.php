<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <header class="appbar"><div class="appbar-inner">
        <span class="brand"><?= partial('partials/logo') ?><span class="brand-word"><?= e(setting('site_name')) ?></span></span>
        <span class="spacer"></span>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Sign out</button></form>
    </div></header>
    <div class="empty">
        <div class="empty-art"><?= icon('mail', 40) ?></div>
        <h2>Check your inbox</h2>
        <p>We sent a confirmation link to <b style="color:var(--ink)"><?= e($email) ?></b>. Tap it to continue.</p>
        <div style="display:grid;gap:10px;width:100%;max-width:320px;margin-top:14px">
            <a href="/verify-email" class="btn btn-primary btn-block">I've confirmed it</a>
            <form method="post" action="/verify-email" data-submit-once><?= csrf_field() ?><button class="btn btn-outline btn-block">Send the email again</button></form>
        </div>
        <p class="small" style="margin-top:10px">Can't find it? Check spam or promotions.</p>
    </div>
</div>
