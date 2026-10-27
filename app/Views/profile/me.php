<?php defined('APP_ROOT') || exit;
$circ = 2 * M_PI * 70;
?>
<header class="appbar"><div class="appbar-inner">
    <h1>Profile</h1><span class="spacer"></span>
    <a href="/settings" class="icon-btn" aria-label="Settings"><?= icon('settings', 22) ?></a>
</div></header>
<div class="page">
    <div class="me-head">
        <div class="me-photo">
            <svg class="ring" viewBox="0 0 148 148" aria-hidden="true">
                <circle class="track" cx="74" cy="74" r="70"/>
                <circle class="val" cx="74" cy="74" r="70" stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $circ * (1 - $complete / 100) ?>" transform="rotate(-90 74 74)"/>
            </svg>
            <span class="avatar"><?php if ($photo): ?><img src="<?= photo_url($photo, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($me['first_name'])) ?><?php endif; ?></span>
            <span class="me-pct"><?= $complete ?>% complete</span>
        </div>
        <div class="me-name"><?= e($me['first_name']) ?>, <?= age($me['dob']) ?><?php if ($me['is_verified']): ?> <?= icon('verified', 22) ?><?php endif; ?></div>
        <p class="muted"><?= e($me['city']) ?></p>
        <div class="me-actions">
            <a href="/profile/edit" class="btn btn-dark btn-sm"><?= icon('edit', 18) ?> Edit profile</a>
            <a href="/u/<?= (int) $me['id'] ?>" class="btn btn-outline btn-sm" data-preview><?= icon('eye', 18) ?> Preview</a>
        </div>
    </div>

    <?php if ($me['is_hidden']): ?>
        <div class="notice" style="margin-bottom:16px"><?= icon('eye-off', 20) ?><span>Your profile is paused, so new people can't see you. <a class="link" href="/settings">Turn it back on</a></span></div>
    <?php elseif (!$me['is_verified']): ?>
        <a href="/verify-profile" class="notice notice-brand" style="margin-bottom:16px"><?= icon('verified', 20) ?><span>Get the blue badge. Verified profiles get more matches. <b class="link">Verify now</b></span></a>
    <?php elseif ($complete < 70): ?>
        <div class="notice notice-brand" style="margin-bottom:16px"><?= icon('sparkle', 20) ?><span>Complete profiles get up to 3× more likes. Add photos, prompts and a few basics.</span></div>
    <?php endif; ?>

    <div class="stat-row">
        <div class="stat"><b><?= (int) $stats['likes'] ?></b><span>Likes received</span></div>
        <div class="stat"><b><?= (int) $stats['matches'] ?></b><span>Matches</span></div>
        <div class="stat"><b><?= (int) $stats['views'] ?></b><span>Profile views</span></div>
    </div>

    <p class="group-title">Account</p>
    <div class="group">
        <a class="item" href="/verify-profile"><?= icon('verified') ?>Get verified<span class="trail"><?= $me['is_verified'] ? 'Verified' : 'Not yet' ?><?= icon('next', 18) ?></span></a>
        <a class="item" href="/settings"><?= icon('settings') ?>Settings<span class="trail"><?= icon('next', 18) ?></span></a>
        <a class="item" href="/safety"><?= icon('shield') ?>Safety centre<span class="trail"><?= icon('next', 18) ?></span></a>
        <button class="item" data-install-open><?= icon('download') ?>Install the app<span class="trail"><?= icon('next', 18) ?></span></button>
        <form method="post" action="/logout"><?= csrf_field() ?><button class="item"><?= icon('logout') ?>Sign out</button></form>
    </div>
    <p class="center small muted" style="margin-top:22px"><?= e(setting('site_name')) ?> is free. No premium plans, ever.</p>
</div>
