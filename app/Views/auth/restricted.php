<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <div class="empty">
        <div class="empty-art"><?= icon('lock', 40) ?></div>
        <h2><?= $user['status'] === 'banned' ? 'Your account has been banned' : 'Your account is on hold' ?></h2>
        <p><?= $user['status'] === 'banned'
            ? 'This account broke our Community Guidelines and can no longer use ' . e(setting('site_name')) . '.'
            : 'We have temporarily paused this account while our team reviews it.' ?></p>
        <?php if ($user['ban_reason'] !== ''): ?>
            <div class="notice" style="max-width:340px;text-align:left"><?= icon('info', 20) ?><span><?= e($user['ban_reason']) ?></span></div>
        <?php endif; ?>
        <p class="small">If you think this is a mistake, write to <a class="link" href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a>.</p>
        <form method="post" action="/logout" style="margin-top:10px"><?= csrf_field() ?><button class="btn btn-outline">Sign out</button></form>
    </div>
</div>
